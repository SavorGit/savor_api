<?php
/**
 * @desc 微信小程序接口api
 * @author zhang.yingtao
 * @since  2018-07-06
 */

namespace Common\Lib;

use Common\Lib\SavorRedis;
class Smallapp_api {
	private $appid;
	private $appsecret;
	private $cacheprefix;
	private $url_oauth = 'https://open.weixin.qq.com/connect/oauth2/authorize';
	private $url_oauth_token = 'https://api.weixin.qq.com/sns/oauth2/access_token';
	private $url_access_token = 'https://api.weixin.qq.com/cgi-bin/token';
	private $url_getticket = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';
	private $url_get_userinfo = 'https://api.weixin.qq.com/sns/userinfo';
	private $url_get_smallapp_code = "https://api.weixin.qq.com/wxa/getwxacodeunlimit";

	public function __construct(){
	    $wx_config = C('SMALLAPP_CONFIG');
	    $this->appid = $wx_config['appid'];
	    $this->appsecret = $wx_config['appsecret'];
	}

	/**
	 * 页面显示微信分享配置信息
	 */
	public function showShareConfig($url,$title,$desc='',$link='',$jump_link=''){
		//return array();
		$sign_info = $this->CreateWxJssdkSign($url);
		$result = array();
		$result['appid'] = $this->appid;
		$result['timestamp'] = $sign_info['timestamp'];
		$result['noncestr'] = $sign_info['noncestr'];
		$result['signature'] = $sign_info['signature'];
		$result['share_link'] = $link;
		$result['jump_link'] = $jump_link;
		$result['share_title'] = $title;
		$result['share_desc'] = $desc;
		return $result;
	}

	/**
	 * 微信js签名算法
	 * @return array
	 */
	private function CreateWxJssdkSign($url){
		$noncestr = md5(rand());
		$timestamp = time();
		$jsapi_ticket = $this->getWxJsTicket();
		$sign_str = "jsapi_ticket=$jsapi_ticket&noncestr=$noncestr&timestamp=$timestamp&url=$url";
		$signature = sha1($sign_str);
		return array('noncestr'=>$noncestr,'timestamp'=>$timestamp,'signature'=>$signature);
	}


	/**
	 * 微信JS接口的临时票据
	 * @return Ambigous <mixed, string>
	 */
	private function getWxJsTicket(){
		$key_ticket = 'savor_wxjsticket';
		$redis = SavorRedis::getInstance();
		$redis->select(15);
		$ticket = $redis->get($key_ticket);
		if(empty($ticket)){

			$token = $this->getWxAccessToken();
			$url = $this->url_getticket."?access_token=$token&type=jsapi";
			$re = file_get_contents($url);
			$result = json_decode($re,true);
			if($result['errcode'] == 0){
				$redis->set($key_ticket,$result['ticket'],5400);
				$ticket = $result['ticket'];
			}
		}
		return $ticket;
		 
	}

	/**
	 * 微信token
	 * @return Ambigous <mixed, string>
	 */
	public function getWxAccessToken(){
	    $smallapp_config = C('SMALLAPP_CONFIG');
		$key_token = $smallapp_config['cache_key'];
		$redis = SavorRedis::getInstance();
		$redis->select(5);
		$token = $redis->get($key_token);
		if(empty($token)){

			$appid = $this->appid;
			$appsecret = $this->appsecret;
			$url = $this->url_access_token."?grant_type=client_credential&appid=$appid&secret=$appsecret";
			
			$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL,$url);
    
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    		curl_setopt($ch, CURLOPT_HEADER, 0);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    		$re = curl_exec($ch);

		    curl_close($ch);
			$result = json_decode($re,true);
			if(isset($result['access_token'])){
				$redis->set($key_token,$result['access_token'],3600);
				$token = $result['access_token'];	
			}
		}
		return $token;
	}
	public function getWxOpenid($code,$jumUrl = ''){
    		$appid = $this->appid;
    		$appsecret = $this->appsecret;
    		$url = $this->url_oauth_token."?appid=$appid&secret=$appsecret&code=$code&grant_type=authorization_code";
    		
    		//$re =  file_get_contents($url);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$re = curl_exec($ch);

		curl_close($ch);
		$result = json_decode($re,true);
		if(!is_array($result) || isset($result['errcode'])){
	       if(!empty($jumUrl)){
    	       header("Location: $jumUrl"); 
    		   exit;
    	   }else {
    		   header("Location: $url");
    		   exit;
    	   }
    	}
    	return $result;
    }
    public function getWxUserInfo($access_token ,$openid){
        $url = $this->url_get_userinfo."?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$re = curl_exec($ch);

		curl_close($ch);
        $result = json_decode($re,true);
        if(!is_array($result) || isset($result['errcode'])){
            header("Location: $url");
        }
        return $result;
    }
    /**
     * @desc 获取小程序二维码
     */
    public function getSmallappCode($access_token,$post_data ){
        $url = $this->url_get_smallapp_code."?access_token=".$access_token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //没有这个会自动输出，不用print_r();也会在后面多个1
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        $out = json_decode($output);
        print_r($out);exit;
        return $out;
    }
}
