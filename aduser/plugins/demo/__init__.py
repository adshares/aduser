import logging
import random

from twisted.internet import defer

import aduser.plugins.simple as simple_plugin
from aduser.plugins.demo import mock_data

taxonomy_name = 'demo'
taxonomy_version = '0.0.1'
taxonomy = {'meta': {'name': taxonomy_name,
                     'version': taxonomy_version},
            'data': [mock_data.mock] + simple_plugin.taxonomy_utils.get_values()}

logger = logging.getLogger(__name__)

pixel = simple_plugin.pixel


@defer.inlineCallbacks
def update_data(user, request_data):
    yield simple_plugin.update_data_from_browscap(user, request_data)
    yield simple_plugin.update_data_from_geoip(user, request_data)

    update_mock_data(user, request_data)

    defer.returnValue(user)


def update_mock_data(user, request_data):
    # Ignore request data

    # Mock determinate behaviour for mock data
    if 'interest' not in user['keywords']:
        user['keywords'].update({'interest': random.choice(mock_data.mock['data'])['key']})

    return user
