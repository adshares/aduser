from twisted.internet import reactor

from aduser import db, logs as server_logging, server_utils

if __name__ == "__main__":
    # Set up logging.
    server_logging.setup()

    # Configuring database.
    db.configure_db()

    # Set up server
    server_utils.configure_server()

    # Run Twisted reactor
    reactor.run()
