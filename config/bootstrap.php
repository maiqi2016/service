<?php
Yii::setAlias('@service', dirname(__DIR__));

define('VERSION', '4.0.0');

define('TIME', $_SERVER['REQUEST_TIME']);
define('DS', DIRECTORY_SEPARATOR);

define('DB_KAKE', 'kake');
define('DB_SERVICE', 'service');

define('MINUTE', 60);
define('HOUR', MINUTE * 60);
define('DAY', HOUR * 24);
define('WEEK', DAY * 7);
define('MONTH', DAY * 30);
define('YEAR', MONTH * 12);