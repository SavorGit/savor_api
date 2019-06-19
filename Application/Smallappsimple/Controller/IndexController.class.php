<?php
namespace Smallappsimple\Controller;
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
            
            case 'getOpenid':
                $this->is_verify  =1;
                $this->valid_fields = array('code'=>1001);
            break;
            case 'getJjOpenid': //极简版openid
                $this->is_verify  =1;
                $this->valid_fields = array('code'=>1001);
                break;
            case 'getHotelInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
            break;
            case 'getBoxWifi':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
            break;
            case 'getInnerIp':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
            break;
            case 'getBoxQr':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
            case 'recordWifiErr':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
        }
        parent::_init_();
    }
    
    /**
     *@desc 获取openid
     */
    public function getOpenid(){
        $code = $this->params['code'];
        $m_small_app = new Smallapp_api($flag = 2);
        $data  = $m_small_app->getSmallappOpenid($code);
        $this->to_back($data);
    }
    /**
     *@desc 获取新极简版openid
     */
    public function getJjOpenid(){
        $code = $this->params['code'];
        $m_small_app = new Smallapp_api($flag = 3);
        $data  = $m_small_app->getSmallappOpenid($code);
        $this->to_back($data);
    }
    /**
     * @desc 获取酒楼信息
     */
    public function getHotelInfo(){
        $box_mac = $this->params['box_mac'];
        $m_box = new \Common\Model\BoxModel();
        $info = array();
        
        $fields  = 'd.name hotel_name,c.name room_name,a.wifi_name,a.wifi_password,a.wifi_mac,a.is_open_simple';
        $where = array();
        $where['d.state'] = 1;
        $where['d.flag']  = 0;
        $where['a.state'] = 1;
        $where['a.flag']  = 0;
        $where['a.mac']   = $box_mac;
        $info = $m_box->getBoxInfo($fields,$where);
        if(empty($info)){
            $this->to_back(70001);
        }else {
            $redis = SavorRedis::getInstance();
            $redis->select(13);
            $cache_key = 'heartbeat:2:'.$box_mac;
            $data = $redis->get($cache_key);
            $intranet_ip = '';
            if(!empty($data)){
                $data = json_decode($data,true);
                $intranet_ip = $data['intranet_ip'];
            }
            $info = $info[0];
            $info['intranet_ip'] = $intranet_ip;
            $this->to_back($info);
        }
    }

    public function getInnerIp(){
        $box_mac = $this->params['box_mac'];
        
        $redis = SavorRedis::getInstance();
        $redis->select(13);
        $cache_key = 'heartbeat:2:'.$box_mac;
        $data = $redis->get($cache_key);
        if(!empty($data)){
            $data = json_decode($data,true);
            $this->to_back($data);
        }else {
            $m_heart_log =  new \Common\Model\HeartLogModel();
            
        }
    }
    /**
     * @des  获取当前机顶盒小程序码
     */
    public function getBoxQr(){
        $box_mac = $this->params['box_mac'];
        $r = $this->params['r'] !='' ? $this->params['r'] : 255;
        $g = $this->params['g'] !='' ? $this->params['g'] : 255;
        $b = $this->params['b'] !='' ? $this->params['b'] : 255;
        $m_small_app = new Smallapp_api(3);
        $tokens  = $m_small_app->getWxAccessToken();
        header('content-type:image/png');
        $data = array();
        $data['scene'] = $box_mac;//自定义信息，可以填写诸如识别用户身份的字段，注意用中文时的情况
        $data['page'] = "pages/index/index";//扫描后对应的path
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
    public function recordWifiErr(){
        $box_mac = $this->params['box_mac'];
        $err_info = str_replace('\\', '', $this->params['err_info']);
        $m_err_info = new \Common\Model\Smallapp\WifiErrModel();
        $data['box_mac'] = $box_mac;
        $data['err_info'] = $err_info;
        $m_err_info->addInfo($data);
    }
}