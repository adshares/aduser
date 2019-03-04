import os

#: Name of AdUser data backend plugin. The value must be an importable module.
#:
#: `Environmental variable override: ADUSER_DATA_PROVIDER`
DATA_PROVIDER = os.getenv('ADUSER_DATA_PROVIDER', 'aduser.data.examples.example')

#: Default human score.
#:
#: `Environmental variable override: DEFAULT_HUMAN_SCORE`
DEFAULT_HUMAN_SCORE = os.getenv('ADUSER_DEFAULT_HUMAN_SCORE', 0.5)