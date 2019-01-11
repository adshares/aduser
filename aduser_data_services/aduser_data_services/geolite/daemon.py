from __future__ import print_function

import os
import sys

from aduser_data_services import DataResponseProtocol
from geoip import open_database
from twisted.internet import reactor
from twisted.internet.endpoints import UNIXServerEndpoint
from twisted.internet.protocol import Factory

#: Socket file
SOCK_FILE = os.getenv('ADUSER_DATA_GEOIP_SOCK_FILE', '/tmp/aduser-data-geoip.sock')
GEOLITE_PATH = os.getenv('ADUSER_DATA_GEOLITE_PATH', '/var/www/aduser_data/GeoLite2-City.mmdb')

geolite_database = None


class GeoLiteProtocolFactory(Factory):

    def buildProtocol(self, addr):
        p = self.DataResponseProtocol()
        p.factory = self
        p.query_function = geolite_database.lookup
        return p


if __name__ == '__main__':

    if os.path.exists(GEOLITE_PATH):
        print("Opening GeoLite database.")
        geolite_database = open_database(GEOLITE_PATH)
        print("GeoLite database operational.")

    if not geolite_database:
        print("Couldn't load GeoLite database, exiting.")
        sys.exit(1)

    endpoint = UNIXServerEndpoint(reactor, SOCK_FILE)
    endpoint.listen(GeoLiteProtocolFactory())

    print("Listening on {0}.".format(SOCK_FILE))

    reactor.run()
