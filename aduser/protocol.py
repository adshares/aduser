from jsonobject import *


class SchemaMetadata(JsonObject):
    schema_version = StringProperty()
    schema_name = StringProperty()


class SchemaKeywordMeta(JsonObject):
    label = StringProperty()
    type = StringProperty(choices=['input', 'dict', 'num', 'bool'])
    values = DictProperty(exclude_if_none=True, default=False)


class SchemaResponse(JsonObject):
    meta = DictProperty(SchemaMetadata)
    values = DictProperty(SchemaKeywordMeta)


class DataRequest(JsonObject):
    username = StringProperty()
    domain = StringProperty()


class DataResponse(JsonObject):
    uid = StringProperty()
    human_score = StringProperty()
    keywords = DictProperty()


class NormalizationRequest(JsonObject):
    keywords = DictProperty()


class NormalizationResponse(JsonObject):
    meta = DictProperty(SchemaMetadata)
    keywords = DictProperty()
