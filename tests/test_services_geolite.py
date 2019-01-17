import json
from unittest import TestCase

from mock import patch, MagicMock

from aduser_data_services.aduser_data_services.geolite.daemon import GeoLiteResponseProtocol


class TestGeoLiteResponseProtocol(TestCase):

    def test_dataReceived(self):
        # Invalid JSON
        with patch('aduser_data_services.aduser_data_services.geolite.daemon.geolite_database', MagicMock()):
            proto = GeoLiteResponseProtocol()
            proto.transport = MagicMock()
            proto.dataReceived('data')
            proto.transport.write.assert_called_with(GeoLiteResponseProtocol._empty_result)

        magic_geolite = MagicMock()
        magic_geolite.lookup.return_value = None

        # Valid JSON
        with patch('aduser_data_services.aduser_data_services.geolite.daemon.geolite_database', magic_geolite):
            proto = GeoLiteResponseProtocol()
            proto.transport = MagicMock()
            proto.dataReceived('"data"')

            proto.transport.write.assert_called_with(GeoLiteResponseProtocol._empty_result)

        magic_geolite = MagicMock()
        magic_geolite.lookup.return_value.to_dict.return_value = {'subdivisions': []}

        with patch('aduser_data_services.aduser_data_services.geolite.daemon.geolite_database', magic_geolite):
            proto = GeoLiteResponseProtocol()
            proto.transport = MagicMock()
            proto.dataReceived('"data"')

            proto.transport.write.assert_called_with(json.dumps({'subdivisions': []}))
