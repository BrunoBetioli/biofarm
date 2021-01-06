<?php
date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_ALL, "pt_BR");

if (!defined('SITE_DATE_FORMAT')) {
    define('SITE_DATE_FORMAT', 'd/m/Y');
}

if (!defined('SITE_TIME_FORMAT')) {
    define('SITE_TIME_FORMAT', 'H:i:s');
}

if (!defined('USE_BASE_FOLDER')) {
    define('USE_BASE_FOLDER', true);
	if (!defined('BASE_FOLDER')) {
		define('BASE_FOLDER', basename(dirname(dirname(dirname(dirname(__FILE__))))));
	}
}

if (!defined('USE_MOD_REWRITE')) {
    define('USE_MOD_REWRITE', true);
}

if (!defined('DEBUG')) {
    define('DEBUG', false);
}