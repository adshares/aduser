from twisted.internet import defer
import txmongo
from txmongo import filter

import const


def get_mongo_db():
    """

    :return: MongoDB instance
    """
    conn = get_mongo_connection()
    return getattr(conn, const.MONGO_DB_NAME)


def get_collection(name):
    """

    :param name: Name of MongoDB collection
    :return: deferred instance of :class:`txmongo.collection.Collection`.
    """
    db = get_mongo_db()
    return getattr(db, name)


#: Global MongoDB connection.
MONGO_CONNECTION = None


@defer.inlineCallbacks
def configure_db():
    """
    Configures the database
    """
    yield get_mongo_db()

    # Creating indexes when daemon starts
    consumer_idx = filter.sort(filter.ASCENDING("consumer_id"))

    # Campaign collection
    yield get_collection('consumer').create_index(consumer_idx, unique=True)
    yield get_collection('mapping').create_index(consumer_idx, unique=True)


def get_mongo_connection():
    """

    :return: Global connection to MongoDB
    """
    global MONGO_CONNECTION
    if MONGO_CONNECTION is None:
        MONGO_CONNECTION = txmongo.lazyMongoConnectionPool(port=const.MONGO_DB_PORT)
    return MONGO_CONNECTION


@defer.inlineCallbacks
def disconnect():
    """
    Disconnects asynchronously and removes global connection.
    """
    global MONGO_CONNECTION
    if MONGO_CONNECTION:
        conn = yield get_mongo_connection()
        yield conn.disconnect()
        MONGO_CONNECTION = None


@defer.inlineCallbacks
def save_consumer(doc):
    db = yield get_mongo_db()
    return_value = yield db.get_collection('consumer').replace_one({'consumer_id': doc['consumer_id']},
                                                                   doc, upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def load_consumer(consumer_id):
    db = yield get_mongo_db()
    return_value = yield db.get_collection('consumer').find_one({'consumer_id': consumer_id})
    defer.returnValue(return_value)


@defer.inlineCallbacks
def save_adserver_mapping(adserver_tid, aduser_tid):
    db = yield get_mongo_db()
    mapping = {'adserver_tid': adserver_tid, 'aduser_tid': aduser_tid}
    return_value = yield db.get_collection('adserver_mapping').replace_one(mapping, mapping, upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def load_adserver_mapping(adserver_tid, aduser_tid):
    db = yield get_mongo_db()
    mapping = {'adserver_tid': adserver_tid, 'aduser_tid': aduser_tid}
    return_value = yield db.get_collection('adserver_mapping').find_one(mapping)
    defer.returnValue(return_value)

