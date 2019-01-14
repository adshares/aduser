import logging
from base64 import b64decode

from aduser.plugins.examples.maxmind_geoip import const, utils

db = None
mmdb_path = const.GEOLITE_PATH

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


def update_data(user, request_data):
    global db

    if db:
        data = db.get_info(request_data['device']['ip'])
        if data:
            user['keywords'].update({'country': data['country']})
        else:
            logger.warning("IP not found in GeoIP db.")

    return user
