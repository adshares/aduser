import json

from aduser.data.demo import const as demo_const

#: Mock data access point.
mock = None

#: Default mock, used when initialization fails.
default_mock = {"label": "Interest",
                "key": "interest",
                "type": "dict",
                "data": [{"key": "1", "label": "Music"},
                         {"key": "2", "label": "Books"},
                         ]}


def init(mock_data_file=None):
    """
    Try to read in the mock file. If this fails, assign default mock.

    :param mock_data_file: Path to JSON file with mock data.
    :return:
    """
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


# Initialize on import
if not mock:
    init(demo_const.MOCK_DATA_PATH)
