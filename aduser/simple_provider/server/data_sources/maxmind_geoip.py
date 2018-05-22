import logging
import os

from twisted.internet import defer

from geoip import open_database

from aduser.simple_provider.server.data_sources import UserDataSource


class GeoIpSource(UserDataSource):

    def __init__(self, mmdb_path):
        self.db = None
        self.mmdb_path = mmdb_path
        self.data_url = "http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz"
        self.logger = None

    @defer.inlineCallbacks
    def init(self):
        self.logger = logging.getLogger(__name__)
        if not self.db:
            self.logger.info("Initializing GeoIP database.")
            if os.path.exists(self.mmdb_path):
                self.db = yield open_database(self.mmdb_path)
            else:
                self.logger.error("GeoIP database not found.")
            if self.db:
                self.logger.info("GeoIP database initialized.")

    def update_user(self, user):
        if user['client_ip'] and self.db:
            match = self.db.lookup(user['client_ip'])
            if match:
                user['keywords'].update(match.to_dict())
            else:
                self.logger.warning("IP not found in GeoIP db.")

    @defer.inlineCallbacks
    def update_source(self):
        self.logger.info("Udpating GeoIP database.")
        self.db = yield open_database(self.mmdb_path)
        if self.db:
            self.logger.info("GeoIP database updated.")
