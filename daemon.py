from twisted.internet import reactor
from aduser.simple_provider import server as provider_server
from aduser.proxy import server as proxy_server


if __name__ == "__main__":

    provider_server.configure_server()
    proxy_server.configure_server()

    reactor.run()
