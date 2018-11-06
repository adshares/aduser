from zope.interface import implementer

from twisted.trial.unittest import TestCase
from twisted.internet.protocol import Protocol
from twisted.internet import reactor, defer
from twisted.web.client import Agent
from twisted.web.iweb import IBodyProducer

import json
from mock import patch
import os

from aduser import server_utils, plugin


class WebclientTestCase(TestCase):

    data_plugin = os.getenv('ADUSER_DATA_PROVIDER')

    class ReceiverProtocol(Protocol):
        def __init__(self, finished):
            self.finished = finished
            self.body = []

        def dataReceived(self, databytes):  # NOSONAR
            self.body.append(databytes)

        def connectionLost(self, reason):  # NOSONAR
            self.finished.callback(''.join(self.body))

    class JsonReceiverProtocol(Protocol):
        def __init__(self, finished):
            self.finished = finished
            self.body = []

        def dataReceived(self, databytes):  # NOSONAR
            self.body.append(databytes)

        def connectionLost(self, reason):  # NOSONAR
            self.finished.callback(json.loads(''.join(self.body)))

    @implementer(IBodyProducer)
    class JsonBytesProducer(object):
        def __init__(self, body):
            self.body = json.dumps(body)
            self.length = len(self.body)

        def startProducing(self, consumer):
            consumer.write(self.body)
            return defer.succeed(None)

        def pauseProducing(self):
            pass

        def stopProducing(self):
            pass

    @defer.inlineCallbacks
    def return_response_body(self, response):
        finished = defer.Deferred()
        response.deliverBody(WebclientTestCase.ReceiverProtocol(finished))
        data = yield finished
        defer.returnValue(data)

    @defer.inlineCallbacks
    def return_response_json(self, response):
        finished = defer.Deferred()
        response.deliverBody(WebclientTestCase.JsonReceiverProtocol(finished))
        data = yield finished
        defer.returnValue(data)

    def setUp(self):  # NOSONAR

        self.agent = Agent(reactor)

        with patch('aduser.const.ADUSER_DATA_PROVIDER', self.data_plugin):
            self.port = server_utils.configure_server()

        self.url = 'http://{0}:{1}'.format(self.port.getHost().host, self.port.getHost().port)

        self.timeout = 5

    def tearDown(self):  # NOSONAR
        self.port.stopListening()
        plugin.data = None
