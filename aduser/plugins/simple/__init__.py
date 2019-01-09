import logging
from base64 import b64decode

from twisted.internet import defer

from aduser.plugins.simple import const
from aduser.plugins.simple.utils import browscap_utils, geoip_utils, taxonomy_utils

db = None
mmdb_path = const.GEOLITE_PATH
browscap = None
csv_path = const.BROWSCAP_CSV_PATH

taxonomy_name = 'simple'
taxonomy_version = '0.0.1'
taxonomy = {'meta': {'name': taxonomy_name,
                     'version': taxonomy_version},
            'data': taxonomy_utils.get_values()}

logger = logging.getLogger(__name__)
PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")
PIXEL_PNG = b64decode(
    "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAFhAJ/wlseKgAAAABJRU5ErkJggg==")


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
    yield update_data_from_browscap(user, request_data)
    yield update_data_from_geoip(user, request_data)

    defer.returnValue(user)


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

            user['keywords'].update({'platform': browser_caps.get('platform'),
                                     'device_type': browser_caps.get('device_type'),
                                     'javascript': browser_caps.get('javascript'),
                                     'browser': browser_caps.get('browser')})

            if browser_caps.is_crawler():
                user['human_score'] = 0.0
            else:
                user['human_score'] = 1.0

        else:
            logger.warning("User agent not identified.")

    defer.returnValue(user)
