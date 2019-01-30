import json
import tempfile

from mock import Mock, patch
from twisted.internet import defer
from twisted.trial.unittest import TestCase

import aduser_data_services.aduser_data_services.geolite.daemon as daemon


class TestGeoLiteResponseProtocol(TestCase):

    def test_dataReceived(self):
        # Invalid JSON
        with patch('aduser_data_services.aduser_data_services.geolite.daemon.geolite_database', Mock()):
            proto = daemon.GeoLiteResponseProtocol()
            proto.transport = Mock()
            proto.dataReceived('data')
            proto.transport.write.assert_called_with(daemon.GeoLiteResponseProtocol._empty_result)

        magic_geolite = Mock()
        magic_geolite.lookup.return_value = None

        # Valid JSON
        with patch('aduser_data_services.aduser_data_services.geolite.daemon.geolite_database', magic_geolite):
            proto = daemon.GeoLiteResponseProtocol()
            proto.transport = Mock()
            proto.dataReceived('"data"')

            proto.transport.write.assert_called_with(daemon.GeoLiteResponseProtocol._empty_result)

        magic_geolite = Mock()
        magic_geolite.lookup.return_value.to_dict.return_value = {'subdivisions': []}

        with patch('aduser_data_services.aduser_data_services.geolite.daemon.geolite_database', magic_geolite):
            proto = daemon.GeoLiteResponseProtocol()
            proto.transport = Mock()
            proto.dataReceived('"data"')

            proto.transport.write.assert_called_with(json.dumps({'subdivisions': []}))


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

        with patch('aduser_data_services.aduser_data_services.geolite.daemon.SOCK_FILE', temp_socket.name):
            self.endpoint = yield daemon.configure_server()
            self.assertIsNotNone(self.endpoint)
            self.assertTrue(hasattr(self.endpoint, 'stopListening'))


class TestInit(TestCase):

    def test_init_database(self):
        self.assertIsNone(daemon.geolite_database)

        with patch('os.path.exists',
                   Mock(return_value=False)):
            with self.assertRaises(SystemExit):
                daemon.init_database()

        with patch('os.path.exists', Mock(return_value=True)):
            with patch('aduser_data_services.aduser_data_services.geolite.daemon.open_database',
                       Mock(return_value=True)):
                daemon.init_database()
                self.assertIsNotNone(daemon.geolite_database)

            with patch('aduser_data_services.aduser_data_services.geolite.daemon.open_database',
                       Mock(return_value=None)):
                with self.assertRaises(SystemExit):
                    daemon.init_database()
