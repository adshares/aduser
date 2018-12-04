from twisted.internet import defer

from aduser import db


# ALL #


def get_collection_iter(collection_name):
    """
    Returns a batch of documents iterable and a deferred. The deferred can be called to get another batch.

    :param collection_name: Name of the collection we iterate over.
    """
    return db.get_collection(collection_name).find(cursor=True)


@defer.inlineCallbacks
def update_mapping(mapping_doc):
    """
    Update campaign data or create one if doesn't exist.

    :param mapping_doc: New campaign data, must include campaign_id to identify existing data.
    :return: deferred instance of :class:`pymongo.results.UpdateResult`.
    """
    return_value = yield db.get_collection('user').replace_one({'tracking_id': mapping_doc['tracking_id']},
                                                               mapping_doc,
                                                               upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def update_pixel(pixel_doc):
    """
    Update campaign data or create one if doesn't exist.

    :param pixel_doc: New campaign data, must include campaign_id to identify existing data.
    :return: deferred instance of :class:`pymongo.results.UpdateResult`.
    """
    return_value = yield db.get_collection('pixel').replace_one({'tracking_id': pixel_doc['tracking_id']},
                                                                pixel_doc,
                                                                upsert=True)
    defer.returnValue(return_value)
