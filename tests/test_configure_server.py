from unittest import TestCase
from aduser import server_utils
from mock import patch


class TestConfigure_server(TestCase):

    port = None

    def tearDown(self):
        if self.port:
            self.port.stopListening()

    def test_configure_server(self):
        self.port = server_utils.configure_server()
        self.assertIsNotNone(self.port)

    def test_bad_plugin(self):
        with self.assertRaises(SystemExit):
            with patch('aduser.const.ADUSER_DATA_PROVIDER', 'fake_module'):
                self.port = server_utils.configure_server()
