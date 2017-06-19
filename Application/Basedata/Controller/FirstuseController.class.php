<?php
namespace BaseData\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class FirstuseController extends BaseController{
 	/**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'pushData':
                $this->is_verify = 0;
               // $this->valid_fields = array('hotelId'=>1001);
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 记录用户首次使用APP
     */
    public function pushData(){
        $traceinfo = $this->traceinfo;
        $data = array();
        $data['device_id'] = $traceinfo['deviceid'];
        $m_first = new \Common\Model\FirstuseModel();
        $info = $m_first->getOne($data);
        if(!empty($info)){
            $this->to_back(20001);
        }
        $data['location'] = $traceinfo['location'];
        $data['hotel_id'] = $this->params['hotelId'];
        $data['create_time'] = date('Y-m-d H:i:s',time());
        $rt = $m_first->addData($data);
        if($rt){
            $this->to_back(10000);
        }else {
            $this->to_back(20002);
        }   
    }
}