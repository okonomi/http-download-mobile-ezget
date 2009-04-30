<?php
require_once 'PHPUnit/Framework.php';
require_once 'HTTP/Download/Mobile/EZget.php';

require_once 'HTTP/Request2.php';
require_once 'Net/URL2.php';
require_once 'MIME/Type.php';


class HTTP_Download_Mobile_EZgetTestCase extends PHPUnit_Framework_TestCase
{
    function testBaseic()
    {
        try {
            $url = new Net_URL2('http://localhost/unittest/http_download_mobile_ezget/send.php');
            $url->setQueryVariable('name' ,'picture.jpg');

            $filesize = filesize(HTTP_DOWNLOAD_MOBILE_EZGET_DATA_DIR.'/picture.jpg');

            $response = null;
            $step = 0;

            $offset = 0;
            do {
                if ($offset == $filesize) {
                    $offset = -1;
                    $count  = -1;
                } else {
                    if ($filesize - $offset < 1280) {
                        $count = $filesize - $offset;
                    } else {
                        $count = 1280;
                    }
                }

                echo sprintf('offset = %6d, count = %6d', $offset, $count).PHP_EOL;

                $url->setQueryVariable('offset', $offset);
                $url->setQueryVariable('count' , $count);

                $request = new HTTP_Request2($url, HTTP_Request2::METHOD_GET);
                $response = $request->send();

                if (MIME_Type::stripParameters($response->getHeader('Content-type')) === 'text/x-hdml') {
                    break;
                } else {
                    $this->assertEquals($count,
                                        $response->getHeader('Content-length'));

                    $offset += $response->getHeader('Content-length');
                }
            } while (++$step < 10000);

            $this->assertEquals('text/x-hdml',
                                MIME_Type::stripParameters($response->getHeader('Content-type')));

        } catch (HTTP_Request2_Exception $e) {
            $this->fail($e->getMessage());
        }
    }
}
