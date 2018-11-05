import json
import logging

from twisted.internet.protocol import Protocol
from twisted.internet import reactor, defer
from twisted.web.client import Agent

logger = logging.getLogger(__name__)

plugin_name = 'IP-api'

class JsonProtocol(Protocol):
    def __init__(self, finished):
        self.finished = finished
        self.body = []

    def dataReceived(self, databytes):
        self.body.append(databytes)

    def connectionLost(self, reason):
        self.finished.callback(json.loads(''.join(self.body)))


agent = Agent(reactor)


def init():
    logger.info("IpApi initialized.")


@defer.inlineCallbacks
def update_user(user, ip):

    url = 'http://ip-api.com/json/' + ip

    response = yield agent.request('GET', url)

    finished = defer.Deferred()
    response.deliverBody(JsonProtocol(finished))
    data = yield finished
    defer.returnValue(data)
