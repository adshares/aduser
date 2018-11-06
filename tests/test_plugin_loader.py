from unittest import TestCase
from aduser import plugin


class TestInitialize(TestCase):

    def setUp(self):
        plugin.data = None

    def test_incorrect_initialize(self):

        plugin.initialize('fake_doesnt_exist')
        self.assertIsNone(plugin.data)

    def test_correct_initialize(self):
        plugin.initialize('example')
        self.assertIsNotNone(plugin.data)
