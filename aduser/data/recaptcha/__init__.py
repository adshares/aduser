import requests
import json
from string import Template
from aduser.iface import const as iface_const
from aduser.data import const as data_const
from aduser.data.recaptcha import const as recaptcha_const
import logging

def score_code(tracking_id, request):
    if not recaptcha_const.RECAPTCHA_SITE_KEY:
        return None
    request.setHeader(b"content-type", b"text/html; charset=utf-8")
    code = Template("""<!DOCTYPE html>
<html>
<script src="https://www.google.com/recaptcha/api.js?render=$key"></script>
<script>
  grecaptcha.ready(function() {
      grecaptcha.execute('$key', {action: 'pixel'}).then(function(token) {
         window.location.replace('//$host:$port/$path/' + token + '/' + Math.random().toString(36).substring(9) + '.gif');
      });
  });
</script>""")
    return code.substitute(key=recaptcha_const.RECAPTCHA_SITE_KEY, host=request.getHost().host,
                           port=request.getHost().port, path=iface_const.SCORE_PATH)


def user_score(tracking_id, token, request):
    url = 'https://www.google.com/recaptcha/api/siteverify'
    payload = {'secret': recaptcha_const.RECAPTCHA_SECRET_KEY, 'response': token}
    response = requests.post(url, data=payload)
    if response.status_code == requests.codes.ok:
        data = json.loads(response.text)
        logging.debug(data)
        return data['score'] if data['success'] else data_const.DEFAULT_HUMAN_SCORE
    logging.debug(response)
    return data_const.DEFAULT_HUMAN_SCORE
