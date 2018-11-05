from twisted.internet.defer import succeed
from twisted.internet.protocol import Protocol
from twisted.web.iweb import IBodyProducer
from zope.interface import implements


class StringProducer(object):
    implements(IBodyProducer)

    def __init__(self, body):
        self.body = body
        self.length = len(body)

    def startProducing(self, consumer):  # NOSONAR
        consumer.write(self.body)
        return succeed(None)

    def pauseProducing(self):  # NOSONAR
        pass

    def stopProducing(self):  # NOSONAR
        pass


class ReceiverProtocol(Protocol):
    def __init__(self, finished):
        self.finished = finished
        self.body = []

    def dataReceived(self, databytes):  # NOSONAR
        self.body.append(databytes)

    def connectionLost(self, reason):  # NOSONAR
        self.finished.callback(''.join(self.body))
