<?php
/**
 * @desc 微信小程序接口api
 * @author zhang.yingtao
 * @since  2018-07-06
 */

namespace Common\Lib;

use Common\Lib\SavorRedis;

class WxWork {
    private $url_access_token  = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken';
    private $url_create_chat   = 'https://qyapi.weixin.qq.com/cgi-bin/appchat/create';
    private $url_chat_send     = 'https://qyapi.weixin.qq.com/cgi-bin/appchat/send';
    private $url_webhook       = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=7c9fd694-4e35-483b-8ea4-c7fee8798d3f';
    /**
     * 企业微信token
     * @return Ambigous <mixed, string>
     */
    public function getWxAccessToken(){
       
        $wx_work_config = C('WX_WORK_CONFIG');
        
        
        $key_token = $wx_work_config['cache_key'];
        $redis = SavorRedis::getInstance();
        $redis->select(12);
        $token = $redis->get($key_token);
        if(empty($token)){
            
            $corpid = $wx_work_config['corpid'];
            $corpsecret = $wx_work_config['corpsecret'];
            $url = $this->url_access_token."?corpid=$corpid&corpsecret=$corpsecret";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $re = curl_exec($ch);
            
            curl_close($ch);
            $result = json_decode($re,true);
            $log_content = date("Y-m-d H:i:s").'|result|'.$re."\n";
            $log_file_name = APP_PATH.'Runtime/Logs/wx_work_gettoken'.date("Ymd").".log";
            @file_put_contents($log_file_name, $log_content, FILE_APPEND);
            if(isset($result['access_token'])){
                $redis->set($key_token,$result['access_token'],3600);
                $token = $result['access_token'];
            }
        }
        return $token;
    }
    /**
     * @desc 创建群聊
     */
    public function createChat($access_token,$post_data ){
        $url = $this->url_create_chat."?access_token=".$access_token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //没有这个会自动输出，不用print_r();也会在后面多个1
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        $out = json_decode($output);
        return $out;
    }
    /**
     * @desc 创建群聊
     */
    public function sendChatMsg($access_token,$post_data ){
        $url = $this->url_chat_send."?access_token=".$access_token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //没有这个会自动输出，不用print_r();也会在后面多个1
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        $out = json_decode($output);
        return $out;
    }
    public function sendWebhook($post_data){
        $url = $this->url_webhook;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //没有这个会自动输出，不用print_r();也会在后面多个1
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        $out = json_decode($output);
        return $out;
    }
}