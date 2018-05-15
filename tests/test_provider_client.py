from twisted.trial.unittest import TestCase
from aduser.simple_provider.client import SimpleProviderClient
from twisted.web.http import Request
from mock import MagicMock, patch
from twisted.internet import defer
import aduser


class TestRequest(TestCase):

    proxy_const = MagicMock()
    proxy_const.DATA_PROVIDER_CLIENT = MagicMock()
    proxy_const.pixel = MagicMock()

    def setUp(self):  # NOSONAR
        self.client = SimpleProviderClient()

    def tearDown(self):  # NOSONAR
        self.client = None

    @defer.inlineCallbacks
    def test_pixel(self):
        req = Request(channel=MagicMock())
        req.setResponseCode = MagicMock()

        response = yield self.client.pixel(req)
        self.assertIsNotNone(response)
        req.setResponseCode.assert_called_with(302)

    @defer.inlineCallbacks
    @patch('aduser.simple_provider.client.Agent', autospec=True)
    def test_get_data(self, mock_agent):

        aduser.simple_provider.client.Agent = mock_agent

        req = Request(channel=MagicMock())
        req.setResponseCode = MagicMock()

        response = yield self.client.get_data(req)
        self.assertIsNotNone(response)

    @defer.inlineCallbacks
    def test_get_schema(self):
        exp = self.client.get_schema()

        self.assertEqual(NotImplemented, exp)
        yield ''
