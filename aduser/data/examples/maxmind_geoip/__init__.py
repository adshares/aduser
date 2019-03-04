import logging
import os
from base64 import b64decode

from twisted.internet import defer

from aduser.data import UnixDataProvider
from aduser.data import const as data_const

taxonomy_name = 'examples.maxmind_geoip'
taxonomy_version = '0.0.1'
taxonomy = {'meta': {'name': taxonomy_name,
                     'version': taxonomy_version},
            'data': [{'label': 'Country',
                      "key": "country",
                      'type': 'input'}]}

logger = logging.getLogger(__name__)
PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")

GEOLITE_SERVICE_SOCKET = os.getenv('ADUSER_DATA_GEOLITE_SOCK_FILE', '/tmp/aduser-data-geolite.sock')

geolite_provider = UnixDataProvider(GEOLITE_SERVICE_SOCKET)


def score(tracking_id, request):
    return None


def score_data(tracking_id, token, request):
    return data_const.DEFAULT_HUMAN_SCORE


def pixel(tracking_id, request):
    request.setHeader(b"content-type", b"image/gif")
    return PIXEL_GIF


@defer.inlineCallbacks
def update_data(user, request_data):
    # Request data
    data = yield geolite_provider.query(request_data['device']['ip'])
    if data:
        # Choose data to return
        user['keywords'].update({'country': data['country']})
    else:
        logger.warning("IP not found in GeoIP db.")

    defer.returnValue(user)
