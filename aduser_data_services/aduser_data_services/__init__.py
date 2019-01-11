from twisted.internet import protocol


class DataResponseProtocol(protocol.Protocol):
    #: Callable for our data query. Take one argument.
    query_function = (lambda q: None)

    def dataReceived(self, data):
        """
        When data is received, query our source, respond and disconnect.

        :param data: Query string.
        :return:
        """

        query_result = self.query_function(data)
        if query_result:
            self.transport.write(bytes(query_result))
        else:
            self.transport.write()

        self.transport.loseConnection()
