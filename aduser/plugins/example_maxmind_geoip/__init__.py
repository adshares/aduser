import logging
import os
from base64 import b64decode

from aduser.plugins.example_maxmind_geoip import utils

db = None
mmdb_path = os.getenv('ADUSER_GEOLITE_PATH')

schema_name = 'example_maxmind_geoip'
schema_version = '0.0.1'
schema = {'meta': {'name': schema_name,
                   'ver': schema_version},
          'values': {'country':
                     {'label': 'Country',
                      'type': 'input'}}}

logger = logging.getLogger(__name__)
PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")


def pixel(request):
    request.setHeader(b"content-type", b"image/gif")
    return PIXEL_GIF


def init():
    global db

    if not db:
        logger.info("Initializing GeoIP database.")
        db = utils.Database(mmdb_path)
        db.init()
        if db.db:
            logger.info("GeoIP database initialized.")
        else:
            db = None


def update_data(user, request_data):
    global db

    if db:
        data = db.get_info(request_data['device']['ip'])
        if data:
            user['keywords'].update({'country': data['country']})
        else:
            logger.warning("IP not found in GeoIP db.")

    return user
