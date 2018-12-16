import json
import os

mock = None
default_mock = {"label": "Interest",
                "key": "interest",
                "type": "dict",
                "data": [{"key": "1", "label": "Music"},
                         {"key": "2", "label": "Books"},
                         ]}


def init(mock_data_file=None):
    global mock
    if not mock_data_file:
        mock = default_mock
        return
    else:
        try:
            with open(mock_data_file, 'r') as f:
                mock = json.load(f)
        except (ValueError, IOError, TypeError) as e:
            mock = default_mock


if not mock:
    init(os.getenv('ADUSER_MOCK_DATA_PATH'))
