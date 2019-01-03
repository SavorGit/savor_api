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
            $info = $info[0];
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
}