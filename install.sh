#! /bin/bash

# add write
sudo chmod -R a+w runtime/ web/assets/

# add execute
sudo chmod a+x thrfit/service.php

# create config files
sudo cp config/main-local.php.backup config/main-local.php
sudo cp config/params-local.php.backup config/params-local.php

echo
read -p "Please choose environment. [dev/prod]: " env
if [ "${env}" != "dev" -a "${env}" != "prod" ]
then
    alert 31 'Environment must be dev/prod!'
    exit 1
fi

sudo cp web/index-${env}.php web/index.php
sudo cp web/index-${env}.php web/index.php

# composer
composer install