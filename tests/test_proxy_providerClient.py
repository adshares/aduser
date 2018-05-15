from unittest import TestCase
from aduser.proxy.client import ProviderClient


class TestProviderClient(TestCase):

    def setUp(self):   # NOSONAR
        self.client = ProviderClient()

    def test_pixel(self):
        self.assertEqual(NotImplemented, self.client.pixel())

    def test_get_data(self):
        self.assertEqual(NotImplemented, self.client.get_data(1))

    def test_get_schema(self):
        self.assertEqual(NotImplemented, self.client.get_schema())
