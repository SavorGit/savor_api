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
        $userinfo = $m_sysuser->getUserInfo($where,'id as userid,username,remark as nickname,password');
        
        if(empty($userinfo)){
            $this->to_back('30001');    //用户不存在
        }
        if($password != md5($userinfo['password'])){
            $this->to_back('30002');     //密码不正确
        }
        unset($userinfo['password']);
        $this->to_back($userinfo);
    }
}



