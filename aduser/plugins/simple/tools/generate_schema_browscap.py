from __future__ import print_function

import csv
from collections import defaultdict
from aduser.plugins.simple import const

index = {}

sets = defaultdict(set)

with open(const.BROWSCAP_CSV_PATH, 'rb') as csvfile:
    spamreader = csv.reader(csvfile, delimiter=',', quotechar='"')

    for row in spamreader:
        if len(row) > 2:
            for ind, val in enumerate(row):
                index[val] = ind
            break

    for row in spamreader:
        for key in index:
            if row[index['Crawler']] != 'true' and row[index[key]]:
                sets[key].add(row[index[key]])

for key in sorted(sets):

    print(key)
    if key == 'Browser':
        values_list = []
        for l in sorted(sets[key]):
            values_list.append({"key": l.lower().replace(' ', '_'),
                                "label": l})
        print(values_list)
    if key.startswith('Device_Type'):
        values_list = []
        for l in sorted(sets[key]):
            values_list.append({"key": l.lower().replace(' ', '_'),
                                "label": l})
        print(values_list)
    if key == 'Platform':
        values_list = []
        for l in sorted(sets[key]):
            values_list.append({"key": l.lower().replace(' ', '_'),
                                "label": l})
        print(values_list)

    if key.startswith('JavaScript'):
        print(sorted(sets[key]))
