<?php
namespace Common\Lib;
class AliyunOCR{
    public $data;
    public $accessKeyId = '';
    public $accessKeySecret = '';
    public $url = "http://ocr-api.cn-hangzhou.aliyuncs.com/?";

    public function __construct(){
        date_default_timezone_set("GMT");
        $this->accessKeyId = C('OSS_ACCESS_ID');
        $this->accessKeySecret = C('OSS_ACCESS_KEY');
    }

    public function percentEncode($str){
        $res = urlencode($str);
        $res = preg_replace('/\+/', '%20', $res);
        $res = preg_replace('/\*/', '%2A', $res);
        $res = preg_replace('/%7E/', '~', $res);
        return $res;
    }

    public function computeSignature($parameters, $accessKeySecret){
        ksort($parameters);
        $canonicalizedQueryString = '';
        foreach($parameters as $key => $value) {
            $canonicalizedQueryString .= '&' . $this->percentEncode($key)
                . '=' . $this->percentEncode($value);
        }
        $stringToSign = 'GET&%2F&' . $this->percentencode(substr($canonicalizedQueryString, 1));
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret . '&', true));
        return $signature;
    }

    public function recognizeShoppingReceipt($url){
        //文档地址：https://help.aliyun.com/document_detail/442282.html?spm=a2c4g.442265.0.0.2c0733c30eG3kv
        $this->data = array(
            'Format' => 'json',
            'Version' => '2021-07-07',
            'AccessKeyId' => $this->accessKeyId,
            'SignatureVersion' => '1.0',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce'=> uniqid(),
            'Timestamp' => date('Y-m-d\TH:i:s\Z'),
            'Action' => 'RecognizeShoppingReceipt',
            'Url' => $url,
        );
        $result = $this->http_get();
        return $result;
    }

    public function RecognizeGeneral($url){
        $this->data = array(
            'Format' => 'json',
            'Version' => '2021-07-07',
            'AccessKeyId' => $this->accessKeyId,
            'SignatureVersion' => '1.0',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce'=> uniqid(),
            'Timestamp' => date('Y-m-d\TH:i:s\Z'),
            'Action' => 'RecognizeGeneral',
            'Url' => $url,
        );
        $result = $this->http_get();
        return $result;
    }

    public function RecognizeBasic($url){
        $this->data = array(
            'Format' => 'json',
            'Version' => '2021-07-07',
            'AccessKeyId' => $this->accessKeyId,
            'SignatureVersion' => '1.0',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce'=> uniqid(),
            'Timestamp' => date('Y-m-d\TH:i:s\Z'),
            'Action' => 'RecognizeBasic',
            'Url' => $url,
        );
        $result = $this->http_get();
        return $result;
    }

    private function http_get(){
        $this->data['Signature'] = $this->computeSignature($this->data, $this->accessKeySecret);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url . http_build_query($this->data));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        $res = json_decode($res,true);
        //$res = json_encode($res,JSON_UNESCAPED_UNICODE);
        return $res;
    }
}