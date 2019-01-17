import json
from unittest import TestCase

from mock import MagicMock, patch
from twisted.internet import defer

from aduser.api.v1.server import DataResource
from aduser.db import utils as db_utils


class TestDataResource(TestCase):

    @defer.inlineCallbacks
    def test_handle_data_without_cache(self):
        # TODO
        # Add user to database
        db_utils.update_mapping({'tracking_id': 'tid',
                                 'server_user_id': 'adserver_user'})

        with patch('aduser.const.DEBUG_WITHOUT_CACHE', 1):
            request_data = json.dumps({'uid': "0_111",
                                       'domain': 'http://example.com',
                                       'ua': '',
                                       'ip': '212.212.22.1'})

            request = MagicMock()
            request.write = MagicMock()
            request.content.read.return_value = request_data[:5]

            dr = DataResource()
            yield dr
