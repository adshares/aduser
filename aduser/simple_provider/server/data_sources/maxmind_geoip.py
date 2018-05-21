import logging
import os

from geoip import open_database

from aduser.simple_provider.server.data_sources import UserDataSource


class GeoIpSource(UserDataSource):

    def __init__(self, mmdb_path):
        self.db = None
        self.mmdb_path = mmdb_path
        self.data_url = "http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz"
        self.logger = logging.getLogger(__name__)

    def init(self):
        if not self.db:
            self.logger.info("Initializing GeoIP database.")
            if os.path.exists(self.csv_path):
                self.db = open_database(self.mmdb_path)
            else:
                self.logger.error("GeoIP database not found.")
            self.logger.info("GeoIP database initialized.")

    def update_user(self, user):
        if user['client_ip'] and self.db:
            match = self.db.lookup(user['client_ip'])
            if match:
                user['keywords'].update(match.to_dict())
            else:
                self.logger.warning("IP not found in GeoIP db.")

    def update_source(self):
        self.logger.info("Udpating GeoIP database.")
        self.db = open_database(self.mmdb_path)
        self.logger.info("GeoIP database updated.")
