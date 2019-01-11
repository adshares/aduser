import os
import logging
from twisted.internet import defer, reactor, protocol
from twisted.internet.endpoints import UNIXClientEndpoint
from twisted.internet.error import ConnectError


class DataRequestProtocol(protocol.Protocol):

    def __init__(self):
        self.response = None

    def dataReceived(self, data):
        """
        Save response data.

        :return:
        """
        self.response = data

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
            response = yield self.endpoint.connect(DataClientFactory(data))
            defer.returnValue(response.content)
        except ConnectError:
            defer.returnValue(None)


@defer.inlineCallbacks
def run(SOCK_FILE):
    data_source = UnixDataProvider(SOCK_FILE)
    ret1 = yield data_source.query('hello')
    ret2 = yield data_source.query('Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0')
    print ret1
    print ret2
    defer.returnValue(ret1)


if __name__ == '__main__':

    SOCK_FILE = os.getenv('ADUSER_DATA_BROWSCAP_SOCK_FILE', '/tmp/aduser-browscap.sock')
    LOG_LEVEL = logging.DEBUG

    logging.basicConfig(format='[%(asctime)s] %(name)-6s %(levelname)-9s %(message)s',
                        datefmt="%Y-%m-%dT%H:%M:%SZ",
                        handlers=[logging.StreamHandler()],
                        level=LOG_LEVEL)

    reactor.callLater(1, reactor.stop)

    res = run(SOCK_FILE)
    if res:
        print res
    reactor.run()
