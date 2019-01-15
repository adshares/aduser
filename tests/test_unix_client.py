from mock import Mock, MagicMock

from twisted.trial.unittest import TestCase as TrialTestCase

from unittest import TestCase
from aduser.plugins.unix_client import DataRequestProtocol, DataClientFactory, UnixDataProvider
from twisted.internet import defer


class TestDataRequestProtocol(TestCase):

    def test_dataReceived(self):
        proto = DataRequestProtocol()
        self.assertIsNone(proto.response)

        proto.dataReceived('data')
        self.assertEqual('data', proto.response)

    def test_connectionMade(self):
        proto = DataRequestProtocol()

        proto.transport = MagicMock(return_value=None)
        proto.transport.write = MagicMock(return_value=None)

        proto.factory = Mock()
        proto.factory.data = None

        proto.connectionMade()

        self.assertTrue(proto.transport.write.called)


class TestDataClientFactory(TestCase):

    def test_init(self):
        factory = DataClientFactory(None)
        protocol = factory.buildProtocol(None)
        self.assertTrue(isinstance(protocol, DataRequestProtocol))


class TestUnixDataProvider(TrialTestCase):

    def test_init(self):
        provider = UnixDataProvider(None)
        self.assertIsNotNone(provider.endpoint)

    @defer.inlineCallbacks
    def test_query(self):
        provider = UnixDataProvider('')

        result = yield provider.query(None)
        self.assertIsNone(result)
