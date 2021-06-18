<?php
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('APP')) {
    define('APP', 'src'.DS.'app');
}

if (!defined('LIBS')) {
    define('LIBS', 'src'.DS.'libs');
}

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(__FILE__)));
}

if (!defined('WEBROOT')) {
    define('WEBROOT', basename(dirname(__FILE__)));
}

if (!defined('WWW_ROOT')) {
    define('WWW_ROOT', dirname(__FILE__) . DS);
}

// Use HTTP Strict Transport Security to force client to use secure connections only
$use_sts = false;

// iis sets HTTPS to 'off' for non-SSL requests
if ($use_sts && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
    header('Strict-Transport-Security: max-age=31536000');
} elseif ($use_sts) {
    header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], true, 301);
    // we are in cleartext at the moment, prevent further execution and output
    die();
}

setlocale(LC_ALL, "pt_BR", "pt_BR.iso-8859-1", "pt_BR.utf-8", "portuguese");
mb_internal_encoding("utf-8");
header('Content-Type: text/html; charset=utf-8');
include ROOT.DS.APP.DS.'Config'.DS.'config.php';
include ROOT.DS.APP.DS.'Config'.DS.'functions.php';
require ROOT.DS.'vendor'.DS.'autoload.php';

use libs\Application;

$application = new Application();
$application->dispatch();
$application->run();