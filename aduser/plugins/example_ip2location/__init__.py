import logging
import os

import IP2Location
from twisted.internet import defer

taxonomy_name = 'example_ip2location'
taxonomy_version = '0.0.1'

logger = logging.getLogger(__name__)


@defer.inlineCallbacks
def init():
    if not db:
        logger.info("Initializing IP2Location database.")
        yield update_source()
        if db:
            logger.info("IP2Location initialized.")


def update_user(user):
    if user['client_ip'] and db:
        match = db.get_all(user['client_ip'])
        if match:
            for info in [attr for attr in dir(match) if not attr.startswith('_')]:
                user['keywords'][info] = getattr(match, info)
        else:
            logger.warning("IP not found in IP2Location db.")


@defer.inlineCallbacks
def update_source():
    if os.path.exists(bin_path):
        logger.info("Updating IP2Location database.")
        db = yield IP2Location.IP2Location(bin_path)
        if db:
            logger.info("IP2Location database updated.")
    else:
        logger.error("IP2Location database not found.")


def normalize(data):
    return data
