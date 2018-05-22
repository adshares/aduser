import logging
import os

from twisted.internet import defer

from pybrowscap.loader.csv import load_file
from aduser.simple_provider.server.data_sources import UserDataSource


class BrowsCapSource(UserDataSource):

    def __init__(self, csv_path):
        self.browscap = None
        self.csv_path = csv_path
        self.data_url = "https://browscap.org/stream?q=BrowsCapCSV"
        self.logger = None

    @defer.inlineCallbacks
    def init(self):
        self.logger = logging.getLogger(__name__)
        if not self.browscap:
            self.logger.info("Initializing browscap")
            if os.path.exists(self.csv_path):
                self.browscap = yield load_file(self.csv_path)
            else:
                self.logger.error("Browscap not found.")
            if self.browscap:
                self.logger.info("Browscap initialized.")

    def update_user(self, user):
        if self.browscap:
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

    @defer.inlineCallbacks
    def update_source(self):
        self.logger.info("Udpating browscap")
        self.browscap = yield load_file(self.csv_path)
        if self.browscap:
            self.logger.info("Browscap updated")
