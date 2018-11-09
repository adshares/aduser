from twisted.trial.unittest import TestCase
from test_server_utils import TestServer
from mock import patch
from aduser.plugins import example_maxmind_geoip


class MaxmindTestServer(TestServer):
    data_plugin = 'example_maxmind_geoip'


class ExtraTestsMaxmind(TestCase):

    def setUp(self):
        example_maxmind_geoip.db = None

    def test_init(self):
        with patch('aduser.plugins.example_maxmind_geoip.mmdb_path', 'fake_path'):
            example_maxmind_geoip.init()
            self.assertIsNone(example_maxmind_geoip.db)

    def test_bad_ip(self):

        example_maxmind_geoip.init()
        user = example_maxmind_geoip.update_data({'keywords': {}},
                                                 {'device': {'ip': '127.0.0.1'}})

        self.assertNotIn('country', user['keywords'].keys())


class SkeletonTestServer(TestServer):
    data_plugin = 'example_skeleton'


class IPapiTestServer(TestServer):
    data_plugin = 'example_ipapi'


class BrowscapTestServer(TestServer):
    data_plugin = 'example_browscap'
