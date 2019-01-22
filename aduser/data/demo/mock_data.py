import json

from aduser.data.demo import const as demo_const

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
        except (ValueError, IOError, TypeError):
            mock = default_mock


if not mock:
    init(demo_const.MOCK_DATA_PATH)
