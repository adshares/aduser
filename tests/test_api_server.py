from unittest import TestCase

from mock import patch

from aduser import server_utils


class TestConfigureServer(TestCase):

    port = None

    def tearDown(self):
        if self.port:
            self.port.stopListening()

    def test_configure_server(self):
        self.port = server_utils.configure_server()
        self.assertIsNotNone(self.port)

    def test_bad_plugin(self):
        with self.assertRaises(ImportError):
            with patch('aduser.const.DATA_PROVIDER', 'fake_module'):
                self.port = server_utils.configure_server()
