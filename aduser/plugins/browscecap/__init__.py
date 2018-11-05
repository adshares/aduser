import logging
import os

from twisted.internet import defer

from pybrowscap.loader.csv import load_file

plugin_name = 'browsecap'

browsecap = None
csv_path = csv_path
data_url = "https://browscap.org/stream?q=BrowsCapCSV"
logger = logging.getLogger(__name__)


@defer.inlineCallbacks
def init():
    if not browscap:
        logger.info("Initializing browscap")
        yield update_source()
        if browscap:
            logger.info("Browscap initialized.")


def update_user(user):
    if browscap:
        try:
            browser_caps = browscap.search(user['headers']['User-Agent'])
            if browser_caps:
                user['keywords'].update(browser_caps.items())

                if browser_caps.is_crawler():
                    user['human_score'] = 0.0
            else:
                logger.warning("User agent not identified.")
        except KeyError:
            logger.warning("Missing header user-agent.")


@defer.inlineCallbacks
def update_source():
    global browsecap
    if os.path.exists(csv_path):
        logger.info("Udpating browscap")
        browscap = yield load_file(csv_path)
        if browscap:
            logger.info("Browscap updated")
    else:
        logger.error("Browscap not found.")
