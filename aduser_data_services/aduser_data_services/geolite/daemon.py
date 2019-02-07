from __future__ import print_function

import json
import os
import sys
from datetime import datetime

from geoip import open_database
from twisted.internet import reactor
from twisted.internet.endpoints import UNIXServerEndpoint
from twisted.internet.protocol import Factory, Protocol

#: Socket file
SOCK_FILE = os.getenv('ADUSER_DATA_GEOLITE_SOCK_FILE', '/tmp/adshares/aduser-data-geolite.sock')

#: Path for GeoLite database file (mmdb)
GEOLITE_PATH = os.getenv('ADUSER_DATA_GEOLITE_PATH', '/opt/adshares/aduser_data/GeoLite2-City.mmdb')

#: Geolite database instance
geolite_database = None


class GeoLiteResponseProtocol(Protocol):
    #: Empty result for None result from our database
    _empty_result = bytes("{}")

    def dataReceived(self, data):
        """
        When data is received, query our source, respond and disconnect.

        :param data: Query string.
        :return:
        """
        try:
            query_result = geolite_database.lookup(json.loads(data))

            if query_result:
                # Convert result to dictionary
                query_result = query_result.to_dict()

                # Convert frozenset to list (frozenset is not JSON serializable)
                query_result['subdivisions'] = list(query_result['subdivisions'])

                # Encode in JSON and push it on the wire
                self.transport.write(bytes(json.dumps(query_result)))

            else:
                self.transport.write(self._empty_result)

        except ValueError:
            self.transport.write(self._empty_result)

        self.transport.loseConnection()


class GeoLiteProtocolFactory(Factory):
    protocol = GeoLiteResponseProtocol


def init_database():
    global geolite_database
    if os.path.exists(GEOLITE_PATH):
        print("Opening GeoLite database.")
        geolite_database = open_database(GEOLITE_PATH)
        print("GeoLite database operational.")

    if not geolite_database:
        print("Couldn't load GeoLite database, exiting.")
        sys.exit(1)


def configure_server():
    endpoint = UNIXServerEndpoint(reactor, SOCK_FILE)
    return endpoint.listen(GeoLiteProtocolFactory())


if __name__ == '__main__':
    print(datetime.now())

    init_database()

    port = configure_server()

    print("Listening on {0}.".format(SOCK_FILE))

    reactor.run()
