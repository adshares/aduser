import logging
import os
from base64 import b64decode

from aduser.plugins.example_browscap import utils

taxonomy_name = 'example_browscap'
taxonomy_version = '0.0.1'

browscap = None
csv_path = os.getenv('ADUSER_BROWSCAP_CSV_PATH')
logger = logging.getLogger(__name__)
PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")
taxonomy = {'meta': {'name': taxonomy_name,
                     'version': taxonomy_version},
            'data': []}


def pixel(request):
    """
    :return: pixel image
    """
    request.setHeader(b"content-type", b"image/gif")
    return PIXEL_GIF


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


def update_data(user, request_data):
    global browscap

    if browscap:
        try:
            browser_caps = browscap.get_info(request_data['device']['ua'])
            if browser_caps:

                for i in browser_caps.items():
                    user['keywords'].append({i: browser_caps.get(i)})

                if browser_caps.is_crawler():
                    user['human_score'] = 0.0

            else:
                logger.warning("User agent not identified.")
        except KeyError:
            logger.warning("Missing header user-agent.")

    return user
