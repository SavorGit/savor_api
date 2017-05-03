<?php
namespace Feed\Controller;

use \Common\Controller\BaseController as BaseController;
class FeedbackController extends BaseController{
 	/**
     * 构造函数
     */
    function _init_() {

        $this->valid_fields=array('deviceId'=>'1001');
        switch(ACTION_NAME) {
            case 'feedInsert':
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取分类列表
     */
    public function feedInsert(){

        $save = array();
        $traceinfo = $this->traceinfo;
        $clent_arr  = C('CLIENT_NAME_ARR');
        $save['device_type'] = $clent_arr[$traceinfo['clientname']];
        $save['device_id'] = $this->params['deviceId'];
        $save['suggestion'] = $this->params['suggestion'];
        $save['contact_way'] = $this->params['contactWay'];
        $save['create_time'] = date('Y-m-d H:i:s');
        $feedModel = new \Common\Model\Feed\FeedbackModel();
        $bool = $feedModel->addData($save);
        if($bool) {
            $data = 10000;
        } else {
            $data = 13002;
        }
        $this->to_back($data);
    }
}

