Configuration
=============

Configuration is spread among 5 files:

    * aduser.proxy.const
    * aduser.simple_provider.client.const
    * aduser.simple_provider.server.const
    * config/log_config.json
    * config/supervisord.conf

Logging
^^^^^^^

*config/log_config.json* contains Python logging configuration. You can learn more about it `here. <https://docs.python.org/2/library/logging.config.html>`_ The AdUser daemon will look for this file in the ``$ADUSER_ROOT/aduser/config`` directory, where ``$ADUSER_ROOT`` is an environmental variable.

Logging config for the Python app can be found in the *config/log_config.json* file. By default, it's captured by supervisor to ``$ADUSER_ROOT/log/aduser.log``. Other logs (MongoDB, supervisord) can also be found in the same directory.
4

.. NOTE::
    Configuration items you should specially consider in initial deployment:

    * Tracking cookie name
    * Tracking id expiration

Supervisor
^^^^^^^^^^

Config for supervisor daemon configuration (log and pid file paths) is in *config/supervisord.conf*.
