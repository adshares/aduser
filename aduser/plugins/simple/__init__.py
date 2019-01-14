import logging
import os
from base64 import b64decode

from twisted.internet import defer

from aduser.plugins.simple import taxonomy_utils
from aduser.plugins.unix_client import UnixDataProvider

taxonomy_name = 'simple'
taxonomy_version = '0.0.1'
taxonomy = {'meta': {'name': taxonomy_name,
                     'version': taxonomy_version},
            'data': taxonomy_utils.get_values()}

logger = logging.getLogger(__name__)
PIXEL_GIF = b64decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==")

BROWSCAP_SERVICE_SOCKET = os.getenv('ADUSER_DATA_BROWSCAP_SOCK_FILE', '/tmp/aduser-data-browscap.sock')
GEOLITE_SERVICE_SOCKET = os.getenv('ADUSER_DATA_GEOLITE_SOCK_FILE', '/tmp/aduser-data-geolite.sock')

browscap_provider = UnixDataProvider(BROWSCAP_SERVICE_SOCKET)
geolite_provider = UnixDataProvider(GEOLITE_SERVICE_SOCKET)


def pixel(request):
    request.setHeader(b"content-type", b"image/gif")
    return PIXEL_GIF


@defer.inlineCallbacks
def update_data(user, request_data):

    yield update_data_from_browscap(user, request_data)
    yield update_data_from_geoip(user, request_data)

    defer.returnValue(user)


@defer.inlineCallbacks
def update_data_from_geoip(user, request_data):
    # Request data
    data = yield geolite_provider.query(request_data['device']['ip'])

    if data:
        # Choose data to return
        user['keywords'].update({'country': data['country']})
    else:
        logger.warning("IP not found in GeoIP db.")

    defer.returnValue(user)


@defer.inlineCallbacks
def update_data_from_browscap(user, request_data):
    # Request data
    browser_caps = yield browscap_provider.query(request_data['device']['ua'])

    if browser_caps:
        # Choose data to return
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
