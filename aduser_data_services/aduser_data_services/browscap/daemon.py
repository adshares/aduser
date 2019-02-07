from __future__ import print_function

import json
import os
import sys
from datetime import datetime

from pybrowscap.loader.csv import load_file
from twisted.internet import reactor
from twisted.internet.endpoints import UNIXServerEndpoint
from twisted.internet.protocol import Factory, Protocol

#: Socket file
SOCK_FILE = os.getenv('ADUSER_DATA_BROWSCAP_SOCK_FILE', '/tmp/apshares/aduser-data-browscap.sock')

#: Path for Browscap database file (csv)
CSV_PATH = os.getenv('ADUSER_DATA_BROWSCAP_CSV_PATH', '/opt/adshares/aduser_data/browscap.csv')

browscap_database = None


class BrowscapResponseProtocol(Protocol):
    #: Empty result for None result from our database
    _empty_result = bytes("{}")

    def dataReceived(self, data):
        """
        When data is received, query our source, respond and disconnect.

        :param data: Query string.
        :return:
        """
        try:
            query_result = browscap_database.search(json.loads(data))

            if query_result:
                # Convert result to dictionary, encode in json and push to the wire
                self.transport.write(bytes(json.dumps(query_result.items())))
            else:
                self.transport.write(self._empty_result)

            self.transport.loseConnection()

        except ValueError:
            self.transport.write(self._empty_result)


class BrowscapProtocolFactory(Factory):
    protocol = BrowscapResponseProtocol


def init_database():
    global browscap_database
    if os.path.exists(CSV_PATH):
        print("Compiling browscap data, this may take a while.")
        browscap_database = load_file(CSV_PATH)
        print("Browscap data compiled.")

    if not browscap_database:
        print("Couldn't load browscap database, exiting.")
        sys.exit(1)


def configure_server():
    endpoint = UNIXServerEndpoint(reactor, SOCK_FILE)
    return endpoint.listen(BrowscapProtocolFactory())


if __name__ == '__main__':

    print(datetime.now())

    init_database()

    port = configure_server()

    print("Listening on {0}.".format(SOCK_FILE))

    reactor.run()
