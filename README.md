# aduser

Implementation of adUser service in Adshares Network

adUser provides data about a visiting user to improve adPay and adSelect results

# Installation
For Ubuntu 16.04

## Dependencies
```
sudo apt-get update
sudo apt-get install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install php7.2-fpm php7.2-bcmath php7.2-curl php7.2-gd php7.2-gmp php7.2-intl php7.2-json php7.2-mysql php7.2-opcache php7.2-readline php7.2-xml php7.2-zip

curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

sudo apt-get install nginx

sudo apt-get install -y percona-server-server
```

## aduser
```
cd /home/adshares
git clone https://github.com/adshares/aduser.git
cd aduser
composer install
bin/console doctrine:database:create
bin/console doctrine:schema:create
```

## nginx
```
tee /etc/nginx/sites-enabled/aduser <<'ADSHARES'
server {
        listen                  80;

        server_name             aduser.dev;
        root    /home/adshares/aduser/web;

        index                   index.htm index.html index.php;
        location ~ /\.  { return 403; }

        location / {
                # try to serve file directly, fallback to rewrite
                try_files $uri @rewriteapp;
        }

        location @rewriteapp {
                # rewrite all to app.php
                rewrite ^(.*)$ /app_dev.php/$1 last;
        }

        location ~ ^/(app|app_dev|config|test)\.php(/|$) {
                include snippets/fastcgi-php.conf;
                fastcgi_pass unix:/run/php/php7.1-fpm.sock;
        }
}
ADSHARES
```

# Configuration
Modify parameters in `app/config/parameters.yml`

# API
### Tracking pixel
`GET /setimg/{requestId}`
* requestId - user provided request id used in future queries about user data

Return tracking pixel and associate it with a provided requestId

### Get data
`GET /get/{requestId}`
* requestId - request id associated with the user 

Return data about the user
```
[
  'user_id' => '12345',
    'request_id' => 'abcdef',
    'human_score' => 0.5,
    'keywords' => [
        'tor' => false, 
        'age' => 24,
        ...
    ],
]
```

### Get info
`GET /info`
Return info about adUser service 

```
[
  'pixel_url' => 'https://example.com/setimg/:id',
  'data_url' => 'https://example.com/get/:id'
  'schema' => []
]
