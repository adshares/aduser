Configuration
=============

Configuration is controlled through environmental variables. Default values are provided below.

Application (aduser.const)
--------------------------

.. automodule:: aduser.const
    :members:
    :undoc-members:
    :show-inheritance:

Database (aduser.db.const)
--------------------------

.. automodule:: aduser.db.const
    :members:
    :undoc-members:
    :show-inheritance:

Logging
-------

*config/log_config.json* contains Python logging configuration. You can learn more about it `here. <https://docs.python.org/2/library/logging.config.html>`_ The AdUser daemon will look for this file in the ``$ADUSER_ROOT/aduser/config`` directory, where ``$ADUSER_ROOT`` is an environmental variable.

Logging config for the Python app can be found in the *config/log_config.json* file. By default, it's captured by supervisor to ``$ADUSER_ROOT/log/aduser.log``. Other logs (MongoDB, supervisord) can also be found in the same directory.
4

.. NOTE::
    Configuration items you should specially consider in initial deployment:

    * Tracking cookie name
    * Tracking id expiration

Supervisor
----------

Config for supervisor daemon is in *config/aduser.conf*.
