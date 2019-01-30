import json
import tempfile

from mock import Mock, patch
from twisted.internet import defer
from twisted.trial.unittest import TestCase

import aduser_data_services.aduser_data_services.browscap.daemon as daemon


class TestBrowscapResponseProtocol(TestCase):

    def test_dataReceived(self):
        # Invalid JSON
        with patch('aduser_data_services.aduser_data_services.browscap.daemon.browscap_database', Mock()):
            proto = daemon.BrowscapResponseProtocol()
            proto.transport = Mock()
            proto.dataReceived('data')
            proto.transport.write.assert_called_with(daemon.BrowscapResponseProtocol._empty_result)

        mock_browscap = Mock()
        mock_browscap.search.return_value = None

        # Valid JSON
        with patch('aduser_data_services.aduser_data_services.browscap.daemon.browscap_database', mock_browscap):
            proto = daemon.BrowscapResponseProtocol()
            proto.transport = Mock()
            proto.dataReceived('"data"')

            proto.transport.write.assert_called_with(daemon.BrowscapResponseProtocol._empty_result)

        mock_browscap = Mock()
        mock_browscap.search.return_value.items.return_value = {'browser': 'fake'}

        with patch('aduser_data_services.aduser_data_services.browscap.daemon.browscap_database', mock_browscap):
            proto = daemon.BrowscapResponseProtocol()
            proto.transport = Mock()
            proto.dataReceived('"data"')

            proto.transport.write.assert_called_with(json.dumps({'browser': 'fake'}))


class TestServer(TestCase):

    def setUp(self):
        self.endpoint = None

    def tearDown(self):
        if self.endpoint:
            self.endpoint.stopListening()

    @defer.inlineCallbacks
    def test_configure_server(self):
        temp_socket = tempfile.TemporaryFile()
        temp_socket.close()

        with patch('aduser_data_services.aduser_data_services.browscap.daemon.SOCK_FILE', temp_socket.name):
            self.endpoint = yield daemon.configure_server()
            self.assertIsNotNone(self.endpoint)
            self.assertTrue(hasattr(self.endpoint, 'stopListening'))


class TestInit(TestCase):

    def test_init_database(self):
        self.assertIsNone(daemon.browscap_database)

        with patch('os.path.exists',
                   Mock(return_value=False)):
            with self.assertRaises(SystemExit):
                daemon.init_database()

        with patch('os.path.exists',
                   Mock(return_value=True)):
            with patch('aduser_data_services.aduser_data_services.browscap.daemon.load_file',
                       Mock(return_value=True)):
                daemon.init_database()
                self.assertIsNotNone(daemon.browscap_database)

            with patch('aduser_data_services.aduser_data_services.browscap.daemon.load_file',
                       Mock(return_value=None)):
                with self.assertRaises(SystemExit):
                    daemon.init_database()
