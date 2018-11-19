import logging

from mock import patch
from twisted.internet import defer
from twisted.trial.unittest import TestCase

from test_server_utils import TestServer

logging.disable(logging.WARNING)
from aduser.plugins import example_maxmind_geoip, example_browscap, simple


class ExampleTestServer(TestServer):
    data_plugin = 'example'


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
        user = example_maxmind_geoip.update_data({'keywords': []},
                                                 {'device': {'ip': '127.0.0.1'}})

        self.assertNotIn('country', user['keywords'].keys())


class SkeletonTestServer(TestServer):
    data_plugin = 'example_skeleton'


class IPapiTestServer(TestServer):
    data_plugin = 'example_ipapi'


class BrowscapTestServer(TestServer):
    data_plugin = 'example_browscap'


class ExtraTestsBrowscap(TestCase):

    def setUp(self):
        example_browscap.browscap = None

    def test_init(self):
        with patch('aduser.plugins.example_browscap.csv_path', 'fake_path'):
            example_browscap.init()
            self.assertIsNone(example_browscap.browscap)

    def test_bad_request(self):
        example_browscap.init()
        # Don't raise an exception
        example_browscap.update_data({}, {})

    def test_bad_ua(self):

        user_agent = 'fake_ua'

        example_browscap.init()
        user = example_browscap.update_data({'human_score': 0.33,
                                             'keywords': []},
                                            {'device': {'ua': user_agent}})

        self.assertEquals(0.33, user['human_score'])

    def test_good_ua(self):

        user_agent = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.10 (maverick) Firefox/3.6.18'

        example_browscap.init()
        user = example_browscap.update_data({'human_score': 0.33,
                                             'keywords': []},
                                            {'device': {'ua': user_agent}})

        self.assertEquals(0.33, user['human_score'])

    def test_bot_ua(self):

        user_agent = 'Google'

        example_browscap.init()
        user = example_browscap.update_data({'human_score': 0.33,
                                             'keywords': []},
                                            {'device': {'ua': user_agent}})

        self.assertEquals(0.0, user['human_score'])


class SimpleTestServer(TestServer):
    data_plugin = 'simple'


class ExtraSimpleTestServer(TestCase):

    def setUp(self):
        simple.browscap = None
        simple.db = None

    def test_init(self):
        with patch('aduser.plugins.simple.csv_path', 'fake_path'):
            with patch('aduser.plugins.simple.mmdb_path', 'fake_path'):
                simple.init()
                self.assertIsNone(simple.browscap)
                self.assertIsNone(simple.db)

    @defer.inlineCallbacks
    def test_bad_ua(self):

        user_agent = 'fake_ua'

        simple.init()
        user = yield simple.update_data({'human_score': 0.33,
                                         'keywords': []},
                                        {'device': {'ua': user_agent,
                                              'ip': '127.0.0.1'}})

        self.assertEquals(0.33, user['human_score'])

    @defer.inlineCallbacks
    def test_good_ua(self):

        user_agent = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.10 (maverick) Firefox/3.6.18'

        simple.init()
        user = yield simple.update_data({'human_score': 0.33,
                                         'keywords': []},
                                        {'device': {'ua': user_agent,
                                              'ip': '127.0.0.1'}})

        self.assertEquals(0.33, user['human_score'])

    @defer.inlineCallbacks
    def test_bot_ua(self):

        user_agent = 'Google'

        simple.init()
        user = yield simple.update_data({'human_score': 0.33,
                                         'keywords': []},
                                        {'device': {'ua': user_agent,
                                              'ip': '127.0.0.1'}})

        self.assertEquals(0.0, user['human_score'])

    @defer.inlineCallbacks
    def test_bad_ip(self):

        user_agent = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.10 (maverick) Firefox/3.6.18'

        simple.init()
        user = yield simple.update_data({'keywords': []},
                                        {'device': {'ua': user_agent,
                                              'ip': '127.0.0.1'}})

        self.assertNotIn('country', user['keywords'].keys())
