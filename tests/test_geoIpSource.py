from unittest import TestCase
from aduser.simple_provider.server.data_sources.maxmind_geoip import GeoIpSource
import os
from copy import deepcopy
import mock


class TestBrowsCapSource(TestCase):

    def setUp(self):

        mock_database = mock.MagicMock()

        def mock_search(ip):
            if ip == '176.221.114.230':
                return {'ip': '176.221.114.230', 'subdivisions': frozenset(['KP']), 'location': (52.5931, 19.0894), 'country': 'PL', 'timezone': 'Europe/Warsaw', 'continent': 'EU'}
            return None

        mock_database.search = mock_search

        self.data_path = os.path.join(os.path.dirname(__file__), '..', 'data', 'GeoLite2-City.mmdb')
        self.source = GeoIpSource(self.data_path)

        self.source.init()

        self.user = {'user_id': None,
                     'request_id': None,
                     'client_ip': None,
                     'cookies': [],
                     'headers': {},
                     'human_score': 1.0,
                     'keywords': {}}

        self.user.update({'client_ip': '176.221.114.230',
                          'headers': {
                              'User-Agent': "Firefox 3.6"}})

    def test_update_user(self):
        old_user = deepcopy(self.user)

        self.source.update_user(self.user)

        self.assertNotEqual(old_user, self.user)

    def test_update_user_localhost(self):
        old_user = deepcopy(self.user)
        self.user['client_ip'] = '127.0.0.1'
        self.source.update_user(self.user)
        self.assertEqual(old_user['keywords'], self.user['keywords'])
