<?php

require_once 'HTTP/Download.php';


/**
 * @see http://www.au.kddi.com/ezfactory/tec/dlcgi/download_1.html
 */
class HTTP_Download_Mobile_EZget
{
    const RESPONSE_UNKNOWN       = 0;
    const RESPONSE_DOWNLOADING   = 1;
    const RESPONSE_DOWNLOADEMPTY = 2;
    const RESPONSE_COMPLETED     = 3;
    const RESPONSE_FAILED        = 4;
    const RESPONSE_FILENOTFOUND  = 5;


    protected $path;

    protected $name;

    protected $offset;

    protected $count;

    protected $messages;


    function __construct()
    {
        $this->reset();
    }

    /**
     * 出力ァイルの親ディレクトリを設定
     */
    public function setBasePath($path)
    {
        $this->path = $path;
    }

    /**
     * nameパラメータを設定
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * offsetパラメータを設定
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * countパラメータを設定
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * レスポンスを取得
     */
    public function getDownload()
    {
        foreach (array('name', 'offset', 'count') as $param) {
            if (empty($this->$param) && array_key_exists($param, $_REQUEST)) {
                $this->$param = $_REQUEST[$param];
            }
        }


        // 出力の種類を判別
        $response = $this->getResponseType($this->offset, $this->count, $this->path.'/'.$this->name);

        // 出力内容を設定
        $download = new HTTP_Download();
        if ($response == HTTP_Download_Mobile_EZget::RESPONSE_DOWNLOADING) {
            $body = '';
            $filename = $this->path.'/'.$this->name;
            if ($fp = fopen($filename, 'rb')) {
                fseek($fp, $this->offset);
                $body = fread($fp, $this->count);
                fclose($fp);
            }

            $download->setContentType('application/x-up-download');
            $download->setData($body);
        } elseif ($response == HTTP_Download_Mobile_EZget::RESPONSE_DOWNLOADEMPTY) {
            $download->setContentType('application/x-up-download');
            $download->setData('');
        } else {
            if ($response == HTTP_Download_Mobile_EZget::RESPONSE_COMPLETED) {
                $task = 'return';
            } else {
                $task = 'cancel';
            }

            $msg = $this->getResponseMessage($response);
            $body = sprintf('<hdml version=3.0 ttl="0" public=true>'."\n".
                            '<display>'."\n".
                            '<action type=accept task=%s>'."\n".
                            '<wrap>%s</wrap>'."\n".
                            '</display>'."\n".
                            '</hdml>'."\n",
                            $task, $msg);
            $body = mb_convert_encoding($body, 'SJIS', 'UTF-8');

            $download->setContentType('text/x-hdml;charset=Shift_JIS');
            $download->setData($body);
        }

        $download->setCache(false);

        return $download;
    }

    /**
     * レスポンスの種類を判別
     */
    public function getResponseType($offset, $count, $filename)
    {
        if ($offset >= 0 && $count > 0) {
            if (file_exists($filename)) {
                $response_type = HTTP_Download_Mobile_EZget::RESPONSE_DOWNLOADING;
            } else {
                $response_type = HTTP_Download_Mobile_EZget::RESPONSE_FILENOTFOUND;
            }
        } elseif ($offset == 0 && $count == 0) {
            $response_type = HTTP_Download_Mobile_EZget::RESPONSE_DOWNLOADEMPTY;
        } elseif ($offset == -1 && $count == -1) {
            $response_type = HTTP_Download_Mobile_EZget::RESPONSE_COMPLETED;
        } elseif ($offset == -1 && $count == -2) {
            $response_type = HTTP_Download_Mobile_EZget::RESPONSE_FAILED;
        } else {
            $response_type = HTTP_Download_Mobile_EZget::RESPONSE_UNKNOWN;
        }

        return $response_type;
    }

    /**
     * レスポンスの出力
     */
    public function send()
    {
        return $this->getDownload()->send(false);
    }

    /**
     * 各値を初期値にリセットする
     */
    public function reset()
    {
        $this->path   = '';
        $this->name   = '';
        $this->offset = 0;
        $this->count  = 0;

        $this->messages = array(
            HTTP_Download_Mobile_EZget::RESPONSE_UNKNOWN       => 'エラーが発生しました',
            HTTP_Download_Mobile_EZget::RESPONSE_DOWNLOADING   => null,
            HTTP_Download_Mobile_EZget::RESPONSE_DOWNLOADEMPTY => null,
            HTTP_Download_Mobile_EZget::RESPONSE_COMPLETED     => 'ダウンロード成功しました',
            HTTP_Download_Mobile_EZget::RESPONSE_FAILED        => 'ダウンロード失敗しました',
            HTTP_Download_Mobile_EZget::RESPONSE_FILENOTFOUND  => 'ファイルが見つかりません',
        );
    }

    /**
     * 終了時のメッセージを取得する
     */
    public function getResponseMessage($response)
    {
        return $this->messages[$response];
    }

    /**
     * 終了時のメッセージを設定する
     */
    public function setResponseMessage($response, $message)
    {
        return $this->messages[$response] = $message;
    }
}
