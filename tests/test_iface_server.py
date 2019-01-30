from unittest import TestCase

from aduser.iface import server as iface_server


class TestConfigureServer(TestCase):

    port = None

    def tearDown(self):
        if self.port:
            self.port.stopListening()

    def test_configure_server(self):
        self.port = iface_server.configure_server()
        self.assertIsNotNone(self.port)
