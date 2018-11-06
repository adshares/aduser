import os

from geoip import open_database


class Database:

    def __init__(self, mmbdb_path):
        self.db = None
        self.mmdb_path = mmbdb_path

    def init(self):
        self.load_database()

    def get_info(self, client_ip):
        match = self.db.lookup(client_ip)
        if match:
            return match.to_dict()

    def load_database(self):
        if os.path.exists(self.mmdb_path):
            self.db = open_database(self.mmdb_path)
