<?php

require_once dirname(__FILE__).'/bootstrap.php';
require_once dirname(__FILE__).'/../src/HTTP/Download/Mobile/EZget.php';

$ezget = new HTTP_Download_Mobile_EZget();
$ezget->setBasePath(HTTP_DOWNLOAD_MOBILE_EZGET_DATA_DIR);
$ezget->send();
