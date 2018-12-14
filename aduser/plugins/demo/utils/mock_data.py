import json
import os

mock = {}
default_mock = {"label": "Interest",
                "key": "interest",
                "type": "dict",
                "data": [{"key": "1", "label": "Music"},
                         {"key": "2", "label": "Books"},
                         ]}

try:
    with open(os.getenv('ADUSER_MOCK_DATA_PATH'), 'r') as f:
        mock = json.load(f)
except (ValueError, IOError, TypeError):
    mock = default_mock
