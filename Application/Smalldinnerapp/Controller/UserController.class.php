<?php
namespace Smalldinnerapp\Controller;
use \Common\Controller\CommonController;
use Common\Lib\Smallapp_api;
class UserController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getOpenid': 
                $this->is_verify  =1;
                $this->valid_fields = array('code'=>1001);
                break;
            case 'checkUser':
                $this->is_verify  =1;
                $this->valid_fields = array('mobile'=>1001);
                break;
        }
        parent::_init_();
    }
    /**
     *@desc 获取openid
     */
    public function getOpenid(){
        $code = $this->params['code'];
        $m_small_app = new Smallapp_api($flag = 4);
        $data  = $m_small_app->getSmallappOpenid($code);
        $this->to_back($data);
    }
    /**
     * @desc 检查手机号是否分配邀请码
     */
    public function checkUser(){
        $mobile = $this->params['mobile'];
        $m_hotel_invite = new \Common\Model\HotelInviteCodeModel();
        $fields = 'id';
        $where = array();
        $where['bind_mobile'] = $mobile;
        $where['flag']  = 0;
        
        $info = $m_hotel_invite->getOne($fields, $where);
        if(empty($info)){
            $this->to_back(92008);
        }else {
            $this->to_back(10000);
        }
    }
}