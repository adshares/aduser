from mock import MagicMock, mock_open, patch
from twisted.internet import defer
from twisted.trial.unittest import TestCase

import aduser
from aduser.data import configure_plugin, demo
from aduser.data.demo import mock_data as demo_mock_data
from test_server_utils import TestServer


class PluginTestCase(TestCase):
    data_plugin = 'aduser.data.examples.example'

    def setUp(self):
        with patch('aduser.data.const.DATA_PROVIDER', self.data_plugin):
            configure_plugin()

    def tearDown(self):
        aduser.data.provider = None

    def test_pixel(self):
        req = MagicMock()

        pixel = aduser.data.provider.pixel(req)
        self.assertIsNotNone(pixel)
        req.setHeader.assert_called_with(b"content-type", b"image/gif")

    def test_update_data(self):
        user_data = {'keywords': {}}
        request_data = {'device': {"ua": "",
                                   "ip": ""}}

        data = aduser.data.provider.update_data(user_data, request_data)
        self.assertIsNotNone(data)


class ExampleBrowscapTestServer(PluginTestCase):
    data_plugin = 'aduser.data.examples.browscap'


class ExampleExampleTestServer(PluginTestCase):
    data_plugin = 'aduser.data.examples.example'


class ExampleIPapiTestServer(TestServer):
    # This uses TestServer (reactor)
    data_plugin = 'aduser.data.examples.ipapi'


class ExampleMaxmindTestServer(PluginTestCase):
    data_plugin = 'aduser.data.examples.maxmind_geoip'


class SkeletonTestServer(PluginTestCase):
    data_plugin = 'aduser.data.skeleton'


class SimpleTestServer(PluginTestCase):
    data_plugin = 'aduser.data.simple'


class DemoTestServer(PluginTestCase):
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
