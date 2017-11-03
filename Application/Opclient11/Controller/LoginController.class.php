<?php
namespace Opclient11\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class LoginController extends BaseController{ 
    /**
     * 构造函数
     */
    private $option_user_skill_arr;
    private $option_user_role_arr;
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
        $this->option_user_skill_arr = C('OPTION_USER_SKILL_ARR');
        $this->option_user_role_arr = C('OPTION_USER_ROLE_ARR');
    }
    public function doLogin(){
        $where = array();
        $username = $this->params['username'];   //用户名
        $password = $this->params['password'];   //密码
        $pwdpre  = C('PWDPRE');
        $passme = md5($password.$pwdpre);
        $password = md5(md5($password.$pwdpre));
        $m_sysuser = new \Common\Model\SysUserModel();
        
        $where['username'] = $username;
        $where['status']   =1;
        $userinfo = $m_sysuser->getUserInfo($where,'id as userid,groupId,username,remark as nickname,password');
        if($userinfo['groupId'] == 1){
            
        }else {
            
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
        
        $m_opuser_role = new \Common\Model\OpuserRoleModel();
        $skill_info = $m_opuser_role->getInfoByUserid('role_id,skill_info,is_lead_install,manage_city',$userinfo['userid']);
        //print_r($skill_info);exit;
        $m_area_model = new \Common\Model\AreaModel();
        $ret = $m_area_model->getHotelAreaList();
        array_unshift( $ret,array('id'=>9999,'region_name'=>'全国'));
        
        foreach($ret as $key=>$v){
            $area_list[$v['id']] = $v;
        }
        //$option_user_skill_arr = C('OPTION_USER_SKILL_ARR');
        $skill_result = array();
        
        //角色类型
        $skill_result['role_info'] = array('id'=>$skill_info['role_id'],'name'=>$this->option_user_role_arr[$skill_info['role_id']]);
        //管理城市
        $manage_city_list = explode(',', $skill_info['manage_city']);
        foreach($manage_city_list as $v){
            $skill_result['manage_city'][] = $area_list[$v];
        }
        //拥有技能
        if(!empty($skill_info['skill_info'])){
            $skill_list = explode(',', $skill_info['skill_info']);
            foreach($skill_list as $v){
                $skill_result['skill_info'][] = array('id'=>$v,'name'=>$this->option_user_skill_arr[$v]);
            
            }
        }
        
        //是否带队安装
        if(!empty($skill_info['is_lead_install'])){
            $skill_result['is_lead_install'] = $skill_info['is_lead_install'];
        }
        $userinfo['skill_list'] = $skill_result;
        $this->to_back($userinfo);
    }
}