class UserDataSource:
    """
    Base class for user information sources
    """

    def init(self):
        return NotImplemented

    def update_user(self, user):
        return NotImplemented

    def update_source(self):
        return NotImplemented
