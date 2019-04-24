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
            case 'isRegister':  //极简版未更换appid
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'register':    //新极简版
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'avatarUrl'=>1000,'nickName'=>1000,'gender'=>1000);
                break;
            case 'isJjRegister': //新极简版
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'registerCom':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'avatarUrl'=>1000,
                    'nickName'=>1000,'gender'=>1000,
                    'session_key'=>1001,'iv'=>1001,
                    'encryptedData'=>1001,
                );
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
    
    public function isJjRegister(){
        $openid = $this->params['openid'];
    
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $userinfo = $m_user->getOne('id user_id,openid,avatarUrl,nickName,gender,status,is_wx_auth', $where);
        $data = array();
        if(empty($userinfo)){
            $data['openid'] = $openid;
            $data['small_app_id'] = 3;
            $data['status'] = 1;
            $m_user->addInfo($data);
            $userinfo['openid'] = $openid;
        }
        $data['userinfo'] = $userinfo;
    
        $this->to_back($data);
    }
    public function register(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $nums = $m_user->countNum($where);
        if(empty($nums)){
            $data['openid']    = $openid;
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['is_wx_auth']= 2;
            $data['small_app_id'] = 3;
            $m_user->addInfo($data);
            $this->to_back($data);
        }else {
            $data = array();
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['is_wx_auth']= 2;
            $data['small_app_id'] = 3;
            $m_user->updateInfo($where, $data);
            $data['openid'] = $openid;
            $this->to_back($data);
        }
    }
    public function registerCom(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $nums = $m_user->countNum($where);
        $encryptedData = $this->params['encryptedData'];
        if(empty($nums)){
            $data['openid']    = $openid;
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['unionId']   = $encryptedData['unionId'];
            $data['is_wx_auth']= 3;
            $data['small_app_id'] = 3;
            $m_user->addInfo($data);
            $this->to_back($data);
        }else {
            $data = array();
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['unionId']   = $encryptedData['unionId'];
            $data['is_wx_auth']= 3;
            $data['small_app_id'] = 3;
            $m_user->updateInfo($where, $data);
            $data['openid'] = $openid;
            $this->to_back($data);
        }
    }
}