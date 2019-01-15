import logging
from base64 import b64decode, b64encode
from hashlib import sha1

from mock import MagicMock
from twisted.trial import unittest

from aduser import utils
from aduser.utils import is_tracking_id_valid, tracking_id_checksum

logging.disable(logging.WARNING)


class TestTrackingId(unittest.TestCase):

    def test_create_tracking_id(self):

        raised = False
        try:
            utils.create_tracking_id(MagicMock())
        except TypeError:
            raised = True

        self.assertFalse(raised)

        raised = False
        try:
            ret = utils.create_tracking_id(MagicMock())
            b64decode(ret)
        except TypeError:
            raised = True

        self.assertFalse(raised)

    def test_is_tracking_id_valid(self):
        uid_sha1 = sha1()
        uid_sha1.update('')
        uid = uid_sha1.digest()[:16]

        tid = b64encode(uid + tracking_id_checksum(uid))

        self.assertTrue(is_tracking_id_valid(tid))

    def test_tracking_id_checksum(self):

        self.assertEqual(6, len(tracking_id_checksum('fake_uid')))
