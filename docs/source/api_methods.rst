API methods
===========

getPixelPath
^^^^^^^^^^^^

    .. http:get:: /getPixelPath

        Get pixel path


Get pixel
^^^^^^^^^^^^

    .. http:get:: /???

        Get actual pixel and attach the tracking cookie.


getData
^^^^^^^

    .. http:post:: /???

        Get actual pixel and attach the tracking cookie.


getTaxonomy
^^^^^^^^^^^

    .. http:get:: /getTaxonomy

        Get taxonomy information

info
^^^^

    .. http:get:: /info

        Get server and api information (currently not implemented)



    root.putChild("getPixelPath", iface_resources.PixelPathResource())
    root.putChild("getData", iface_resources.DataResource())
    root.putChild("getTaxonomy", iface_resources.TaxonomyResource())
    root.putChild("info", iface_resources.ApiInfoResource())
    root.putChild(iface_const.PIXEL_PATH, iface_resources.PixelFactory())