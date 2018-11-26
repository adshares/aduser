import logging
import os
from base64 import b64decode

from aduser.plugins.examples.maxmind_geoip import utils

db = None
mmdb_path = os.getenv('ADUSER_GEOLITE_PATH')

taxonomy_name = 'examples.maxmind_geoip'
taxonomy_version = '0.0.1'
taxonomy = {'meta': {'name': taxonomy_name,
                     'version': taxonomy_version},
            'data': [{'label': 'Country',
                      "key": "country",
                      'type': 'input'}]}

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
            user['keywords'].append({'country': data['country']})
        else:
            logger.warning("IP not found in GeoIP db.")

    return user
