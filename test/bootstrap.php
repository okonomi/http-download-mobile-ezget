<?php


mb_language('ja');
mb_internal_encoding('UTF-8');

if (defined('E_DEPRECATED')) {
    error_reporting(E_ALL & ~E_DEPRECATED);
} else {
    error_reporting(E_ALL);
}

set_include_path(realpath(dirname(__FILE__) . '/../src') . PATH_SEPARATOR .
                 get_include_path());


define ('HTTP_DOWNLOAD_MOBILE_EZGET_DATA_DIR', dirname(__FILE__).'/data');
