import json
import logging
from base64 import b64decode

from twisted.internet import defer, reactor
from twisted.internet.protocol import Protocol
from twisted.web.client import Agent

# http://ip-api.com/docs/api:serialized_php#usage_limits

logger = logging.getLogger(__name__)

taxonomy_name = 'examples.ipapi'
taxonomy_version = '0.0.1'
taxonomy = {'meta': {'name': taxonomy_name,
                     'version': taxonomy_version},
            'data': [{'label': 'Country',
                      'key': 'countryCode',
                      'type': 'input'}]}

agent = Agent(reactor)

PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")


class JsonProtocol(Protocol):
    def __init__(self, finished):
        self.finished = finished
        self.body = []

    def dataReceived(self, databytes):
        self.body.append(databytes)

    def connectionLost(self, reason):
        self.finished.callback(json.loads(''.join(self.body)))


def pixel(request):
    request.setHeader(b"content-type", b"image/gif")
    return PIXEL_GIF


@defer.inlineCallbacks
def update_data(user, request_data):

    url = 'http://ip-api.com/json/' + request_data['device']['ip']

    response = yield agent.request('GET', bytes(url))

    finished = defer.Deferred()
    response.deliverBody(JsonProtocol(finished))
    data = yield finished

    user['keywords'].update({'countryCode': data['countryCode']})

    defer.returnValue(user)
