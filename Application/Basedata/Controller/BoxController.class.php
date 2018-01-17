<?php
namespace BaseData\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;
class BoxController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'reportDeviceToken':
                $this->is_verify = 1;
                $this->valid_fields = array('box_id'=>1001,'box_mac'=>1001,'device_token'=>1001);
                break;
        }
        parent::_init_();
    }
    public function reportDeviceToken(){
        $box_id = $this->params['box_id'];
        $box_mac = $this->params['box_mac'];
        $device_token = $this->params['device_token'];
        $m_box = new \Common\Model\BoxModel();
        $where = array();
        $where['id'] = $box_id; 
        $where['state'] = 1;
        $where['flag']  = 0;
        $box_info = $m_box->getOnerow($where);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        if($box_info['mac'] != $box_mac){
            $this->to_back(70002);
        }
        if($box_info['device_token'] == $device_token){
            $this->to_back(10000);
        }
        $data = array();
        $data['device_token'] = $device_token;
        $ret = $m_box->saveData($data, $where);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(70003);
        }
    }
    
}