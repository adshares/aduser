import json
import logging
import urllib

from twisted.internet import defer, reactor
from twisted.internet.defer import succeed
from twisted.internet.protocol import Protocol
from twisted.web.client import Agent
from twisted.web.iweb import IBodyProducer
from twisted.web.util import redirectTo
from zope.interface import implements

import const
from aduser.proxy.client import ProviderClient


class StringProducer(object):
    implements(IBodyProducer)

    def __init__(self, body):
        self.body = body
        self.length = len(body)

    def startProducing(self, consumer):
        consumer.write(self.body)
        return succeed(None)

    def pauseProducing(self):
        pass

    def stopProducing(self):
        pass


class ReceiverProtocol(Protocol):
    def __init__(self, finished):
        self.finished = finished
        self.body = []

    def dataReceived(self, databytes):
        self.body.append(databytes)

    def connectionLost(self, reason):
        self.finished.callback(''.join(self.body))


class SimpleProviderClient(ProviderClient):

    def __init__(self):
        self.logger = logging.getLogger(__name__)

    def pixel(self, request):
        """
        Use the last cookie as our tracking id aka user id.

        :param request:
        :return: 302 Redirect to a data probiver server.
        """
        tid = request.cookies[-1]
        self.logger.info("Redirecting to {0}".format("{0}/{1}".format(const.DATA_PROVIDER, urllib.quote(tid))))
        return redirectTo("{0}/{1}".format(const.DATA_PROVIDER, urllib.quote(tid)), request)

    @defer.inlineCallbacks
    def get_data(self, user_identifier):
        """
        Get user profile from provider server.

        :param user_identifier: User identifier
        :return: User profile dictionary.
        """
        agent = Agent(reactor)
        data_url = '{0}/get_data/{1}'.format(const.DATA_PROVIDER_CONSUMER_INFO, user_identifier)

        self.logger.info("Fetching data from {0}".format(data_url))
        response = yield agent.request('GET', data_url)

        finished = defer.Deferred()
        response.deliverBody(ReceiverProtocol(finished))
        data = yield finished
        defer.returnValue(json.loads(data) if data else None)
