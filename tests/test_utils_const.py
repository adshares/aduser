import os
from datetime import timedelta

from twisted.trial.unittest import TestCase

from aduser.utils import const as utils_const


class TestRequest(TestCase):

    def test_const(self):

        for c in dir(utils_const):
            self.assertIsNotNone(c)

    def test_expiry_period(self):

        for env in ['4w', '7d', 'invalid_period']:
            os.environ['ADUSER_COOKIE_EXPIRY_PERIOD'] = env
            reload(utils_const)

            self.assertGreater(utils_const.COOKIE_EXPIRY_PERIOD, timedelta(seconds=0))
