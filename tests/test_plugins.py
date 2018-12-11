import logging

from mock import patch
from twisted.internet import defer
from twisted.trial.unittest import TestCase

from test_server_utils import TestServer

logging.disable(logging.WARNING)
from aduser.plugins import simple
from aduser.plugins.examples import browscap, maxmind_geoip


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

        browscap.init()
        user = browscap.update_data({'human_score': 0.33,
                                     'keywords': {}},
                                    {'device': {'ua': user_agent}})

        self.assertEquals(0.33, user['human_score'])

    def test_bot_ua(self):

        user_agent = 'Google'

        browscap.init()
        user = browscap.update_data({'human_score': 0.33,
                                     'keywords': {}},
                                    {'device': {'ua': user_agent}})

        self.assertEquals(0.0, user['human_score'])


class SimpleTestServer(TestServer):
    data_plugin = 'aduser.plugins.simple'


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
                                         'keywords': {}},
                                        {'device': {'ua': user_agent,
                                                    'ip': '127.0.0.1'}})

        self.assertEquals(0.33, user['human_score'])

    @defer.inlineCallbacks
    def test_good_ua(self):

        user_agent = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.10 (maverick) Firefox/3.6.18'

        simple.init()
        user = yield simple.update_data({'human_score': 0.33,
                                         'keywords': {}},
                                        {'device': {'ua': user_agent,
                                                    'ip': '127.0.0.1'}})

        self.assertEquals(0.33, user['human_score'])

    @defer.inlineCallbacks
    def test_bot_ua(self):

        user_agent = 'Google'

        simple.init()
        user = yield simple.update_data({'human_score': 0.33,
                                         'keywords': {}},
                                        {'device': {'ua': user_agent,
                                                    'ip': '127.0.0.1'}})

        self.assertEquals(0.0, user['human_score'])

    @defer.inlineCallbacks
    def test_bad_ip(self):

        user_agent = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.10 (maverick) Firefox/3.6.18'

        simple.init()
        user = yield simple.update_data({'keywords': {}},
                                        {'device': {'ua': user_agent,
                                                    'ip': '127.0.0.1'}})

        self.assertNotIn('country', [i for i in user['keywords']])
