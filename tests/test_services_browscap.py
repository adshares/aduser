import json
from unittest import TestCase

from mock import patch, MagicMock

from aduser_data_services.aduser_data_services.browscap.daemon import BrowscapResponseProtocol


class TestBrowscapResponseProtocol(TestCase):

    def test_dataReceived(self):
        # Invalid JSON
        with patch('aduser_data_services.aduser_data_services.browscap.daemon.browscap_database', MagicMock()):
            proto = BrowscapResponseProtocol()
            proto.transport = MagicMock()
            proto.dataReceived('data')
            proto.transport.write.assert_called_with(BrowscapResponseProtocol._empty_result)

        mock_browscap = MagicMock()
        mock_browscap.search.return_value = None

        # Valid JSON
        with patch('aduser_data_services.aduser_data_services.browscap.daemon.browscap_database', mock_browscap):
            proto = BrowscapResponseProtocol()
            proto.transport = MagicMock()
            proto.dataReceived('"data"')

            proto.transport.write.assert_called_with(BrowscapResponseProtocol._empty_result)

        mock_browscap = MagicMock()
        mock_browscap.search.return_value.items.return_value = {'browser': 'fake'}

        with patch('aduser_data_services.aduser_data_services.browscap.daemon.browscap_database', mock_browscap):
            proto = BrowscapResponseProtocol()
            proto.transport = MagicMock()
            proto.dataReceived('"data"')

            proto.transport.write.assert_called_with(json.dumps({'browser': 'fake'}))
