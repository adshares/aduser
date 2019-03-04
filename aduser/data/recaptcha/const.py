import os

#: Site key for generating the HTML code your site serves to users.
#:
#: `Environmental variable override: RECAPTCHA_SITE_KEY`
RECAPTCHA_SITE_KEY = os.getenv('RECAPTCHA_SITE_KEY', None)


#: Secret key for communication between your site and reCAPTCHA.
#:
#: `Environmental variable override: RECAPTCHA_SECRET_KEY`
RECAPTCHA_SECRET_KEY = os.getenv('RECAPTCHA_SECRET_KEY', None)
