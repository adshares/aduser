from twisted.trial.unittest import TestCase
from aduser.simple_provider.server import const


class TestRequest(TestCase):

    def test_const(self):
        for c in dir(const):

            self.assertIsNotNone(c)
