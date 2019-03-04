import logging
import os
import time
from base64 import b64decode, b64encode
from datetime import datetime
from hashlib import sha1
from random import getrandbits

from aduser.utils import const


def create_tracking_id(request):
    """
    Create the tracking id based on some request data.

    :param request: Instance of `twisted.web.http.Request`.
    :return: Base64 encoded tracking id with checksum.
    """
    logger = logging.getLogger(__name__)
    logger.info("Creating new tracking id.")

    tid_elements = [int(time.time()) * 1000000,                                           # Microsecond epoch time
                    request.getClientIP() if request.getClientIP() else getrandbits(64),  # Client IP
                    None if None else getrandbits(64),                                    # Client port
                    None if None else getrandbits(64),                                    # Client request time (float)
                    os.urandom(22)]                                                       # 22 random bytes

    uid_sha1 = sha1()
    uid_sha1.update(':'.join(map(str, tid_elements)))
    uid = uid_sha1.digest()[:16]

    return b64encode(uid + tracking_id_checksum(uid))


def tracking_id_checksum(uid):
    """

    :param uid: Tracking id.
    :return: Checksum for uid tracking id.
    """
    checksum_sha1 = sha1()
    checksum_sha1.update(uid + const.COOKIE_SECRET)
    return checksum_sha1.digest()[:6]


def is_tracking_id_valid(tid):
    """
    Validates the tracking id with its' checksum.

    :param tid: tid to check
    :return: True or False
    """
    tid = b64decode(tid)
    uid = tid[:16]
    checksum = tid[16:22]

    return tracking_id_checksum(uid) == checksum


def attach_tracking_cookie(request):
    """
    Attach the cookie to request. Create it, if it doesn't exist.

    :param request: Instance of `twisted.web.http.Request` to attach the cookie to.
    :return: Tracking id.
    """
    logger = logging.getLogger(__name__)
    tid = request.getCookie(const.COOKIE_NAME)

    if not (tid and is_tracking_id_valid(tid)):
        logger.warning("Needs new tracking id.")
        tid = create_tracking_id(request)

    logger.info("Updating tracking id.")

    expiry_time = datetime.now() + const.COOKIE_EXPIRY_PERIOD
    expiry_string = 'Date: {0}'.format(expiry_time.strftime("%a, %d %b %Y %H:%M:%S GMT"))

    request.addCookie(const.COOKIE_NAME, tid, expires=expiry_string, path='/')

    return tid
