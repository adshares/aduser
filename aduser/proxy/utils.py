import time
import os
import logging

from hashlib import sha1
from base64 import b64encode, b64decode
from random import getrandbits
from datetime import datetime

import const


def create_tracking_id(request):
    """
    Create the tracking id based on some request data.

    :param request: Twisted request.
    :return: Base64 encoded tracking id with checksum.
    """
    logger = logging.getLogger(__name__)
    logger.info("Creating new tracking id.")

    tid_elements = [int(time.time() * 1000 * 1000),                                       # Microsecond epoch time
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
    checksum_sha1.update(uid + const.SECRET)
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

    :param request: Request to attach the cookie to.
    :return: Tracking id.
    """
    logger = logging.getLogger(__name__)
    tid = request.getCookie(const.COOKIE_NAME)

    if not (tid and is_tracking_id_valid(tid)):
        logger.warning("Needs new tracking id.")
        tid = create_tracking_id(request)

    logger.info("Attaching tracking id.")

    request.addCookie(const.COOKIE_NAME, tid, expires=str(datetime.now() + const.EXPIRY_PERIOD))

    return tid


def get_schema():
    schema = '''[
        {
            "label": "Site",
            "key": "site",
            "children": [
                {
                    "label": "Site domain",
                    "key": "domain",
                    "values": [
                        {"label": "coinmarketcap.com", "value": "coinmarketcap.com"},
                        {"label": "icoalert.com", "value": "icoalert.com"}
                    ],
                    "value_type": "string",
                    "allow_input": true
                },
                {
                    "label": "Inside frame",
                    "key": "inframe",
                    "value_type": "boolean",
                    "values": [
                        {"label": "Yes", "value": "true"},
                        {"label": "No", "value": "false"}
                    ],
                    "allow_input": false
                },
                {
                    "label": "Language",
                    "key": "lang",
                    "values": [
                        {"label": "Polish", "value": "pl"},
                        {"label": "English", "value": "en"},
                        {"label": "Italian", "value": "it"},
                        {"label": "Japanese", "value": "jp"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                },
                {
                    "label": "Content keywords",
                    "key": "keywords",
                    "values": [
                        {"label": "blockchain", "value": "blockchain"},
                        {"label": "ico", "value": "ico"}
                    ],
                    "value_type": "string",
                    "allow_input": true
                }
            ]
        },
        {
            "label": "User",
            "key": "user",
            "children": [
                {
                    "label": "Age",
                    "key": "age",
                    "values": [
                        {"label": "18-35", "value": "18,35"},
                        {"label": "36-65", "value": "36,65"}
                    ],
                    "value_type": "number",
                    "allow_input": true
                },
                {
                    "label": "Interest keywords",
                    "key": "keywords",
                    "values": [
                        {"label": "blockchain", "value": "blockchain"},
                        {"label": "ico", "value": "ico"}
                    ],
                    "value_type": "string",
                    "allow_input": true
                },
                {
                    "label": "Language",
                    "key": "lang",
                    "values": [
                        {"label": "Polish", "value": "pl"},
                        {"label": "English", "value": "en"},
                        {"label": "Italian", "value": "it"},
                        {"label": "Japanese", "value": "jp"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                },
                {
                    "label": "Gender",
                    "key": "gender",
                    "values": [
                        {"label": "Male", "value": "pl"},
                        {"label": "Female", "value": "en"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                },
                {
                    "label": "Geo",
                    "key": "geo",
                    "children": [
                        {
                            "label": "Continent",
                            "key": "continent",
                            "values": [
                                {"label": "Africa", "value": "af"},
                                {"label": "Asia", "value": "as"},
                                {"label": "Europe", "value": "eu"},
                                {"label": "North America", "value": "na"},
                                {"label": "South America", "value": "sa"},
                                {"label": "Oceania", "value": "oc"},
                                {"label": "Antarctica", "value": "an"}
                            ],
                            "value_type": "string",
                            "allow_input": false
                        },
                        {
                            "label": "Country",
                            "key": "country",
                            "values": [
                                {"label": "United States", "value": "us"},
                                {"label": "Poland", "value": "pl"},
                                {"label": "Spain", "value": "eu"},
                                {"label": "China", "value": "cn"}
                            ],
                            "value_type": "string",
                            "allow_input": false
                        }
                    ]
                }
            ]
        },
        {
            "label": "Device",
            "key": "device",
            "children": [
                {
                    "label": "Screen size",
                    "key": "screen",
                    "children": [
                        {
                            "label": "Width",
                            "key": "width",
                            "values": [
                                {"label": "1200 or more", "value": "<1200,>"},
                                {"label": "between 1200 and 1800", "value": "<1200,1800>"}
                            ],
                            "value_type": "number",
                            "allow_input": true
                        },
                        {
                            "label": "Height",
                            "key": "height",
                            "values": [
                                {"label": "1200 or more", "value": "<1200,>"},
                                {"label": "between 1200 and 1800", "value": "<1200,1800>"}
                            ],
                            "value_type": "number",
                            "allow_input": true
                        }
                    ]
                },
                {
                    "label": "Language",
                    "key": "lang",
                    "values": [
                        {"label": "Polish", "value": "pl"},
                        {"label": "English", "value": "en"},
                        {"label": "Italian", "value": "it"},
                        {"label": "Japanese", "value": "jp"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                },
                {
                    "label": "Browser",
                    "key": "browser",
                    "values": [
                        {"label": "Chrome", "value": "Chrome"},
                        {"label": "Edge", "value": "Edge"},
                        {"label": "Firefox", "value": "Firefox"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                },
                {
                    "label": "Operating system",
                    "key": "os",
                    "values": [
                        {"label": "Linux", "value": "Linux"},
                        {"label": "Mac", "value": "Mac"},
                        {"label": "Windows", "value": "Windows"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                },
                {
                    "label": "Geo",
                    "key": "geo",
                    "children": [
                        {
                            "label": "Continent",
                            "key": "continent",
                            "values": [
                                {"label": "Africa", "value": "af"},
                                {"label": "Asia", "value": "as"},
                                {"label": "Europe", "value": "eu"},
                                {"label": "North America", "value": "na"},
                                {"label": "South America", "value": "sa"},
                                {"label": "Oceania", "value": "oc"},
                                {"label": "Antarctica", "value": "an"}
                            ],
                            "value_type": "string",
                            "allow_input": false
                        },
                        {
                            "label": "Country",
                            "key": "country",
                            "values": [
                                {"label": "United States", "value": "us"},
                                {"label": "Poland", "value": "pl"},
                                {"label": "Spain", "value": "eu"},
                                {"label": "China", "value": "cn"}
                            ],
                            "value_type": "string",
                            "allow_input": false
                        }
                    ]
                },
                {
                    "label": "Javascript support",
                    "key": "js_enabled",
                    "value_type": "boolean",
                    "values": [
                        {"label": "Yes", "value": "true"},
                        {"label": "No", "value": "false"}
                    ],
                    "allow_input": false
                }
            ]
        }
    ]'''

    return schema
