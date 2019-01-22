import importlib
import json

from twisted.internet import defer, protocol, reactor
from twisted.internet.endpoints import UNIXClientEndpoint
from twisted.internet.error import ConnectError

from aduser.data import const as data_const

#: Attribute where the data plugin is instantiated.
provider = None


def configure_plugin():
    """
    Initialize the plugin by name.
    Searches for a package with the same name in ADUSER_PLUGINS_PATH and imports it.
    Allows access via `data` attribute.

    :return:
    """
    global provider
    provider = importlib.import_module(data_const.DATA_PROVIDER)
    return provider


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
        self.endpoint = UNIXClientEndpoint(reactor, socket_path, timeout=1)

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
