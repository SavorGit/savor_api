<?php
namespace Smalldinnerapp11\Controller;
use \Common\Controller\CommonController;
use Common\Lib\SavorRedis;
class LoginController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'login':
                $this->is_verify = 1;
                $this->valid_fields = array('mobile'=>1001,'openid'=>1001,'invite_code'=>1001,'verify_code'=>1001);
                break;
            case 'getHotelRoomInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>'1001');
                break;
                
        }
        parent::_init_();
    }
    
    public function login(){
        $mobile = intval($this->params['mobile']);
        $openid = $this->params['openid'];
        $verify_code = trim($this->params['verify_code']);
        $invite_code = trim($this->params['invite_code']);//邀请码
        //验证手机格式
        if(!check_mobile($mobile)){
            $this->to_back(92001);
        }

        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = 'smallappdinner_vcode_'.$mobile;
        $cache_verify_code = $redis->get($cache_key);

        if($verify_code != $cache_verify_code){
            $this->to_back(92006);
        }

        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $where = array('a.bind_mobile'=>$mobile,'a.flag'=>0);
        $invite_code_info = $m_hotel_invite_code->getInfo('a.id invite_id,a.is_import_customer,a.code,b.id hotel_id,b.name hotel_name,c.is_open_customer', $where);
        if(!empty($invite_code_info) && $invite_code!=$invite_code_info['code']){
            $this->to_back(92008);
        }

        if(empty($invite_code_info)){
            $where = array('a.code'=>$invite_code,'a.flag'=>0);
            $invite_code_info = $m_hotel_invite_code->getInfo('a.id,a.bind_mobile,a.state,b.id hotel_id,b.name hotel_name,c.is_open_customer',$where);
            if(empty($invite_code_info)){//输入的邀请码不正确
                $this->to_back(92002);
            }
            if($invite_code_info['state'] ==1 && $invite_code_info['bind_mobile']!=$mobile){
                $this->to_back(92003);
            }
            $where = array('id'=>$invite_code_info['id']);
            $data = array('state'=>1,'bind_mobile'=>$mobile);
            $data['bind_time'] = date('Y-m-d H:i:s');
            $m_hotel_invite_code->saveInfo($where,$data);
        }
        if($verify_code){
            $redis->remove($cache_key);
        }

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $userinfo = $m_user->getOne('id as user_id,openid,mobile', $where);
        if(empty($userinfo)){
            $data = array('mobile'=>$mobile,'small_app_id'=>4,'openid'=>$openid,'status'=>1);
            $res = $m_user->addInfo($data);
            if(!$res){
                $this->to_back(92007);
            }
            $userinfo = array('user_id'=>$res,'openid'=>$openid,'mobile'=>$mobile);
        }else{
            $data = array('mobile'=>$mobile,'small_app_id'=>4,'openid'=>$openid,'status'=>1);
            $where = array('id'=>$userinfo['user_id']);
            $m_user->updateInfo($where,$data);
            $userinfo = array('user_id'=>$userinfo['user_id'],'openid'=>$openid,'mobile'=>$mobile);
        }
        $userinfo['hotel_id'] = $invite_code_info['hotel_id'];
        $userinfo['hotel_name'] = $invite_code_info['hotel_name'];
        $this->to_back($userinfo);
    }
    /**
     * @desc  获取酒楼包间名称
     */
    public function getHotelRoomInfo(){
        $box_mac = $this->params['box_mac'];
        $m_box = new \Common\Model\BoxModel();
        $info = array();
        
        $fields  = 'd.name hotel_name,c.name room_name,a.wifi_name,a.wifi_password,a.wifi_mac,a.is_open_simple';
        $where = array();
        $where['d.state'] = 1;
        $where['d.flag']  = 0;
        $where['a.state'] = 1;
        $where['a.flag']  = 0;
        $where['a.mac']   = $box_mac;
        $info = $m_box->getBoxInfo($fields,$where);
        if(empty($info)){
            $this->to_back(70001);
        }else {
            $redis = SavorRedis::getInstance();
            $redis->select(13);
            $cache_key = 'heartbeat:2:'.$box_mac;
            $data = $redis->get($cache_key);
            $intranet_ip = '';
            if(!empty($data)){
                $data = json_decode($data,true);
                $intranet_ip = $data['intranet_ip'];
            }
            $info = $info[0];
            $info['intranet_ip'] = $intranet_ip;
            $this->to_back($info);
        }
    }
}