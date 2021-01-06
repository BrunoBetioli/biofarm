<?php
define('APP', 'app');
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));
define('WEBROOT', 'webroot');
define('WWW_ROOT', ROOT . DS . WEBROOT . DS);

$index = WEBROOT . DS . 'index.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
require_once $index;