import logging
import os

from pybrowscap.loader.csv import load_file

logger = logging.getLogger(__name__)


class Database:

    def __init__(self, csv_path):
        self.db = None
        self.csv_path = csv_path

    def init(self):
        logging.disable(logging.DEBUG)
        logger.info('Loading browscap database. This make take a while.')
        self.load_database()
        logger.info('Loading finished.')

    def get_info(self, user_agent):
        match = self.db.search(user_agent)
        return match

    def load_database(self):
        if os.path.exists(self.csv_path):
            self.db = load_file(self.csv_path)
