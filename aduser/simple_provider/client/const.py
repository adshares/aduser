import os
from dotenv import load_dotenv

load_dotenv()

#: Data provider redirect
DATA_PROVIDER = os.getenv('ADUSER_SIMPLE_DATA_PROVIDER')

#: Data provider consumer information endpoint
DATA_PROVIDER_CONSUMER_INFO = os.getenv('ADUSER_SIMPLE_DATA_PROVIDER_CONSUMER_INFO')
