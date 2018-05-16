import urllib
import logging

from twisted.internet import defer, reactor
from twisted.web.util import redirectTo
from twisted.web.client import Agent

from aduser.proxy.client import ProviderClient
import const


class SimpleProviderClient(ProviderClient):

    def __init__(self):
        self.logger = logging.getLogger(__name__)

    def pixel(self, request):
        tid = request.cookies[-1]
        self.logger.info("Redirecting to {0}".format("{0}/{1}".format(const.DATA_PROVIDER, urllib.quote(tid))))
        return redirectTo("{0}/{1}".format(const.DATA_PROVIDER, urllib.quote(tid)), request)

    @defer.inlineCallbacks
    def get_data(self, user_identifier):

        agent = Agent(reactor)
        data_url = '{0}/get_data/{1}'.format(const.DATA_PROVIDER_CONSUMER_INFO, user_identifier)

        self.logger.info("Fetching data from {0}".format(data_url))
        response = yield agent.request('GET', data_url)

        defer.returnValue({'user_id': user_identifier,
                           'request_id': user_identifier,
                           'human_score': 0.5,
                           'keywords': {}
                           })
