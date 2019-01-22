from twisted.internet import defer

from aduser.db import utils as db_utils
from tests import db_test_case


class DbUtilsTestCase(db_test_case):

    @defer.inlineCallbacks
    def test_get_collection_iter(self):
        # Verify we get an empty list.
        l1, l2 = yield db_utils.get_collection_iter('data')
        self.assertListEqual([], l1)

    @defer.inlineCallbacks
    def test_mapping(self):
        # Verify you can't get non-existing user data.
        user_doc = yield db_utils.get_mapping('fake_user_id')
        self.assertIsNone(user_doc)

        # Verify mapping is added.
        mapping_doc = {'tracking_id': 'tracking_id',
                       'server_user_id': 'server_user_id'}

        update = yield db_utils.update_mapping(mapping_doc)
        self.assertIsNotNone(update)
        self.assertIsNotNone(update.upserted_id)

        # Verify mapping we get from database is the same we put in.
        user_doc = yield db_utils.get_mapping(mapping_doc['server_user_id'])
        self.assertIsNotNone(user_doc)

        # Remove mongo attribute
        del user_doc['_id']
        self.assertEqual(user_doc, mapping_doc)

    @defer.inlineCallbacks
    def test_user_data(self):
        # Verify you can't get non-existing user data.
        user_doc = yield db_utils.get_user_data('fake_tracking_id')
        self.assertIsNone(user_doc)

        # Verify user data is added.
        user_data_doc = {'tracking_id': 'tracking_id',
                         'data': 'user_data'}

        update = yield db_utils.update_user_data(user_data_doc)
        self.assertIsNotNone(update)
        self.assertIsNotNone(update.upserted_id)

        # Verify user data we get from database is the same we put in.
        user_doc = yield db_utils.get_user_data(user_data_doc['tracking_id'])
        self.assertIsNotNone(user_doc)

        # Remove mongo attribute
        del user_doc['_id']
        self.assertEqual(user_doc, user_data_doc)

    @defer.inlineCallbacks
    def test_pixel_update(self):
        # Verify pixel doc is added.
        pixel_doc = {'tracking_id': 'tracking_id',
                     'data': 'header',
                     'more_headers': 'more'}

        update = yield db_utils.update_pixel(pixel_doc)
        self.assertIsNotNone(update)
        self.assertIsNotNone(update.upserted_id)
