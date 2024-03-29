<?php
namespace Smallappdata\Controller;
use \Common\Controller\CommonController as CommonController;

class UserController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'bindAuthMobile':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'session_key'=>1001,'iv'=>1001,'encryptedData'=>1001);
                break;
            case 'getSessionkey':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
        }
        parent::_init_();
    }

    public function bindAuthMobile(){
        $openid = $this->params['openid'];
        $encryptedData = $this->params['encryptedData'];
        if(!empty($encryptedData['phoneNumber'])){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $where = array('openid'=>$openid);
            $m_user->updateInfo($where, array('mobile'=>$encryptedData['phoneNumber']));
        }
        $this->to_back($encryptedData);
    }

    public function getSessionkey(){
        $openid = $this->params['openid'];
        $cache_key = C('SAPP_DATA').'session_openid:'.$openid;
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(17);
        $res_session = $redis->get($cache_key);
        $session_key = '';
        if(!empty($res_session)){
            $session_key = $res_session;
        }
        $this->to_back(array('session_key'=>$session_key));
    }
}