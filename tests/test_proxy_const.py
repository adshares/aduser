from twisted.trial.unittest import TestCase
from aduser.proxy import const
from datetime import timedelta


class TestRequest(TestCase):

    def test_const(self):
        for c in dir(const):

            self.assertIsNotNone(c)

        self.assertGreater(const.EXPIRY_PERIOD, timedelta(seconds=0))
