import txmongo
from twisted.internet import defer
from txmongo import filter

from aduser.db import const as db_consts


@defer.inlineCallbacks
def configure_db():
    """
    Configures the database
    """
    yield get_mongo_db()

    # Creating indexes when daemon starts
    suid_idx = filter.sort(filter.ASCENDING("server_user_id"))
    tid_idx = filter.sort(filter.ASCENDING("tracking_id"))

    # User identification collection
    user_collection = yield get_collection('user')
    yield user_collection.create_index(suid_idx, unique=True)
    yield user_collection.create_index(tid_idx)

    yield get_collection('pixel').create_index(suid_idx)
    yield get_collection('data').create_index(tid_idx)


def get_mongo_db():
    """

    :return: MongoDB instance
    """
    conn = get_mongo_connection()
    return getattr(conn, db_consts.MONGO_DB_NAME)


def get_collection(name):
    """

    :param name: Name of MongoDB collection
    :return: deferred instance of :class:`txmongo.collection.Collection`.
    """
    db = get_mongo_db()
    return getattr(db, name)


#: Global MongoDB connection.
MONGO_CONNECTION = None


def get_mongo_connection():
    """

    :return: Global connection to MongoDB
    """
    global MONGO_CONNECTION
    if MONGO_CONNECTION is None:
        MONGO_CONNECTION = txmongo.lazyMongoConnectionPool(port=db_consts.MONGO_DB_PORT,
                                                           host=db_consts.MONGO_DB_HOST)
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
