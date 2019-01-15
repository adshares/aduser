import json

from twisted.internet import defer, protocol, reactor
from twisted.internet.endpoints import UNIXClientEndpoint
from twisted.internet.error import ConnectError


class DataRequestProtocol(protocol.Protocol):

    def __init__(self):
        self.content = None

    def dataReceived(self, data):
        """
        Save response data.

        :return:
        """
        self.content = json.loads(data)

    def connectionMade(self):
        """
        Send query to data backend (data stored in factory object)

        :return:
        """
        self.transport.write(self.factory.data)


class DataClientFactory(protocol.ClientFactory):
    protocol = DataRequestProtocol

    def __init__(self, data):
        self.data = data


class UnixDataProvider:
    """
    Actual client
    """

    def __init__(self, socket_path):
        self.endpoint = UNIXClientEndpoint(reactor, socket_path)

    @defer.inlineCallbacks
    def query(self, data):
        try:
            response = yield self.endpoint.connect(DataClientFactory(json.dumps(data)))
            defer.returnValue(response.content)
        except ConnectError:
            defer.returnValue(None)
