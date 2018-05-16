class ProviderClient:
    """
    Interface class for data providers.
    """
    def pixel(self):
        """

        :return: pixel image
        """
        return NotImplemented

    def get_data(self, user_identifier=None):
        """

        :param user_identifier:
        :return: User profile (with keywords)
        """
        return NotImplemented

    def get_schema(self):
        """

        :return: Schema
        """
        return NotImplemented
