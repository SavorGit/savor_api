<?php
namespace Common\Lib;
class EmayMessage {
    private $sms_addr = 'www.btom.cn:8080';
    private $sms_send_uri = '/simpleinter/sendSMS';
    private $sms_send_per_uri = '/simpleinter/sendPersonalitySMS';

    public function sendSMS($content,$mobile){
        $timestamp = date("YmdHis");
        $ym_config = C('YM_CONFIG');
        $app_id = $ym_config['appid'];
        $aespwd = $ym_config['appsecret'];

        $sign = $this->signmd5($app_id,$aespwd,$timestamp);
        $data = array(
            "appId" => $app_id,
            "timestamp" => $timestamp,
            "sign" => $sign,
            "mobiles" => $mobile,
            "content" =>  $content,
            "customSmsId" => "10001",
            "extendedCode" => "1234"
        );
        $url = $this->sms_addr.$this->sms_send_uri;
        $resobj = $this->http_request($url, $data);
        return $resobj;
    }

    public function http_request($url, $data){
        $data = http_build_query($data);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    public function signmd5($appId,$secretKey,$timestamp){
        return md5($appId.$secretKey.$timestamp);
    }


}
?>