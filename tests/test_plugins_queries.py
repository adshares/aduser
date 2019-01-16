from mock import MagicMock, patch
from twisted.internet import defer
from twisted.trial.unittest import TestCase as TrialTestCase

from aduser.plugins.examples.browscap import update_data as browscap_update_data
from aduser.plugins.examples.maxmind_geoip import update_data as geolite_update_data
from aduser.plugins.simple import update_data as simple_update_data


class TestPluginQueries(TrialTestCase):

    @defer.inlineCallbacks
    def test_simple(self):

        user = {'keywords': {}}
        request_data = {'device': {'ua': '',
                                   'ip': '127.0.0.1'}}

        data = yield simple_update_data(user, request_data)
        self.assertIsNotNone(data)
        self.assertEqual(user, data)

        geolite_mock_return = {'country': 'DE'}

        with patch('aduser.plugins.simple.geolite_provider.query',
                   MagicMock(return_value=geolite_mock_return)):

            yield simple_update_data(user, request_data)
            self.assertEqual(user['keywords'], {'country': 'DE'})

        browscap_mock_return = MagicMock()
        browscap_mock_return.is_crawler.return_value = True

        with patch('aduser.plugins.simple.browscap_provider.query',
                   MagicMock(return_value=browscap_mock_return)):

            yield simple_update_data(user, request_data)
            self.assertEqual(user['human_score'], 0.0)

        browscap_mock_return.is_crawler.return_value = False

        with patch('aduser.plugins.simple.browscap_provider.query',
                   MagicMock(return_value=browscap_mock_return)):

            yield simple_update_data(user, request_data)
            self.assertEqual(user['human_score'], 1.0)

    @defer.inlineCallbacks
    def test_examples_browscap(self):
        user = {'keywords': {}}
        request_data = {'device': {'ua': '',
                                   'ip': '127.0.0.1'}}

        data = yield browscap_update_data(user, request_data)
        self.assertIsNotNone(data)
        self.assertEqual(user, data)

        browscap_mock_return = MagicMock()
        browscap_mock_return.is_crawler.return_value = True

        with patch('aduser.plugins.examples.browscap.browscap_provider.query',
                   MagicMock(return_value=browscap_mock_return)):
            yield browscap_update_data(user, request_data)
            self.assertEqual(user['human_score'], 0.0)

        browscap_mock_return.is_crawler.return_value = False

        with patch('aduser.plugins.examples.browscap.browscap_provider.query',
                   MagicMock(return_value=browscap_mock_return)):
            yield browscap_update_data(user, request_data)
            self.assertEqual(user['human_score'], 1.0)

    @defer.inlineCallbacks
    def test_examples_geolite(self):
        user = {'keywords': {}}
        request_data = {'device': {'ua': '',
                                   'ip': '127.0.0.1'}}

        data = yield simple_update_data(user, request_data)
        self.assertIsNotNone(data)
        self.assertEqual(user, data)

        geolite_mock_return = {'country': 'DE'}

        with patch('aduser.plugins.examples.maxmind_geoip.geolite_provider.query',
                   MagicMock(return_value=geolite_mock_return)):
            yield geolite_update_data(user, request_data)
            self.assertEqual(user['keywords'], {'country': 'DE'})