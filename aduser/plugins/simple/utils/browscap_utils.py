import cPickle as pickle
import os

from pybrowscap.loader.csv import load_file


class Database:

    def __init__(self, csv_path):
        self.db = None
        self.csv_path = csv_path
        self.pickle_path = csv_path + '.pickle'

    def init(self):
        # logging.disable(logging.DEBUG)
        self.load_database()

    def get_info(self, user_agent):
        match = self.db.search(user_agent)
        return match

    def load_database(self):

        if os.getenv('PICKLE_PYBROWSCAP', True) and os.path.exists(self.pickle_path):
            with open(self.pickle_path, 'rb') as f:
                self.db = pickle.load(f)
        else:
            if os.path.exists(self.csv_path):
                self.db = load_file(self.csv_path)
                if not os.path.exists(self.pickle_path):
                    with open(self.pickle_path, 'wb') as f:
                        pickle.dump(self.db, f, -1)
