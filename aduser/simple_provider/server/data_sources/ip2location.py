import logging
import os

from twisted.internet import defer

import IP2Location

from aduser.simple_provider.server.data_sources import UserDataSource


class Ip2LocationSource(UserDataSource):

    def __init__(self, bin_path):
        self.db = None
        self.bin_path = bin_path
        self.logger = None

    @defer.inlineCallbacks
    def init(self):
        self.logger = logging.getLogger(__name__)
        if not self.db:
            self.logger.info("Initializing IP2Location database.")
            yield self.update_source()
            if self.db:
                self.logger.info("IP2Location initialized.")

    def update_user(self, user):
        if user['client_ip'] and self.db:
            match = self.db.get_all(user['client_ip'])
            if match:
                for info in [attr for attr in dir(match) if not attr.startswith('_')]:
                    user['keywords'][info] = getattr(match, info)
            else:
                self.logger.warning("IP not found in IP2Location db.")

    @defer.inlineCallbacks
    def update_source(self):
        if os.path.exists(self.bin_path):
            self.logger.info("Updating IP2Location database.")
            self.db = yield IP2Location.IP2Location(self.bin_path)
            if self.db:
                self.logger.info("IP2Location database updated.")
        else:
            self.logger.error("IP2Location database not found.")
