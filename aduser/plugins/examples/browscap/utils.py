import os

from pybrowscap.loader.csv import load_file


class Database:

    def __init__(self, csv_path):
        self.db = None
        self.csv_path = csv_path

    def init(self):
        self.load_database()

    def get_info(self, user_agent):
        match = self.db.search(user_agent)
        return match

    def load_database(self):
        if os.path.exists(self.csv_path):
            self.db = load_file(self.csv_path)
