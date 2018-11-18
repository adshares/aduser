import logging
import os
from base64 import b64decode

from twisted.internet import defer

from aduser.plugins.simple.utils import browscap_utils, geoip_utils, schema_utils

db = None
mmdb_path = os.getenv('ADUSER_GEOLITE_PATH')
browscap = None
csv_path = os.getenv('ADUSER_BROWSCAP_CSV_PATH')

schema_name = 'simple'
schema_version = '0.0.1'
schema = {'meta': {'name': schema_name,
                   'ver': schema_version},
          'values': schema_utils.get_values()}

logger = logging.getLogger(__name__)
PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")


def pixel(request):
    request.setHeader(b"content-type", b"image/gif")
    return PIXEL_GIF


def init():
    global db
    global browscap

    if not db:
        logger.info("Initializing GeoIP database.")
        db = geoip_utils.Database(mmdb_path)
        db.init()
        if db.db:
            logger.info("GeoIP database initialized.")
        else:
            db = None

    if not browscap:
        logger.info("Initializing browscap database.")
        browscap = browscap_utils.Database(csv_path)
        browscap.init()
        if browscap.db:
            logger.info("Browscap database initialized.")
        else:
            browscap = None


@defer.inlineCallbacks
def update_data(user, request_data):
    user_cap = yield update_data_from_browscap(user, request_data)
    user_geo = yield update_data_from_geoip(user, request_data)
    user_cap.update(user_geo)
    defer.returnValue(user_cap)


@defer.inlineCallbacks
def update_data_from_geoip(user, request_data):
    global db
    if db:
        data = yield db.get_info(request_data['device']['ip'])
        if data:
            user['keywords'].update({'country': data['country']})
        else:
            logger.warning("IP not found in GeoIP db.")
    defer.returnValue(user)


@defer.inlineCallbacks
def update_data_from_browscap(user, request_data):
    global browscap
    if browscap:
        browser_caps = yield browscap.get_info(request_data['device']['ua'])
        if browser_caps:

            keywords = {'platform': browser_caps.get('platform'),
                        'device_type': browser_caps.get('device_type'),
                        'javascript': browser_caps.get('javascript'),
                        'browser': browser_caps.get('browser')}

            user['keywords'].update(keywords)
            # user['keywords'].update(browser_caps.items())

            if browser_caps.is_crawler():
                user['human_score'] = 0.0
        else:
            logger.warning("User agent not identified.")
    defer.returnValue(user)
