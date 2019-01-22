from twisted.internet import defer

from aduser import db
from aduser.db import const as db_const, utils as db_utils
from tests import db_test_case


class DbUtilsTestCase(db_test_case):

    @defer.inlineCallbacks
    def test_get_collection_iter(self):
        l1, l2 = yield db_utils.get_collection_iter('data')
        self.assertListEqual([], l1)

    @defer.inlineCallbacks
    def test_mapping(self):
        # Try to get non-existing mapping
        user_doc = yield db_utils.get_mapping('fake_user_id')
        self.assertIsNone(user_doc)

        # Try to add mapping

        mapping_doc = {'tracking_id': 'tracking_id',
                       'server_user_id': 'server_user_id'}

        update = yield db_utils.update_mapping(mapping_doc)
        self.assertIsNotNone(update)

        self.assertIsNotNone(update.upserted_id)

        # Try to get existing mapping from database

        user_doc = yield db_utils.get_mapping(mapping_doc['server_user_id'])
        self.assertIsNotNone(user_doc)

        del user_doc['_id']

        # Remove mongo attribute
        self.assertEqual(user_doc, mapping_doc)
