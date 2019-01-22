from mock import Mock, MagicMock

from twisted.trial.unittest import TestCase as TrialTestCase

from unittest import TestCase
from aduser.data import JSONProtocol, DataClientFactory, UnixDataProvider
from twisted.internet import defer


class TestJSONProtocol(TestCase):

    @defer.inlineCallbacks
    def test_dataReceived(self):
        proto = JSONProtocol()
        proto.dataReceived('"data"')
        response_data = yield proto.dataDeferred
        self.assertEqual('data', response_data)

    def test_connectionMade(self):
        proto = JSONProtocol()

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
        self.assertTrue(isinstance(protocol, JSONProtocol))


class TestUnixDataProvider(TrialTestCase):

    def test_init(self):
        provider = UnixDataProvider(None)
        self.assertIsNotNone(provider.endpoint)

    @defer.inlineCallbacks
    def test_query(self):
        provider = UnixDataProvider('')

        result = yield provider.query(None)
        self.assertIsNone(result)

        proto = JSONProtocol()
        proto.dataReceived('"data"')

        provider.endpoint.connect = Mock()
        provider.endpoint.connect.return_value = proto

        result = yield provider.query(None)

        self.assertIsNotNone(result)
        self.assertEqual('data', result)
