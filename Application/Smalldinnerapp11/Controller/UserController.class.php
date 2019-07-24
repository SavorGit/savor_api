<?php
namespace Smalldinnerapp11\Controller;
use \Common\Controller\CommonController;
use Common\Lib\Smallapp_api;
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
            case 'register':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'avatarUrl'=>1000,'nickName'=>1000,
                    'gender'=>1000);
                break;
            case 'refuseRegister':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'getOpenid': 
                $this->is_verify  =1;
                $this->valid_fields = array('code'=>1001);
                break;
            case 'checkUser':
                $this->is_verify  =1;
                $this->valid_fields = array('mobile'=>1001);
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
    /**
     * @desc 判断是否注册用户
     */
    public function isRegister(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 4;
        $userinfo = $m_user->getOne('id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth', $where);
        $data = array();
        if(empty($userinfo)){
            $data['openid'] = $openid;
            $data['status'] = 1;
            $m_user->addInfo($data);
            $userinfo['openid'] = $openid;
            $userinfo['is_wx_auth'] = 0;
        }
        if(!empty($userinfo['mobile'])){
            $m_hotel_invite_code = new \Common\Model\Smallapp\HotelInviteCodeModel();
            $rts = $m_hotel_invite_code->field('hotel_id')->where(array('bind_mobile'=>$userinfo['mobile'],'flag'=>0))->find();
            $userinfo['hotel_id'] = $rts['hotel_id'];
        }
        $data['userinfo'] = $userinfo;
        $this->to_back($data);
    }
    /**
     * @desc 注册用户
     */
    public function register(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 4;
        $userinfo = $m_user->getOne('openid,mobile', $where);
        //$nums = $m_user->countNum($where);
        if(empty($userinfo)){
            $data['openid']    = $openid;
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['is_wx_auth']= 2;
            $data['small_app_id'] = 4;
            $m_user->addInfo($data);
            $this->to_back($data);
        }else {
            $data = array();
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['is_wx_auth']= 2;
            $data['small_app_id'] = 4;
            $m_user->updateInfo($where, $data);
            $data['openid'] = $openid;
            $data['mobile'] = $userinfo['mobile'];
            $this->to_back($data);
        }
        
    }
    /**
     * @desc 拒绝授权
     */
    public function refuseRegister(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $data = array();
        $data['is_wx_auth'] = 1;
    
        $ret = $m_user->updateInfo($where, $data);
        if($ret){
    
            $this->to_back(10000);
        }else {
            $this->to_back(91015);
        }
    
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
    public function registerCom(){
        /*$openid = $this->params['openid'];
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
            $data['small_app_id'] = 4;
            $m_user->addInfo($data);
            $this->to_back($data);
        }else {
            $data = array();
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['unionId']   = $encryptedData['unionId'];
            $data['is_wx_auth']= 3;
            $data['small_app_id'] = 4;
            $m_user->updateInfo($where, $data);
            $data['openid'] = $openid;
            $this->to_back($data);
        }*/
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 4;
        $userinfo = $m_user->getOne('openid,mobile', $where);
        $encryptedData = $this->params['encryptedData'];
        //$nums = $m_user->countNum($where);
        if(empty($userinfo)){
            $data['openid']    = $openid;
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['unionId']   = $encryptedData['unionId'];
            $data['is_wx_auth']= 3;
            $data['small_app_id'] = 4;
            $m_user->addInfo($data);
            $this->to_back($data);
        }else {
            $data = array();
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['unionId']   = $encryptedData['unionId'];
            $data['is_wx_auth']= 3;
            $data['small_app_id'] = 4;
            $m_user->updateInfo($where, $data);
            $data['openid'] = $openid;
            $data['mobile'] = $userinfo['mobile'];
            $m_hotel_invite_code = new \Common\Model\Smallapp\HotelInviteCodeModel();
            $rts = $m_hotel_invite_code->field('hotel_id')->where(array('bind_mobile'=>$userinfo['mobile'],'flag'=>0))->find();
            $data['hotel_id'] = $rts['hotel_id'];
            $this->to_back($data);
        }
    }
}