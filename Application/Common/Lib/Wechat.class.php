<?php
namespace Common\Lib;

class Wechat{

    private $appid;
    private $appsecret;
    private $cacheprefix;
    private $url_oauth = 'https://open.weixin.qq.com/connect/oauth2/authorize';
    private $url_oauth_token = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    private $url_access_token = 'https://api.weixin.qq.com/cgi-bin/token';
    private $url_getticket = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';
    private $url_get_userinfo = 'https://api.weixin.qq.com/sns/userinfo';
    private $url_userinfo = 'https://api.weixin.qq.com/cgi-bin/user/info';
    private $url_templatesend = 'https://api.weixin.qq.com/cgi-bin/message/template/send';

    public function __construct($config=array()){
        if(!empty($config)){
            $wx_config = $config;
        }else{
            $wx_config = C('WX_MP_CONFIG');
            $wx_config = array('cache_key'=>'wxmp','appid'=>'wxcb1e088545260931','appsecret'=>'9f1ebb78d1dc7afe73dcb22a135cfcf9');
        }
        $this->cacheprefix = $wx_config['cache_key'];
        $this->appid = $wx_config['appid'];
        $this->appsecret = $wx_config['appsecret'];
    }

    /*
     * 微信授权
     * scopen snsapi_base snsapi_userinfo
     */
    public function wx_oauth($url,$scope='snsapi_userinfo'){
        $appid = $this->appid;
        $uri = urlencode($url);
        $state = 'savorwx';
        $wx_url = $this->url_oauth."?appid=$appid&redirect_uri=$uri&response_type=code&scope=$scope&state=$state#wechat_redirect";
        header("Location:".$wx_url);
    }

    /**
     * 页面显示微信分享配置信息
     */
    public function showShareConfig($url,$title,$desc='',$link='',$jump_link=''){
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
     * 微信token
     * @return Ambigous <mixed, string>
     */
    public function getWxAccessToken(){
        $key_token = $this->cacheprefix.'savor_wxtoken';
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(15);
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

    public function getWxUserDetail($access_token ,$openid){
        $url = $this->url_userinfo."?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
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

    /*
     * $data = array(
            'touser'=>$res['openid'],
            'template_id'=>"8HdJeBWn7ZmpKWYQgH17A5ZaD75CxL8zrFcNoTzmDqg",
            'url'=>"",
            'miniprogram'=>array(
                'appid'=>'wxfdf0346934bb672f',
                'pagepath'=>'pages/index/index',
            ),
            'data'=>array(
                'first'=>array('value'=>'您好，您的会员积分信息有了新的变更。') ,
                'keyword1'=>array('value'=>$res['nickname']),
                'keyword2'=>array('value'=>6009891111),
                'keyword3'=>array('value'=>300,),
                'keyword4'=>array('value'=>1200),
                'remark'=>array('value'=>'如有疑问，请拨打123456789.','color'=>"#FF1C2E"),
            )
        );
     */
    public function templatesend($data){
        $access_token = $this->getWxAccessToken();
        $url = $this->url_templatesend."?access_token=".$access_token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0); //过滤HTTP头
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
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
        $key_ticket = $this->cacheprefix.'savor_wxjsticket';
        $redis = new \Common\Lib\SavorRedis();
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
}
