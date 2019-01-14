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
                $this->valid_fields = array('mobile'=>1001,'openid'=>1001,'invite_code'=>1001,'verify_code'=>1001);
                break;
        }
        parent::_init_();
    }
    
    public function login(){
        $mobile = intval($this->params['mobile']);
        $openid = $this->params['openid'];
        $verify_code = trim($this->params['verify_code']);
        $invite_code = trim($this->params['invite_code']);//邀请码
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
        if($invite_code_info['state']==0){
            $where = array('code'=>$invite_code,'flag'=>0);
            $data = array('state'=>1,'bind_mobile'=>$mobile);
            $data['bind_time'] = date('Y-m-d H:i:s');
            $m_hotel_invite_code->saveInfo($where,$data);
        }
        if($verify_code){
            $redis->remove($cache_key);
        }
        unset($invite_code_info['state']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('mobile'=>$mobile,'small_app_id'=>4,'openid'=>$openid,'status'=>0);
        $userinfo = $m_user->getOne('id as user_id,openid,mobile', $where);
        if(empty($userinfo)){
            $data = array('mobile'=>$mobile,'small_app_id'=>4,'openid'=>$openid);
            $data['status'] = 1;
            $res = $m_user->addInfo($data);
            if(!$res){
                $this->to_back(92007);
            }
            $userinfo = array('user_id'=>$res,'openid'=>$openid,'mobile'=>$mobile);
        }
        $userinfo['hotel_id'] = $invite_code_info['hotel_id'];
        $userinfo['hotel_name'] = $invite_code_info['hotel_name'];
        $this->to_back($userinfo);
    }
}