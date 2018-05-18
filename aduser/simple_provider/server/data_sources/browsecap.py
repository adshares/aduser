import logging

from pybrowscap.loader.csv import load_file
from aduser.simple_provider.server.data_sources import UserDataSource


class BrowsCapSource(UserDataSource):

    def __init__(self, csv_path):
        self.browscap = None
        self.csv_path = csv_path
        self.data_url = "https://browscap.org/stream?q=BrowsCapCSV"
        self.logger = logging.getLogger(__name__)

    def init(self):
        if not self.browscap:
            self.logger.info("Initializing browscap")
            self.browscap = load_file(self.csv_path)
            self.logger.info("Browscap initialized.")

    def update_user(self, user):
        try:
            browser_caps = self.browscap.search(user['headers']['User-Agent'])
            if browser_caps:
                user['keywords'].update(browser_caps.items())

                if browser_caps.is_crawler():
                    user['human_score'] = 0.0
            else:
                self.logger.warning("User agent not identified.")
        except KeyError:
            self.logger.warning("Missing header user-agent.")

    def update_source(self):
        self.logger.info("Udpating browscap")
        self.browscap = load_file(self.csv_path)
        self.logger.info("Browscap updated")
