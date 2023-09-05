<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController;
use Common\Lib\Smallapp_api;

class LoginController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'registerLogin':
                $this->is_verify = 1;
                $this->valid_fields = array('mobile'=>1001,'verify_code'=>1001,'openid'=>1001,
                    'avatarUrl'=>1001,'nickName'=>1001,'gender'=>1001,
                    'session_key'=>1001,'iv'=>1001,'encryptedData'=>1001);
                break;
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
                $this->valid_fields = array('mobile'=>1001,'verify_code'=>1001,'openid'=>1001);
                break;
        }
        parent::_init_();
    }
    public function userLogin(){
        $mobile = $this->params['mobile'];
        $verify_code = trim($this->params['verify_code']);
        $openid = $this->params['openid'];
        
        if(!check_mobile($mobile)){//验证手机格式
            $this->to_back(92001);
        }
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = C('SAPP_OPS').'register:'.$mobile;
        $cache_verify_code = $redis->get($cache_key);
        if($verify_code != $cache_verify_code){
            $this->to_back(92006);
        }
        
        
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_mobilestaff = $m_staff->getInfo(array('mobile'=>$mobile,'status'=>1));
        if(empty($res_mobilestaff)){
            $this->to_back(94001);
        }
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(!empty($res_staff)){
            $this->to_back(94002);
        }
        
        $staff_data = array('openid'=>$openid,'update_time'=>date('Y-m-d H:i:s'));
        $m_staff->updateData(array('id'=>$res_mobilestaff['id']),$staff_data);
        
        
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>6);
        $userinfo = $m_user->getOne('id,openid,mobile,role_id', $where);
        $data = array('openid'=>$openid,'avatarUrl'=>'','nickName'=>'','gender'=>0,'mobile'=>$mobile,
            'is_wx_auth'=>0,'small_app_id'=>6,'status'=>1);
        
        if(empty($userinfo)){
            $m_user->addInfo($data);
        }else{
            $m_user->updateInfo(array('id'=>$userinfo['id']), $data);
        }
        $data['staff_id'] = $res_staff['id'];
        $data['permission_city'] = $m_staff->get_permission_city($res_staff);
        $this->to_back($data);
        
    }
    public function registerLogin(){
        $mobile = $this->params['mobile'];
        $verify_code = trim($this->params['verify_code']);
        $openid = $this->params['openid'];
        $avatarUrl = $this->params['avatarUrl'];
        $nickName = $this->params['nickName'];
        $gender = $this->params['gender'];
        $encryptedData = $this->params['encryptedData'];

        if(!check_mobile($mobile)){//验证手机格式
            $this->to_back(92001);
        }
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = C('SAPP_OPS').'register:'.$mobile;
        $cache_verify_code = $redis->get($cache_key);
        if($verify_code != $cache_verify_code){
            $this->to_back(92006);
        }
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_mobilestaff = $m_staff->getInfo(array('mobile'=>$mobile,'status'=>1));
        if(empty($res_mobilestaff)){
            $this->to_back(94001);
        }
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(!empty($res_staff)){
            $this->to_back(94002);
        }

        $staff_data = array('openid'=>$openid,'update_time'=>date('Y-m-d H:i:s'));
        $m_staff->updateData(array('id'=>$res_mobilestaff['id']),$staff_data);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>6);
        $userinfo = $m_user->getOne('id,openid,mobile,role_id', $where);
        $data = array('openid'=>$openid,'avatarUrl'=>$avatarUrl,'nickName'=>$nickName,'gender'=>$gender,'mobile'=>$mobile,
            'is_wx_auth'=>3,'small_app_id'=>6,'status'=>1);
        if(!empty($encryptedData['unionId'])){
            $data['unionId'] = $encryptedData['unionId'];
        }
        if(empty($userinfo)){
            $m_user->addInfo($data);
        }else{
            $m_user->updateInfo(array('id'=>$userinfo['id']), $data);
        }
        $data['staff_id'] = $res_staff['id'];
        $data['permission_city'] = $m_staff->get_permission_city($res_staff);
        $this->to_back($data);
    }

    public function isLogin(){
        $openid = $this->params['openid'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(!empty($res_staff)){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $where = array('openid'=>$openid,'small_app_id'=>6);
            $data = $m_user->getOne('id,openid,avatarUrl,nickName,mobile', $where);
            $m_area = new \Common\Model\AreaModel();
            $res_area = $m_area->getWhere('id,region_name',array('id'=>$res_staff['area_id']),'id desc','0,1');
            $data['staff_id'] = $res_staff['id'];
            $data['job'] = $res_staff['job'];
            $data['area_name'] = $res_area['region_name'];
            $data['role_type'] = $res_staff['hotel_role_type'];
            $data['permission_city'] = $m_staff->get_permission_city($res_staff);
            if($res_staff['is_operrator']==0){
                $data['permission_work_city'] = $m_staff->get_permission_work_city($res_staff);
            }else {
                $data['permission_work_city'] = array();
            }
            $data['check_city'] = $m_staff->get_check_city($res_staff);
            
            $this->to_back($data);
        }else{
            $data = array('id'=>0);
            $this->to_back($data);
        }
    }

    public function getOpenid(){
        $code = $this->params['code'];
        $m_small_app = new Smallapp_api(6);
        $data  = $m_small_app->getSmallappOpenid($code);
        if(!empty($data['openid']) && !empty($data['session_key'])){
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(14);
            $cache_key = C('SAPP_OPS').'session_openid:'.$data['openid'];
            $redis->set($cache_key,$data['session_key'],86400);
        }
        $this->to_back($data);
    }



}