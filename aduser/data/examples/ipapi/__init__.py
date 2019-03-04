import json
from base64 import b64decode

from twisted.internet import defer, reactor
from twisted.internet.protocol import Protocol
from twisted.web.client import Agent
from aduser.data import const as data_const

# http://ip-api.com/docs/api:serialized_php#usage_limits

#: Meta information - taxonomy name
taxonomy_name = 'examples.ipapi'
taxonomy_version = '0.0.1'
taxonomy = {'meta': {'name': taxonomy_name,
                     'version': taxonomy_version},
            'data': [{'label': 'Country',
                      'key': 'countryCode',
                      'type': 'input'}]}

#: Twisted web client
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


def score(tracking_id, request):
    return None


def score_data(tracking_id, token, request):
    return data_const.DEFAULT_HUMAN_SCORE


def pixel(tracking_id, request):
    request.setHeader(b"content-type", b"image/gif")
    return PIXEL_GIF


@defer.inlineCallbacks
def update_data(user, request_data):
    """
    Update user data

    :param user: User data to update
    :param request_data: Request data - must include IP address.
    :return: Updated user data (via deferred)
    """
    url = 'http://ip-api.com/json/' + request_data['device']['ip']

    response = yield agent.request('GET', bytes(url))

    finished = defer.Deferred()
    response.deliverBody(JsonProtocol(finished))
    data = yield finished

    user['keywords'].update({'countryCode': data['countryCode']})

    defer.returnValue(user)
