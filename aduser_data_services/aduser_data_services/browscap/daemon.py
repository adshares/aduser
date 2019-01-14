from __future__ import print_function

import os
import sys

from aduser_data_services import DataResponseProtocol
from pybrowscap.loader.csv import load_file
from twisted.internet import reactor
from twisted.internet.endpoints import UNIXServerEndpoint
from twisted.internet.protocol import Factory

#: Socket file
SOCK_FILE = os.getenv('ADUSER_DATA_BROWSCAP_SOCK_FILE', '/tmp/aduser-data-browscap.sock')
CSV_PATH = os.getenv('ADUSER_DATA_BROWSCAP_CSV_PATH', '/var/www/aduser_data/browscap.csv')

browscap_database = None


class BrowscapProtocolFactory(Factory):

    def buildProtocol(self, addr):
        p = DataResponseProtocol()
        p.factory = self
        p.query_function = browscap_database.search
        return p


if __name__ == '__main__':

    if os.path.exists(CSV_PATH):
        print("Compiling browscap data, this may take a while.")
        browscap_database = load_file(CSV_PATH)
        print("Browscap data compiled.")

    if not browscap_database:
        print("Couldn't load browscap database, exiting.")
        sys.exit(1)

    endpoint = UNIXServerEndpoint(reactor, SOCK_FILE)
    endpoint.listen(BrowscapProtocolFactory())

    print("Listening on {0}.".format(SOCK_FILE))

    reactor.run()
