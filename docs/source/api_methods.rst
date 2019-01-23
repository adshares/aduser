API methods
===========

getPixelPath
^^^^^^^^^^^^

    .. http:get:: /getPixelPath

        Get pixel path

        **Example request**:

        .. sourcecode:: http

              GET /getPixelPath HTTP/1.1
              Host: example.com

        **Example success response**:

        .. sourcecode:: http

            HTTP/1.1 200 OK
            Content-Type: application/json

            "http://example.com/pixel_path/{adserver_id}/{user_id}/{nonce}.gif"

        :resheader Content-Type: application/json
        :statuscode 200: Success

getPixel
^^^^^^^^

    .. http:get:: /{pixel_path}/{adserver_id}/{user_id}/{nonce}.gif

        Get actual pixel and attach the tracking cookie. The url is returned by the `getPixelPath` method.

        .. NOTE::
            This is a dynamic url, the first element ({pixel_path}) can be configured to any value. The following elements are used to uniquely identify the user.

        **Example request**:

        .. sourcecode:: http

              GET /pixel_path/adserver_id/user_id/nonce.gif HTTP/1.1
              Host: example.com


        :resheader Content-Type: image/gif
        :statuscode 200: Success
        :statuscode 404: Path elements are missing

getData
^^^^^^^

    .. http:post:: /getData

        Request user data.

        **Example request**:

        .. sourcecode:: http

              POST / HTTP/1.1
              Host: example.com

              {
               "ip": "111.222.111.222",
               "ua": "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0 Mozilla/5.0 (Macintosh; Intel Mac OS X x.y; rv:42.0) Gecko/20100101 Firefox/42.0",
               "uid": "serverid_userid"
               }

        **Example success response**:

        .. sourcecode:: http

            HTTP/1.1 200 OK
            Content-Type: application/json

            {
             "uid": "serverid_userid",
             "human_score": 0.5,
             "keywords": {}
            }


        :resheader Content-Type: application/json
        :statuscode 200: Success
        :statuscode 400: Malformed JSON or missing attributes
        :statuscode 404: AdUser doesn't track this user


getTaxonomy
^^^^^^^^^^^

    .. http:get:: /getTaxonomy

        Get taxonomy information

        **Example request**:

        .. sourcecode:: http

              GET /getTaxonomy HTTP/1.1
              Host: example.com

        **Example success response**:

        .. sourcecode:: http

            HTTP/1.1 200 OK
            Content-Type: application/json


            {
             "meta": {"name": "example",
                             "version": "0.0.1"},
             "data": [
                      {"label": "Interests",
                       "key": "0001",
                       "type": "dict",
                       "data": [
                                {"label": "Interest: DC", "key": "0001"},
                                {"label": "Interest: Marvel", "key": "0002"},
                                {"label": "Interest: Marvel: Spiderman", "key": "0003"},
                                {"label": "Interest: Marvel: Venom", "key": "0004"}
                                ],
                       },
                       {"label": "Comic books owned",
                        "key": "0001",
                        "type": "num"},
                       {"label": "Favourite superhero",
                        "key": "0001",
                        "type": "input"},
                       {"label": "Registered at comicbookheroes.net",
                        "key": "0001",
                        "type": "bool"}
                      ]
            }

        :resheader Content-Type: application/json
        :statuscode 200: Success

info
^^^^

    .. http:get:: /info

        Get server and api information (currently not implemented)

        **Example request**:

        .. sourcecode:: http

              GET /info HTTP/1.1
              Host: example.com


        **Example success response**:

        .. sourcecode:: http

            HTTP/1.1 200 OK
            Content-Type: application/json

            {}

        :resheader Content-Type: application/json
        :statuscode 200: Success
