import os

#: Name of AdUser data backend plugin. The value must be an importable module.
#:
#: `Environmental variable override: ADUSER_DATA_PROVIDER`
DATA_PROVIDER = os.getenv('ADUSER_DATA_PROVIDER', 'aduser.data.examples.example')
