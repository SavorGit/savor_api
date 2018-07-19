<?php
namespace Smallapp\Controller;
use Think\Controller;
use Common\Lib\Smallapp_api;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class IndexController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getOssParams':
                $this->is_verify = 0;
            break;
            case 'getBoxQr':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>'1001');
            break;
            case 'getOpenid':
                $this->is_verify  =1;
                $this->valid_fields = array('code'=>1001);
            break;
            case 'recordForScreenPics':
                
        }
        parent::_init_();
    }
    /**
     * 获取OSS资源上传的配置初始化参数
     *
     */
    public function getOssParams(){
        $id = C('OSS_ACCESS_ID');
        $key = C('OSS_ACCESS_KEY');
        //$host = 'http://'.C('OSS_BUCKET').'.'.C('OSS_HOST');
        $host = 'https://'.C('OSS_HOST');
        $callbackUrl = C('HOST_NAME').'/'.C('OSS_SYNC_CALLBACK_URL');
        $callback_param = array(
            'callbackUrl'=>$callbackUrl,
            'callbackBody'=>'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType'=>"application/x-www-form-urlencoded"
        );
        $callback_string = json_encode($callback_param);
        $base64_callback_body = base64_encode($callback_string);
        $now = time();
        $expire = 30; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = $this->_gmt_iso8601($end);
    
        $rand = rand(10,99);
        	
        //资源空间的目录前缀
        $dir = C('OSS_FORSCREEN_ADDR_PATH');
    
        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition;
    
        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0=>'starts-with', 1=>'$key', 2=>$dir);
        $conditions[] = $start;
        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));
    
        $response              = array();
        $response['accessid']  = $id;
        $response['host']      = $host;
        $response['policy']    = $base64_policy;
        $response['signature'] = $signature;
        $response['expire']    = $end;
        $response['callback']  = $base64_callback_body;
        //这个参数是设置用户上传指定的前缀
        $response['dir']       = $dir;
        echo json_encode($response);
        exit;
    }
    /**
     * @des  获取当前机顶盒小程序码
     */
    public function getBoxQr(){
        $box_mac = $this->params['box_mac'];
        $m_small_app = new Smallapp_api();
        $tokens  = $m_small_app->getWxAccessToken();
        header('content-type:image/gif');
        $data = array();
        $data['scene'] = $box_mac;//自定义信息，可以填写诸如识别用户身份的字段，注意用中文时的情况
        $data['page'] = "pages/forscreen/forscreen";//扫描后对应的path
        $data['width'] = 400;//自定义的尺寸
        $data['auto_color'] = false;//是否自定义颜色
        $color = array(
            "r"=>"221",
            "g"=>"0",
            "b"=>"0",
        );
        $data['line_color'] = $color;//自定义的颜色值
        $data['is_hyaline'] = false;
        $data = json_encode($data);
        $m_small_app->getSmallappCode($tokens,$data);
    }
    /**
     *@desc 获取openid
     */
    public function getOpenid(){
        $code = $this->params['code'];
        $m_small_app = new Smallapp_api();
        $data  = $m_small_app->getSmallappOpenid($code);
        $this->to_back($data);
        
    }
    /**
     * @desc 发送随机验证码给电视
     */
    public function genCode(){
        $box_mac = $this->params['box_mac'];
        $openid    = $this->params['openid'];
        $code = rand(100, 999);
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SMALLAPP_CHECK_CODE');
        $cache_key .= $box_mac.':'.$openid;
        $info = $redis->get($cache_key);
        if(empty($info)){
            $info = array();
            $info['is_have'] = 0;
            $info['code'] = $code;
            $redis->set($cache_key, json_encode($info),7200);
            echo json_encode($info);
            exit;
        }else {
            echo $info;
            exit;
        }
        
    }
    /**
     * @desc 查看是否有验证码
     */
    public function isHaveCode(){
        $box_mac = $this->params['box_mac'];
        $openid  = $this->params['openid'];
        $code    = $this->params['code'];
        $redis   = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SMALLAPP_CHECK_CODE');
        $cache_key .= $box_mac.':'.$openid;
        $info = $redis->get($cache_key);
        $result = array();
        if(empty($info)){
            $result['is_have'] = 0;
        }else {
            $info = json_decode($info,true);
            
            $result['is_have'] = $info['is_have'];
        }
        echo json_encode($result);
        exit;
    }
    /**
     * @param 获取该机顶盒该用户的随机码
     */
    public function checkcode(){
        $code = $this->params['code'];
        $box_mac = $this->params['box_mac'];
        $openid  = $this->params['openid'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SMALLAPP_CHECK_CODE');
        $cache_key .= $box_mac.':'.$openid;
        $info = $redis->get($cache_key);
        $result = array();
        if(empty($info)){//没有该用户的记录
            $result['is_right'] = 0;
            echo json_encode($result);
            exit;
        }else {
            $info = json_decode($info,true);
            if($code==$info['code']){//验证码一致
                $info['is_have'] = 1;
                $redis->set($cache_key, json_encode($info),7200);
                $result['is_right'] =2;
                echo json_encode($result);
                exit;
            }else {//验证码不一致
                $result['is_right'] =1;
                echo json_encode($result);
                exit;
            }
        }
    }
    /**
     * @desc 记录用户投屏的图片
     */
    public function recordForScreenPics(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $imgs    = $this->params['imgs'];
        
        $data = array();
        $data['openid'] = $openid;
        $data['box_mac']= $box_mac;
        $data['imgs']   = $imgs;
        $data['create_time'] = date('Y-m-d H:i:s');
        $m_smallapp_forscreen_record = new \Common\Model\ForscreenRecordModel();  
        $ret = $m_smallapp_forscreen_record->addInfo($data);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back();
        }
        
        
    }
    
    private function _gmt_iso8601($time){
        $dtStr = date("c", $time);
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }
}