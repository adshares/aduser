from unittest import TestCase
from aduser import plugin


class TestInitialize(TestCase):
    def test_initialize(self):

        plugin.initialize('fake_doesnt_exist')
        self.assertIsNone(plugin.data)

        plugin.initialize('example')
        self.assertIsNotNone(plugin.data)
