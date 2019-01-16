import json

from twisted.internet import defer, protocol, reactor
from twisted.internet.endpoints import UNIXClientEndpoint
from twisted.internet.error import ConnectError


class JSONProtocol(protocol.Protocol):

    def __init__(self):
        self.dataDeferred = defer.Deferred()

    def dataReceived(self, data):
        """
        Get data and send it to data callback

        :return:
        """
        self.dataDeferred.callback(json.loads(data))

    def connectionMade(self):
        """
        Send query to data backend (data stored in factory object)

        :return:
        """
        self.transport.write(self.factory.data)


class DataClientFactory(protocol.ClientFactory):
    protocol = JSONProtocol

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
        """
        Connect to unix server and collects results

        :param data: Data to send
        :return: Data (probably a dictionary) or None
        """
        try:
            # Get protocol instance
            response = yield self.endpoint.connect(DataClientFactory(json.dumps(data)))

            # Get data
            response_data = yield response.dataDeferred

            defer.returnValue(response_data)
        except ConnectError:
            defer.returnValue(None)
