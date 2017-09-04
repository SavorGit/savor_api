<?php
namespace Opclient\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class LoginController extends BaseController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'doLogin':
                $this->is_verify = 1;
                $this->valid_fields=array('username'=>'1001','password'=>'1001');
                break;
            case 'doLogout':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    /**
     * @用户登录
     */
    public function doLogin(){
        $where = array();
        $username = $this->params['username'];   //用户名
        $password = $this->params['password'];   //密码
        $pwdpre  = C('PWDPRE');
        
        $password = md5(md5($password.$pwdpre));
        $m_sysuser = new \Common\Model\SysUserModel();
        
        $where['username'] = $username;
        $where['status']   =1;
        $userinfo = $m_sysuser->getUserInfo($where,'id,username,remark,password');
        
        if(empty($userinfo)){
            $this->to_back('30001');    //用户不存在
        }
        if($password != md5($userinfo['password'])){
            $this->to_back('30002');     //密码不正确
        }
        unset($userinfo['password']);
        $this->to_back($userinfo);
    }
    /**
     * @用户退出
     */
    public function logout(){
        $userinfo = $this->userinfo;
        $m_user_token = new \Common\Model\UserTokenModel();
        $where = $data = array();
        $where['userid'] = $userinfo['userid'];
        $data['is_logout'] = 1;
        $ret = $m_user_token->updateInfo($where,$data);
        if($ret){
            $this->to_back('');        //退出登录成功
        }else {
            $this->to_back('11111');   //退出登录失败
        }
    }
}



