from twisted.internet import defer

from aduser import db


def get_collection_iter(collection_name):
    """
    Returns a batch of documents iterable and a deferred. The deferred can be called to get another batch.

    :param collection_name: Name of the collection we iterate over.
    """
    return db.get_collection(collection_name).find(cursor=True)


# User mapping

@defer.inlineCallbacks
def update_mapping(mapping_doc):
    """
    Insert or update mapping between external server+user id and our internal tracking id.

    :param mapping_doc: Document with external ('server_user_id') and internal ('tracking_id') id.
    :return: Deferred instance of :class:`pymongo.results.UpdateResult`.
    """
    return_value = yield db.get_collection('user').replace_one({'tracking_id': mapping_doc['tracking_id']},
                                                               mapping_doc,
                                                               upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def get_mapping(user_id):
    """
    Get user mapping from the database to find which tracking ids match.

    :param user_id: Our internal user id (server+user id)
    :return:
    """
    return_value = yield db.get_collection('user').find_one({'server_user_id': user_id})

    defer.returnValue(return_value)


# User data storage


@defer.inlineCallbacks
def update_user_data(data_doc):
    """
    Insert or update user data cached in the database,

    :param data_doc: User data document.
    :return: Deferred instance of :class:`pymongo.results.UpdateResult`.
    """
    return_value = yield db.get_collection('data').replace_one({'tracking_id': data_doc['tracking_id']},
                                                               data_doc,
                                                               upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def get_user_data(tracking_id):
    """
    Get user data cached in the database.

    :param tracking_id: Our tracking id.
    :return: User data document.
    """
    return_value = yield db.get_collection('data').find_one({'tracking_id': tracking_id})

    defer.returnValue(return_value)


# Pixel requests


@defer.inlineCallbacks
def update_pixel(pixel_doc):
    """
    Insert or update pixel request. We collect all pixel requests.

    :param pixel_doc: Request data + our tracking id.
    :return: Deferred instance of :class:`pymongo.results.UpdateResult`.
    """
    return_value = yield db.get_collection('pixel').replace_one({'tracking_id': pixel_doc['tracking_id']},
                                                                pixel_doc,
                                                                upsert=True)
    defer.returnValue(return_value)
