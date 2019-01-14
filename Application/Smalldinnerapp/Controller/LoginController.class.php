<?php
namespace Smalldinnerapp\Controller;
use \Common\Controller\CommonController;
class LoginController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'login':
                $this->is_verify = 1;
                $this->valid_fields = array('mobile'=>1001,'invite_code'=>1001,'verify_code'=>1000);
                break;
        }
        parent::_init_();
    }
    
    public function login(){
        $mobile = intval($this->params['mobile']);//手机号
        $verify_code = trim($this->params['verify_code']); //手机验证码
        $invite_code = trim($this->params['invite_code']); //邀请码
        //验证手机格式
        if(!check_mobile($mobile)){
            $this->to_back(92001);
        }

        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = 'smallappdinner_vcode_'.$mobile;
        $cache_verify_code = $redis->get($cache_key);

        if($verify_code != $cache_verify_code){
            $this->to_back(92006);
        }

        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $where = array('a.bind_mobile'=>$mobile,'a.code'=>$invite_code,'a.flag'=>0);
        $invite_code_info = $m_hotel_invite_code->getInfo('a.id invite_id,a.state,b.id hotel_id,b.name hotel_name,c.is_open_customer', $where);
        if(empty($invite_code_info)) {
            $this->to_back(92002);
        }
        $where = array('code'=>$invite_code,'flag'=>0);
        $data = array('state'=>1,'bind_mobile'=>$mobile);
        $data['bind_time'] = date('Y-m-d H:i:s');
        $ret = $m_hotel_invite_code->saveInfo($where,$data);
        if($ret){
            if($verify_code){
                $redis->remove($cache_key);
            }
            unset($invite_code_info['state']);
            $this->to_back($invite_code_info);
        }else {
            $this->to_back(92007);
        }
    }



}