import socket

from twisted.trial.unittest import TestCase
from twisted.internet.protocol import Protocol
from twisted.internet import reactor, defer
from twisted.web.client import Agent

from aduser.proxy import const as proxy_const
from aduser.simple_provider.server import const as provider_const
from aduser.simple_provider import server as provider_server
from aduser.proxy import server


class WebclientTestCase(TestCase):

    class ReceiverProtocol(Protocol):
        def __init__(self, finished):
            self.finished = finished
            self.body = []

        def dataReceived(self, databytes):  # NOSONAR
            self.body.append(databytes)

        def connectionLost(self, reason):  # NOSONAR
            self.finished.callback(''.join(self.body))

    @defer.inlineCallbacks
    def return_response_body(self, response):
        finished = defer.Deferred()
        response.deliverBody(WebclientTestCase.ReceiverProtocol(finished))
        data = yield finished
        defer.returnValue(data)

    def setUp(self):  # NOSONAR
        self.agent = Agent(reactor)
        self.port = server.configure_server()
        self.provider_port = provider_server.configure_server()

        host = socket.gethostbyname(socket.gethostname())
        self.url = 'http://{0}:{1}'.format(host, self.port_number)

        self.timeout = 5

    def tearDown(self):  # NOSONAR
        self.port.stopListening()
        self.provider_port.stopListening()


class ProxyWebclientTestCase(WebclientTestCase):
    port_number = proxy_const.SERVER_PORT

    def setUp(self):  # NOSONAR
        WebclientTestCase.setUp(self)


class ProviderWebclientTestCase(WebclientTestCase):
    port_number = provider_const.SERVER_PORT
