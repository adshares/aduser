from mock import mock_open, patch
from twisted.internet import defer
from twisted.trial.unittest import TestCase

from aduser.plugins import demo
from aduser.plugins.demo import mock_data as demo_mock_data
from test_server_utils import TestServer


class ExampleTestServer(TestServer):
    data_plugin = 'aduser.plugins.examples.example'


class MaxmindTestServer(TestServer):
    data_plugin = 'aduser.plugins.examples.maxmind_geoip'


class SkeletonTestServer(TestServer):
    data_plugin = 'aduser.plugins.skeleton'


class IPapiTestServer(TestServer):
    data_plugin = 'aduser.plugins.examples.ipapi'


class BrowscapTestServer(TestServer):
    data_plugin = 'aduser.plugins.examples.browscap'


class SimpleTestServer(TestServer):
    data_plugin = 'aduser.plugins.simple'


class DemoTestServer(SimpleTestServer):
    data_plugin = 'aduser.plugins.demo'


class ExtraDemoTestServer(TestCase):
    data_plugin = demo

    @defer.inlineCallbacks
    def test_mock_data(self):
        user = yield self.data_plugin.update_data({'human_score': 0.33,
                                                   'keywords': {}},
                                                  {'device': {'ua': '',
                                                              'ip': '127.0.0.1'}})

        self.assertIn('interest', user['keywords'].keys())
        self.assertIn(user['keywords']['interest'], ["1", "2"])

    def test_mock_file(self):
        with patch('__builtin__.open', mock_open(read_data='{}')) as m:
            demo_mock_data.init('fake_path')

        self.assertIsNotNone(demo_mock_data.mock)
        self.assertEqual({}, demo_mock_data.mock)

    def test_notfound_mock_file(self):
        demo_mock_data.init('fake_path')

        self.assertIsNotNone(demo_mock_data.mock)
        self.assertEqual(demo_mock_data.default_mock, demo_mock_data.mock)

    def test_empty_mock_file(self):
        demo_mock_data.init()

        self.assertIsNotNone(demo_mock_data.mock)
        self.assertEqual(demo_mock_data.default_mock, demo_mock_data.mock)

        demo_mock_data.init(None)

        self.assertIsNotNone(demo_mock_data.mock)
        self.assertEqual(demo_mock_data.default_mock, demo_mock_data.mock)
