from unittest import TestCase
from aduser.simple_provider.server.data_sources.browsecap import BrowsCapSource
import os
from copy import deepcopy


class TestBrowsCapSource(TestCase):

    def setUp(self):

        self.data_path = os.path.join(os.path.dirname(__file__), 'data', 'browscap_14_05_2012.csv')
        self.source = BrowsCapSource(self.data_path)
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
        self.assertGreater(self.user['human_score'], 0.0)
