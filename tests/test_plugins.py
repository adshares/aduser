import logging
import os

from mock import mock_open, patch
from twisted.internet import defer
from twisted.trial.unittest import TestCase

from test_server_utils import TestServer

logging.disable(logging.WARNING)
from aduser.plugins import simple, demo
from aduser.plugins.examples import browscap, maxmind_geoip
from aduser.plugins.demo.utils import mock_data as demo_mock_data

browscap_test_path = os.path.abspath('.venv/lib/python2.7/site-packages/pybrowscap/test/data/browscap_14_05_2012.csv')


class ExampleTestServer(TestServer):
    data_plugin = 'aduser.plugins.examples.example'


class MaxmindTestServer(TestServer):
    data_plugin = 'aduser.plugins.examples.maxmind_geoip'


class ExtraTestsMaxmind(TestCase):

    def setUp(self):
        maxmind_geoip.db = None

    def test_init(self):
        with patch('aduser.plugins.examples.maxmind_geoip.mmdb_path', 'fake_path'):
            maxmind_geoip.init()
            self.assertIsNone(maxmind_geoip.db)

    def test_bad_ip(self):
        maxmind_geoip.init()
        user = maxmind_geoip.update_data({'keywords': {}},
                                         {'device': {'ip': '127.0.0.1'}})

        self.assertNotIn('country', [i.keys() for i in user['keywords']])


class SkeletonTestServer(TestServer):
    data_plugin = 'aduser.plugins.skeleton'


class IPapiTestServer(TestServer):
    data_plugin = 'aduser.plugins.examples.ipapi'


class BrowscapTestServer(TestServer):
    data_plugin = 'aduser.plugins.examples.browscap'


class ExtraTestsBrowscap(TestCase):

    def setUp(self):
        browscap.browscap = None

    def test_init(self):
        with patch('aduser.plugins.examples.browscap.csv_path', 'fake_path'):
            browscap.init()
            self.assertIsNone(browscap.browscap)

        with patch('aduser.plugins.examples.browscap.csv_path', browscap_test_path):
            browscap.init()
            self.assertIsNotNone(browscap.browscap)

    def test_bad_request(self):
        browscap.init()
        # Don't raise an exception
        browscap.update_data({}, {})

    def test_bad_ua(self):

        user_agent = 'fake_ua'

        browscap.init()
        user = browscap.update_data({'human_score': 0.33,
                                     'keywords': {}},
                                    {'device': {'ua': user_agent}})

        self.assertEquals(0.33, user['human_score'])

    def test_good_ua(self):
        user_agent = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.10 (maverick) Firefox/3.6.18'

        with patch('aduser.plugins.examples.browscap.csv_path', browscap_test_path):
            browscap.init()
            self.assertIsNotNone(browscap.browscap)
            user = browscap.update_data({'human_score': 0.33,
                                         'keywords': {}},
                                        {'device': {'ua': user_agent}})

        self.assertEquals(0.33, user['human_score'])

    def test_bot_ua(self):

        user_agent = 'Google'

        with patch('aduser.plugins.examples.browscap.csv_path', browscap_test_path):
            browscap.init()
            self.assertIsNotNone(browscap.browscap)
            user = browscap.update_data({'human_score': 0.33,
                                         'keywords': {}},
                                        {'device': {'ua': user_agent}})

        self.assertEquals(0.0, user['human_score'])


class SimpleTestServer(TestServer):
    data_plugin = 'aduser.plugins.simple'


class ExtraSimpleTestServer(TestCase):
    data_plugin = simple

    def setUp(self):
        self.data_plugin.browscap = None
        self.data_plugin.db = None
        self.data_plugin.csv_path = browscap_test_path

    def test_fake_init(self):
        with patch('aduser.plugins.simple.csv_path', 'fake_path'):
            with patch('aduser.plugins.simple.mmdb_path', 'fake_path'):
                self.data_plugin.init()
                self.assertIsNone(self.data_plugin.browscap)
                self.assertIsNone(self.data_plugin.db)

    @defer.inlineCallbacks
    def test_bad_ua(self):

        user_agent = 'fake_ua'

        self.data_plugin.init()
        user = yield self.data_plugin.update_data({'human_score': 0.33,
                                                   'keywords': {}},
                                                  {'device': {'ua': user_agent,
                                                              'ip': '127.0.0.1'}})

        self.assertEquals(0.33, user['human_score'])

    @defer.inlineCallbacks
    def test_good_ua(self):

        user_agent = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.10 (maverick) Firefox/3.6.18'

        self.data_plugin.init()
        user = yield self.data_plugin.update_data({'human_score': 0.33,
                                                   'keywords': {}},
                                                  {'device': {'ua': user_agent,
                                                              'ip': '127.0.0.1'}})

        self.assertEquals(0.33, user['human_score'])

    @defer.inlineCallbacks
    def test_bot_ua(self):

        user_agent = 'Google'

        self.data_plugin.init()
        user = yield self.data_plugin.update_data({'human_score': 0.33,
                                                   'keywords': {}},
                                                  {'device': {'ua': user_agent,
                                                              'ip': '127.0.0.1'}})

        self.assertEquals(0.0, user['human_score'])

    @defer.inlineCallbacks
    def test_bad_ip(self):

        user_agent = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.10 (maverick) Firefox/3.6.18'

        self.data_plugin.init()
        user = yield self.data_plugin.update_data({'keywords': {}},
                                                  {'device': {'ua': user_agent,
                                                              'ip': '127.0.0.1'}})

        self.assertNotIn('country', [i for i in user['keywords']])


class DemoTestServer(SimpleTestServer):
    data_plugin = 'aduser.plugins.demo'


class ExtraDemoTestServer(ExtraSimpleTestServer):
    data_plugin = demo

    def setUp(self):
        simple.browscap = None
        simple.db = None
        simple.csv_path = browscap_test_path

    @defer.inlineCallbacks
    def test_mock_data(self):
        self.data_plugin.init()
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
