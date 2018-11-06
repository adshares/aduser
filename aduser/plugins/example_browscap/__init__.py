import logging
import os

import utils

schema_name = 'example_browscap'
schema_version = '0.0.1'

browscap = None
csv_path = os.path.join(os.getenv('ADUSER_DATA_PATH'), 'browcap.csv')
logger = logging.getLogger(__name__)


def init():
    global browscap

    if not browscap:
        logger.info("Initializing browscap database.")
        browscap = utils.Database(csv_path)
        browscap.init()
        if browscap.db:
            logger.info("Browscap database initialized.")
        else:
            browscap = None


def update_user(user):
    global browscap

    if browscap:
        try:
            browser_caps = browscap.get_info(user['headers']['User-Agent'])
            if browser_caps:
                user['keywords'].update(browser_caps.items())

                if browser_caps.is_crawler():
                    user['human_score'] = 0.0
            else:
                logger.warning("User agent not identified.")
        except KeyError:
            logger.warning("Missing header user-agent.")


def normalize(data):
    return data
