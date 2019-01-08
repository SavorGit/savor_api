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
                $this->valid_fields = array('box_mac'=>'1001','r'=>1000,'g'=>1000,'b'=>1000);
            break;
            case 'getOpenid':
                $this->is_verify  =1;
                $this->valid_fields = array('code'=>1001);
            break;
            case 'recordForScreenPics':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1000,
                                            'imgs'=>1001,'mobile_brand'=>1000,
                                            'mobile_model'=>1000,'action'=>1000,
                                            'resource_type'=>1000,'resource_id'=>1000,
                                            'is_pub_hotelinfo'=>1000,'is_share'=>1000,
                                            'forscreen_id'=>1000
                );
            break;
            case 'getHotelInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
            break;
            case 'isSmallappForscreen':
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1001,'versionCode'=>1000);
                break;
            case 'getBoxProgramList':    //获取该机顶盒下的节目单列表 在小程序中展示
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
            case 'getBirthdayMedia':
                $this->is_verify = 0;
                break;
            case 'isHaveCallBox':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'delCallCode':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'openid'=>1001);
                break;
            case 'test':
                $this->is_verify = 0;
                break;
                
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
        $r = $this->params['r'] !='' ? $this->params['r'] : 255;
        $g = $this->params['g'] !='' ? $this->params['g'] : 255;
        $b = $this->params['b'] !='' ? $this->params['b'] : 255;
        $m_small_app = new Smallapp_api();
        $tokens  = $m_small_app->getWxAccessToken();
        header('content-type:image/png');
        $data = array();
        $data['scene'] = $box_mac;//自定义信息，可以填写诸如识别用户身份的字段，注意用中文时的情况
        $data['page'] = "pages/forscreen/forscreen";//扫描后对应的path
        $data['width'] = "280";//自定义的尺寸
        $data['auto_color'] = false;//是否自定义颜色
        $color = array(
            "r"=>$r,
            "g"=>$g,
            "b"=>$b,
        );
        $data['line_color'] = $color;//自定义的颜色值
        $data['is_hyaline'] = true;
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
     * @desc 记录用户投屏的图片、视频
     */
    public function recordForScreenPics(){
        $forscreen_id = $this->params['forscreen_id'] ? $this->params['forscreen_id'] :0;
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $mobile_brand = $this->params['mobile_brand'];
        $mobile_model = $this->params['mobile_model'];
        $forscreen_char = $this->params['forscreen_char'];
        $imgs    = str_replace("\\", '', $this->params['imgs']);
        $action  = $this->params['action'] ? $this->params['action'] : 0;
        $resource_type = $this->params['resource_type'] ? $this->params['resource_type'] : 0;
        $resource_id   = $this->params['resource_id'] ? $this->params['resource_id'] : 0;
        $resource_size = $this->params['resource_size'] ? $this->params['resource_size'] :0;
        $res_sup_time  = $this->params['res_sup_time'] ? $this->params['res_sup_time'] : 0;
        $res_eup_time  = $this->params['res_eup_time'] ? $this->params['res_eup_time'] : 0;
        $is_pub_hotelinfo = $this->params['is_pub_hotelinfo'] ?$this->params['is_pub_hotelinfo']:0;
        $is_share      = $this->params['is_share'] ? $this->params['is_share'] : 0;
        $duration      = $this->params['duration'] ? $this->params['duration'] : 0.00;
        $data = array();
        $data['forscreen_id'] = $forscreen_id;
        $data['openid'] = $openid;
        $data['box_mac']= $box_mac;
        $data['action'] = $action;
        $data['resource_type'] = $resource_type;
        $data['resource_id']   = $resource_id;
        $data['mobile_brand'] = $mobile_brand;
        $data['mobile_model'] = $mobile_model;
        $data['imgs']   = $imgs;
        $data['forscreen_char'] = !empty($forscreen_char) ? $forscreen_char : '';
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['res_sup_time']= $res_sup_time;
        $data['res_eup_time']= $res_eup_time;
        $data['resource_size'] = $resource_size;
        $data['is_pub_hotelinfo'] = $is_pub_hotelinfo;
        $data['is_share']    = $is_share;
        $data['duration']    = $duration;
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_SCRREN').":".$box_mac;
        
        $redis->rpush($cache_key, json_encode($data));
        
        if(!empty($is_share)){
            $map = array();
            $map['forscreen_id'] = $forscreen_id;
            $map['openid'] = $openid;
            $map['box_mac']= $box_mac;
            if($action==4){
                $map['res_type'] = 1;
            }else if($action==2 && $resource_type==2){
                $map['res_type'] = 2;
                $map['duration'] = $duration;
            }
            $map['resource_size'] = $resource_size;
            $map['resource_id']   = $resource_id;
            $forscreen_res = json_decode($imgs,true);
            
            $map['res_url']   = $forscreen_res[0]; 
            $map['is_pub_hotelinfo'] =$is_pub_hotelinfo;
            
            $cache_key = C('SAPP_SCRREN_SHARE').$box_mac.':'.$openid.":".$forscreen_id;
            $redis->rpush($cache_key, json_encode($map));
        }
        $this->to_back(10000);
    }
    /**
     * @desc 
     */
    public function getHotelInfo(){
        $box_mac = $this->params['box_mac'];
        $m_box = new \Common\Model\BoxModel();
        $info = array();
        $info = $m_box->getHotelInfoByBoxMacNew($box_mac);
        $info['vedio_url'] = 'http://oss.littlehotspot.com/media/resource/jda24z7C8Z.mp4';
        $info['file_name'] = 'jda24z7C8Z.mp4';
        $info['name']     = 'Happy Birthday';
        $this->to_back($info);
        
    }
    /**
     * @DESC 判断机顶盒是否支持小程序投屏
     */
    public function isSmallappForscreen(){
        $box_min_version_code = C('SAPP_FORSCREEN_VERSION_CODE');
        $box_mac = $this->params['box_mac'];
        $versionCode = intval($this->params['versionCode']);
        $data = array();
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $is_support_nett = $redis->get('support_netty_balance');
        if(empty($versionCode)|| $versionCode <$box_min_version_code ){  //上线前替换1234
            $data['is_sapp_forscreen'] = 0;
            $data['support_netty_balance'] =  $is_support_nett;
            $this->to_back($data);
        }else if($versionCode>=$box_min_version_code){                   //上线前替换1234
            $m_box = new \Common\Model\BoxModel();
            $where = array();
            $where['mac'] = $box_mac;
            $where['state'] = 1;
            $where['flag']  = 0 ;
            $box_info = $m_box->getOnerow($where);
            if(empty($box_info)){
                $this->to_back(70001);
            }
            
            $data['is_sapp_forscreen'] = intval($box_info['is_sapp_forscreen']);
            $data['support_netty_balance'] =  $is_support_nett;
            $this->to_back($data);
        }        
    }
    /**
     * @desc 
     */
    public function getBirthdayMedia(){
        $data['vedio_url'] = 'http://oss.littlehotspot.com/media/resource/jda24z7C8Z.mp4';
        $data['file_name'] = 'jda24z7C8Z.mp4';
        $this->to_back($data);
    }
    /**
     * @desc 判断该用户两个小时内是否有过呼码
     */
    public function isHaveCallBox(){
        $openid = $this->params['openid'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $key = C('SMALLAPP_CHECK_CODE')."*".$openid;
        $keys = $redis->keys($key);
        if(!empty($keys)){
            $keys = $keys[0];
            $key_arr = explode(':', $keys);
            $box_mac = $key_arr['2'];
            $m_box = new \Common\Model\BoxModel();
            $map = array();
            $map['a.mac'] = $box_mac;
            $map['a.flag']=0;
            $map['a.state'] =1;
            $map['d.flag'] =0;
            $map['d.state'] = 1;
            $rets = $m_box->getBoxInfo('c.name room_name,d.name hotel_name,a.is_open_simple', $map);
            $hotel_info = $rets[0];
            $code_info = $redis->get($keys);
            $code_info = json_decode($code_info,true);
            $this->to_back(array('is_have'=>$code_info['is_have'],
                                 'box_mac'=>$box_mac,'hotel_name'=>$hotel_info['hotel_name'],
                                 'room_name'=>$hotel_info['room_name'],
                                 'is_open_simple'=>$hotel_info['is_open_simple']
                                )
                          );
        }else {
            $this->to_back(array('is_have'=>0));
        }
        
    } 
    /**
     * @desc 断开连接删除投屏呼玛缓存
     */
    public function delCallCode(){
        $box_mac = $this->params['box_mac'];
        $openid  = $this->params['openid'];
        $cache_key = C('SMALLAPP_CHECK_CODE').$box_mac.":".$openid;
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $redis->remove($cache_key);
        $this->to_back(10000);
    }
    public function test(){
        $data['employId'] = 1;
        $this->to_back($data);
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