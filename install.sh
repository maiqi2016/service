#! /bin/bash

path=$(cd `dirname $0`; pwd)

# create config files
cp ${path}/config/main.php.backup ${path}/config/main.php
cp ${path}/config/main-local.php.backup ${path}/config/main-local.php
cp ${path}/config/params.php.backup ${path}/config/params.php
cp ${path}/config/params-local.php.backup ${path}/config/params-local.php

# add write
chmod -R a+w ${path}/config/
chmod -R a+w ${path}/runtime/
mkdir ${path}/web/assets/ >/dev/null
chmod -R a+w ${path}/web/assets/

# add execute
chmod a+x ${path}/thrift/service.php

echo
read -p "Please choose environment. [dev/prod]: " env
if [ "${env}" != "dev" -a "${env}" != "prod" ]
then
    alert 31 'Environment must be dev/prod!'
    exit 1
fi

cp ${path}/web/index-${env}.php ${path}/web/index.php