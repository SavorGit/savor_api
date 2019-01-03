<?php
namespace Smallappsimple\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class UserController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'isRegister':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001);
                break;
        }
        parent::_init_();
    }
    public function isRegister(){
        $openid = $this->params['openid'];
        
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $userinfo = $m_user->getOne('id user_id,openid,avatarUrl,nickName,gender,status,is_wx_auth', $where);
        $data = array();
        if(empty($userinfo)){
            $data['openid'] = $openid;
            $data['small_app_id'] = 2;
            $data['status'] = 1;
            $m_user->addInfo($data);
            $userinfo['openid'] = $openid;
        }
        $data['userinfo'] = $userinfo;
        
        $this->to_back($data);
    } 
}