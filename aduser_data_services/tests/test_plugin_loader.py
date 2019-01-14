from unittest import TestCase

from mock import patch

from aduser import plugin


class TestInitialize(TestCase):

    def setUp(self):
        plugin.data = None

    def test_incorrect_initialize(self):
        with patch('aduser.const.DATA_PROVIDER', 'fake_path'):
            with self.assertRaises(ImportError):
                plugin.initialize()

    def test_correct_initialize(self):
        with patch('aduser.const.DATA_PROVIDER', 'aduser.plugins.examples.example'):
            plugin.initialize()
            self.assertIsNotNone(plugin.data)
