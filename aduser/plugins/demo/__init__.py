import logging
import random

from twisted.internet import defer

from aduser.plugins.demo import const
from aduser.plugins.demo.utils import mock_data
from aduser.plugins.simple.utils import browscap_utils, geoip_utils, taxonomy_utils
from aduser.plugins.simple import PIXEL_GIF, PIXEL_PNG, pixel, init, update_data_from_browscap, update_data_from_geoip
from aduser.plugins.simple import init as simple_init
from aduser.plugins.simple import db, mmdb_path, browscap, csv_path


taxonomy_name = 'demo'
taxonomy_version = '0.0.1'
taxonomy = {'meta': {'name': taxonomy_name,
                     'version': taxonomy_version},
            'data': [mock_data.mock] + taxonomy_utils.get_values()}

logger = logging.getLogger(__name__)


@defer.inlineCallbacks
def update_data(user, request_data):
    yield update_data_from_browscap(user, request_data)
    yield update_data_from_geoip(user, request_data)

    if 'interest' not in user['keywords']:
        update_mock_data(user, request_data)
    defer.returnValue(user)


def update_mock_data(user, request_data):
    return user['keywords'].update({'interest': random.choice(mock_data.mock['data'])['key']})
