import json
import logging

from twisted.internet.protocol import Protocol
from twisted.internet import reactor, defer
from twisted.web.client import Agent

from aduser.simple_provider.server.data_sources import UserDataSource


class JsonProtocol(Protocol):
    def __init__(self, finished):
        self.finished = finished
        self.body = []

    def dataReceived(self, databytes):
        self.body.append(databytes)

    def connectionLost(self, reason):
        self.finished.callback(json.loads(''.join(self.body)))


class IpApiSource(UserDataSource):

    def __init__(self, mmdb_path):
        self.db = None
        self.mmdb_path = mmdb_path
        self.data_url = "http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz"
        self.agent = Agent(reactor)

    def init(self):
        logger = logging.getLogger(__name__)
        logger.info("IpApi initialized.")

    @defer.inlineCallbacks
    def update_user(self, user, ip):

        url = 'http://ip-api.com/json/' + ip

        response = yield self.agent.request('GET', url)

        finished = defer.Deferred()
        response.deliverBody(JsonProtocol(finished))
        data = yield finished
        defer.returnValue(data)
