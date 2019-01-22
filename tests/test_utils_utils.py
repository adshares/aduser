import logging
from base64 import b64decode, b64encode
from hashlib import sha1

from mock import MagicMock
from twisted.trial import unittest

from aduser.utils import const, utils

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

        tid = b64encode(uid + utils.tracking_id_checksum(uid))

        self.assertTrue(utils.is_tracking_id_valid(tid))

    def test_tracking_id_checksum(self):

        self.assertEqual(6, len(utils.tracking_id_checksum('fake_uid')))

    def test_attach_tracking_cookie(self):

        # Unknown user
        request = MagicMock()

        # No cookie found
        request.getCookie.return_value = None

        tid = utils.attach_tracking_cookie(request)
        self.assertIsNotNone(tid)

        # Make sure adding cookie worked
        self.assertTrue(request.addCookie.called)
        self.assertTrue(request.addCookie.called_with(const.COOKIE_NAME, tid))

        # Known user
        request = MagicMock()

        # No cookie found
        request.getCookie.return_value = tid

        new_tid = utils.attach_tracking_cookie(request)
        self.assertIsNotNone(new_tid)
        # tid and new_tid should be the same
        self.assertEqual(tid, new_tid)

        # Make sure adding cookie worked
        self.assertTrue(request.addCookie.called)
        self.assertTrue(request.addCookie.called_with(const.COOKIE_NAME, new_tid))
