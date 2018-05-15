class ProviderClient:

    def pixel(self):
        return NotImplemented

    def get_data(self, user_identifier=None):
        return NotImplemented

    def get_schema(self):
        return NotImplemented
