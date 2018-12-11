from twisted.web.resource import Resource

from aduser import const
from aduser.api.v1.server import ApiInfoResource, DataResource, PixelFactory, PixelPathResource, TaxonomyResource


def configure_entrypoint():

    # Set up endpoints.
    root = Resource()
    root.putChild("getPixelPath", PixelPathResource())
    root.putChild("getData", DataResource())
    root.putChild("getTaxonomy", TaxonomyResource())
    root.putChild("info", ApiInfoResource())
    root.putChild(const.PIXEL_PATH, PixelFactory())

    return root
