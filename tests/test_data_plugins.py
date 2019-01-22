from mock import mock_open, patch
from twisted.internet import defer
from twisted.trial.unittest import TestCase

from aduser.data import demo
from aduser.data.demo import mock_data as demo_mock_data
from test_server_utils import TestServer


class ExampleBrowscapTestServer(TestServer):
    data_plugin = 'aduser.data.examples.browscap'


class ExampleExampleTestServer(TestServer):
    data_plugin = 'aduser.data.examples.example'


class ExampleIPapiTestServer(TestServer):
    data_plugin = 'aduser.data.examples.ipapi'


class ExampleMaxmindTestServer(TestServer):
    data_plugin = 'aduser.data.examples.maxmind_geoip'


class SkeletonTestServer(TestServer):
    data_plugin = 'aduser.data.skeleton'


class SimpleTestServer(TestServer):
    data_plugin = 'aduser.data.simple'


class DemoTestServer(SimpleTestServer):
    data_plugin = 'aduser.data.demo'


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
