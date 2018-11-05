from twisted.trial.unittest import TestCase
from datetime import timedelta
import os
import aduser.const
import imp


class TestRequest(TestCase):

    def test_const(self):

        for c in dir(aduser.const):
            self.assertIsNotNone(c)

    def test_expiry_period(self):

        for env in ['4w', '7d', 'invalid_period']:
            os.environ['ADUSER_EXPIRY_PERIOD'] = env
            reload(aduser.const)

            self.assertGreater(aduser.const.EXPIRY_PERIOD, timedelta(seconds=0))
