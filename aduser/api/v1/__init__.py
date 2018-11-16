from twisted.web.resource import Resource

from aduser.api.v1.server import ApiInfoRequest, DataRequest, NormalizationRequest, PixelFactory, PixelPathFactory, \
    SchemaRequest


def configure_entrypoint():

    # Set up endpoints.
    root = Resource()
    root.putChild("getPixelPath", PixelPathFactory())
    root.putChild("pixel", PixelFactory())
    root.putChild("getData", DataRequest())
    root.putChild("getSchema", SchemaRequest())
    root.putChild("info", ApiInfoRequest())

    return root
