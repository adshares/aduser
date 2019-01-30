from mock import patch
from twisted.trial.unittest import TestCase

import aduser
from aduser.data import configure_plugin


class TestInitialize(TestCase):

    def setUp(self):
        aduser.data.provider = None

    def test_correct_initialize(self):
        with patch('aduser.data.const.DATA_PROVIDER', 'aduser.data.examples.example'):
            configure_plugin()
            self.assertIsNotNone(aduser.data.provider)

    def test_incorrect_initialize(self):
        with patch('aduser.data.const.DATA_PROVIDER', 'fake_path'):
            with self.assertRaises(ImportError):
                configure_plugin()
