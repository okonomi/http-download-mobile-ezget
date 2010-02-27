<?php

require_once dirname(__FILE__).'/bootstrap.php';
require_once dirname(__FILE__).'/../src/HTTP/Download/Mobile/EZget.php';
require_once 'HTTP/Download.php';


$ezget = new HTTP_Download_Mobile_EZget();

$response = $ezget->setFilename(isset($_REQUEST['name']) ? $_REQUEST['name'] : null)
                  ->setOffset(isset($_REQUEST['offset']) ? $_REQUEST['offset'] : null)
                  ->setCount(isset($_REQUEST['count']) ? $_REQUEST['count'] : null)
                  ->getResponse();

$download = new HTTP_Download();
$download->setContentType($response['content-type']);
$download->setData($response['body']);
$download->setCache(false);
$download->send();
