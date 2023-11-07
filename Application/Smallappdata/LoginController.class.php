<?php
namespace Smallappdata\Controller;
use \Common\Controller\CommonController;
use Common\Lib\Smallapp_api;

class LoginController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'isLogin':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'getOpenid':
                $this->is_verify = 1;
                $this->valid_fields = array('code'=>1001);
                break;
            case 'userLogin':
                $this->is_verify = 1;
                $this->valid_fields = array('mobile'=>1001,'openid'=>1001);
                break;
        }
        parent::_init_();
    }

    public function userLogin(){
        $mobile = $this->params['mobile'];
        $openid = $this->params['openid'];
        
        if(!check_mobile($mobile)){//验证手机格式
            $this->to_back(92001);
        }

        $m_vintner = new \Common\Model\VintnerModel();
        $res_vintner = $m_vintner->getInfo(array('mobile'=>$mobile,'status'=>1));
        if(empty($res_vintner)){
            $this->to_back(95001);
        }
        $res_vintner = $m_vintner->getInfo(array('openid'=>$openid,'status'=>1));
        if(!empty($res_vintner)){
            $this->to_back(95002);
        }
        $m_vintner->updateData(array('id'=>$res_vintner['id']),array('openid'=>$openid,'update_time'=>date('Y-m-d H:i:s')));

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>7);
        $userinfo = $m_user->getOne('id,openid,mobile,role_id', $where);
        $data = array('openid'=>$openid,'avatarUrl'=>'','nickName'=>'','gender'=>0,'mobile'=>$mobile,
            'is_wx_auth'=>0,'small_app_id'=>7,'status'=>1);
        if(empty($userinfo)){
            $m_user->addInfo($data);
        }else{
            $m_user->updateInfo(array('id'=>$userinfo['id']), $data);
        }
        $this->to_back($data);
    }

    public function isLogin(){
        $openid = $this->params['openid'];

        $m_vintner = new \Common\Model\VintnerModel();
        $res_vintner = $m_vintner->getInfo(array('openid'=>$openid,'status'=>1));
        if(!empty($res_vintner)){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $where = array('openid'=>$openid,'small_app_id'=>7);
            $data = $m_user->getOne('id,openid,avatarUrl,nickName,mobile', $where);
            $this->to_back($data);
        }else{
            $data = array('id'=>0);
            $this->to_back($data);
        }
    }

    public function getOpenid(){
        $code = $this->params['code'];
        $m_small_app = new Smallapp_api(7);
        $data  = $m_small_app->getSmallappOpenid($code);
        if(!empty($data['openid']) && !empty($data['session_key'])){
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(17);
            $cache_key = C('SAPP_DATA').'session_openid:'.$data['openid'];
            $redis->set($cache_key,$data['session_key'],86400);
        }
        $this->to_back($data);
    }



}