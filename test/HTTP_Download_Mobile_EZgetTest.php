<?php
require_once 'PHPUnit/Framework.php';
require_once 'HTTP/Download/Mobile/EZget.php';

require_once 'HTTP/Request2.php';
require_once 'Net/URL2.php';
require_once 'MIME/Type.php';


class HTTP_Download_Mobile_EZgetTestCase extends PHPUnit_Framework_TestCase
{
    public function testGetResponseType()
    {
        $ezget = new HTTP_Download_Mobile_EZget();

        $data = array(
            array(
                'filename' => HTTP_DOWNLOAD_MOBILE_EZGET_DATA_DIR.'/picture.jpg',
                'offset'   => 0,
                'count'    => 120,
                'response' => HTTP_Download_Mobile_EZget::RESPONSE_DOWNLOADING,
            ),
            array(
                'filename' => 'dummy.jpg',
                'offset'   => 0,
                'count'    => 120,
                'response' => HTTP_Download_Mobile_EZget::RESPONSE_FILENOTFOUND,
            ),
            array(
                'filename' => HTTP_DOWNLOAD_MOBILE_EZGET_DATA_DIR.'/picture.jpg',
                'offset'   => 0,
                'count'    => 0,
                'response' => HTTP_Download_Mobile_EZget::RESPONSE_DOWNLOADEMPTY,
            ),
            array(
                'filename' => HTTP_DOWNLOAD_MOBILE_EZGET_DATA_DIR.'/picture.jpg',
                'offset'   => -1,
                'count'    => -1,
                'response' => HTTP_Download_Mobile_EZget::RESPONSE_COMPLETED,
            ),
            array(
                'filename' => HTTP_DOWNLOAD_MOBILE_EZGET_DATA_DIR.'/picture.jpg',
                'offset'   => -1,
                'count'    => -2,
                'response' => HTTP_Download_Mobile_EZget::RESPONSE_FAILED,
            ),
            array(
                'filename' => HTTP_DOWNLOAD_MOBILE_EZGET_DATA_DIR.'/picture.jpg',
                'offset'   => -2,
                'count'    => -2,
                'response' => HTTP_Download_Mobile_EZget::RESPONSE_UNKNOWN,
            ),
            array(
                'filename' => HTTP_DOWNLOAD_MOBILE_EZGET_DATA_DIR.'/picture.jpg',
                'offset'   => null,
                'count'    => null,
                'response' => HTTP_Download_Mobile_EZget::RESPONSE_UNKNOWN,
            ),
            array(
                'filename' => HTTP_DOWNLOAD_MOBILE_EZGET_DATA_DIR.'/picture.jpg',
                'offset'   => '0',
                'count'    => '0',
                'response' => HTTP_Download_Mobile_EZget::RESPONSE_UNKNOWN,
            ),
        );
        foreach ($data as $value) {
            $actual = $ezget->getResponseType($value['offset'], $value['count'], $value['filename']);
            $this->assertEquals($value['response'], $actual);
        }
    }

    public function testGetResponseMessage()
    {
        $ezget = new HTTP_Download_Mobile_EZget();

        $messages = array(
            HTTP_Download_Mobile_EZget::RESPONSE_UNKNOWN       => '1',
            HTTP_Download_Mobile_EZget::RESPONSE_DOWNLOADING   => '2',
            HTTP_Download_Mobile_EZget::RESPONSE_DOWNLOADEMPTY => '3',
            HTTP_Download_Mobile_EZget::RESPONSE_COMPLETED     => '4',
            HTTP_Download_Mobile_EZget::RESPONSE_FAILED        => '5',
            HTTP_Download_Mobile_EZget::RESPONSE_FILENOTFOUND  => '6',
        );
        foreach ($messages as $response_type => $message) {
            $ezget->setResponseMessage($response_type, $message);
        }

        foreach ($messages as $response_type => $expect) {
            $actual = $ezget->getResponseMessage($response_type);
            $this->assertEquals($actual,$expect);
        }
    }

    public function testGetResponse()
    {
        $ezget = new HTTP_Download_Mobile_EZget();

        $data = array(
            array(
                'filename' => HTTP_DOWNLOAD_MOBILE_EZGET_DATA_DIR.'/picture.jpg',
                'offset'   => 0,
                'count'    => 120,
                'response' => file_get_contents(HTTP_DOWNLOAD_MOBILE_EZGET_DATA_DIR.'/picture.jpg', 0, null, 0, 120),
            ),
            array(
                'filename' => 'dummy.jpg',
                'offset'   => 0,
                'count'    => 120,
                'response' => implode("\n", array(
                                          '<hdml version=3.0 ttl="0" public=true>',
                                          '<display>',
                                          '<action type=accept task=cancel>',
                                          '<wrap>'.mb_convert_encoding('ファイルが見つかりません', 'shift-jis', 'UTF-8').'</wrap>',
                                          '</display>',
                                          '</hdml>',
                                      )),
            ),
            array(
                'filename' => 'dummy.jpg',
                'offset'   => null,
                'count'    => null,
                'response' => implode("\n", array(
                                          '<hdml version=3.0 ttl="0" public=true>',
                                          '<display>',
                                          '<action type=accept task=cancel>',
                                          '<wrap>'.mb_convert_encoding('エラーが発生しました', 'shift-jis', 'UTF-8').'</wrap>',
                                          '</display>',
                                          '</hdml>',
                                      )),
            ),
        );
        foreach ($data as $value) {
            $response = $ezget->setFilename($value['filename'])
                              ->setOffset($value['offset'])
                              ->setCount($value['count'])
                              ->getResponse();

            $this->assertEquals($value['response'], $response['body']);
        }
    }

    public function _testBaseic()
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
