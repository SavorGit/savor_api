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
        $user_array = array('liulei',
                            'duoduo',
                            'zhengwei',
                            'chengtong',
                            'huangyong',
                            'chensusu',
                            'zhanglei',
                            'zongyanli',
                            'mafeng',
                            'licong',
                            'sunbo',
                            'sunchao',
                            'bichao',
        );
        
        $where = array();
        $username = $this->params['username'];   //用户名
        $password = $this->params['password'];   //密码
        $pwdpre  = C('PWDPRE');
        $passme = md5($password.$pwdpre);
        $password = md5(md5($password.$pwdpre));
        $m_sysuser = new \Common\Model\SysUserModel();
        
        $where['username'] = $username;
        $where['status']   =1;
        $userinfo = $m_sysuser->getUserInfo($where,'id as userid,username,remark as nickname,password');
        
        if(!in_array($username, $user_array)){
            //获取运维组id
            $sysusergroup  = new \Common\Model\SysusergroupModel();
            $map['sgr.name'] = '酒楼运维';
            $map['su.username'] = $username;
            //$map['su.password'] = $passme;
            $map['su.status'] = '1';
            $field = 'su.id';
            $userarr =  $sysusergroup->getOpeprv($map, $field);
            if(empty($userarr)){
                $this->to_back('30001');    //用户密码错误或者无权限
            }
        }
        

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



