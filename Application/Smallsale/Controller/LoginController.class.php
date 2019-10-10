<?php
namespace Smallsale\Controller;
use \Common\Controller\CommonController;
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
            case 'qrcodeLogin':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'qrcode'=>1001);

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

        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = 'smallappdinner_vcode_'.$mobile;
        $cache_verify_code = $redis->get($cache_key);

        if($verify_code != $cache_verify_code){
            $this->to_back(92006);
        }

        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $res_code = $m_hotel_invite_code->getOne('hotel_id,code,type',array('code'=>$invite_code));
        if($res_code['type']==3){
            $type = 3;
        }else{
            $type = 2;
        }

        $where = array('a.bind_mobile'=>$mobile,'a.flag'=>0,'type'=>$type);
        $invite_code_info = $m_hotel_invite_code->getInfo('a.id,a.is_import_customer,a.code,a.type,b.id hotel_id,b.name hotel_name,c.is_open_customer', $where);
        if(!empty($invite_code_info) && $invite_code!=$invite_code_info['code']){
            $this->to_back(92008);
        }

        if(empty($invite_code_info)){
            $where = array('a.code'=>$invite_code,'a.flag'=>0,'type'=>$type);
            $invite_code_info = $m_hotel_invite_code->getInfo('a.id,a.bind_mobile,a.state,a.type,b.id hotel_id,b.name hotel_name,c.is_open_customer',$where);
            if(empty($invite_code_info)){//输入的邀请码不正确
                $this->to_back(92002);
            }
            if($invite_code_info['state'] ==1 && $invite_code_info['bind_mobile']!=$mobile){
                $this->to_back(92003);
            }
            $where = array('id'=>$invite_code_info['id']);
            $data = array('state'=>1,'bind_mobile'=>$mobile,'openid'=>$openid);
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
            $data = array('mobile'=>$mobile,'small_app_id'=>5,'openid'=>$openid,'status'=>1);
            $res = $m_user->addInfo($data);
            if(!$res){
                $this->to_back(92007);
            }
            $userinfo = array('user_id'=>$res,'openid'=>$openid,'mobile'=>$mobile);
        }else{
            $data = array('mobile'=>$mobile,'small_app_id'=>5,'openid'=>$openid,'status'=>1);
            $where = array('id'=>$userinfo['user_id']);
            $m_user->updateInfo($where,$data);
            $userinfo = array('user_id'=>$userinfo['user_id'],'openid'=>$openid,'mobile'=>$mobile);
        }
        $userinfo['hotel_id'] = $invite_code_info['hotel_id'];
        $userinfo['hotel_name'] = $invite_code_info['hotel_name'];
        $userinfo['role_type'] = $invite_code_info['type'];
        $userinfo['hotel_has_room'] = 0;
        if($invite_code_info['hotel_id']){
            $m_hotel = new \Common\Model\HotelModel();
            $res_room = $m_hotel->getRoomNumByHotelId($invite_code_info['hotel_id']);
            if($res_room){
                $userinfo['hotel_has_room'] = 1;
            }
        }
        if($type==3){
            $userinfo['hotel_id'] = -1;
            $userinfo['hotel_name'] = '';
            $userinfo['hotel_has_room'] = 1;
        }

        $this->to_back($userinfo);
    }

    public function scancodeLogin(){
        $openid = $this->params['openid'];
        $qrcode = $this->params['qrcode'];
        $de_qrcode = decrypt_data($qrcode,false);
        if(empty($de_qrcode)){
            $this->to_back(93003);
        }
        $decode_info = explode('&',$de_qrcode);
        $hotel_invite_id = intval($decode_info[0]);

        $m_hotel_invite_code = new \Common\Model\Smallapp\HotelInviteCodeModel();
        $where = array('openid'=>$openid,'flag'=>0);
        $invite_code_info = $m_hotel_invite_code->getInfo($where);
        if(!empty($invite_code_info) && $invite_code_info['invite_id']){
            $userinfo = $this->getUserinfo($openid);
            $userinfo['hotel_id'] = $invite_code_info['hotel_id'];

            $userinfo['hotel_has_room'] = 0;
            $m_hotel = new \Common\Model\HotelModel();
            $res_room = $m_hotel->getRoomNumByHotelId($invite_code_info['hotel_id']);
            if($res_room){
                $userinfo['hotel_has_room'] = 1;
            }
            $this->to_back($userinfo);
        }

        $cache_key = C('SAPP_SALE_INVITE_QRCODE');
        $code_key = $cache_key.$hotel_invite_id.":$de_qrcode";

        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $res_cache = $redis->get($code_key);
        if(empty($res_cache)){
            $this->to_back(93004);
        }

        if(empty($invite_code_info)){
            $res_invite = $m_hotel_invite_code->getInfo(array('id'=>$hotel_invite_id));
            $hotel_id = $res_invite['hotel_id'];
            $where = array('hotel_id'=>$hotel_id,'type'=>1,'state'=>0,'flag'=>0);
            $res = $m_hotel_invite_code->getDataList('id,code',$where,'id asc',0,1);
            if($res['total']==0){
                $this->to_back(93005);
            }
            $invite_code = $res['list'][0]['code'];
            $id = $res['list'][0]['id'];

            $where = array('id'=>$id);
            $data = array('hotel_id'=>$hotel_id,'code'=>$invite_code,'openid'=>$openid,'invite_id'=>$hotel_invite_id,'state'=>1);
            $data['bind_time'] = date('Y-m-d H:i:s');
            $m_hotel_invite_code->updateData($where,$data);
        }else{
            $hotel_id = $invite_code_info['hotel_id'];
            if($invite_code_info['type']==1 && empty($invite_code_info['invite_id'])){
                $where = array('id'=>$invite_code_info['id']);
                $data = array('invite_id'=>$hotel_invite_id);
                $m_hotel_invite_code->updateData($where,$data);
            }
        }
        $redis->select(14);
        $redis->remove($code_key);
        $userinfo = $this->getUserinfo($openid);
        $userinfo['hotel_id'] = $hotel_id;

        $userinfo['hotel_has_room'] = 0;
        $m_hotel = new \Common\Model\HotelModel();
        $res_room = $m_hotel->getRoomNumByHotelId($hotel_id);
        if($res_room){
            $userinfo['hotel_has_room'] = 1;
        }
        $this->to_back($userinfo);
    }

    private function getUserinfo($openid){
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $userinfo = $m_user->getOne('id as user_id,openid,mobile', $where);
        if(empty($userinfo)){
            $data = array('small_app_id'=>5,'openid'=>$openid,'status'=>1);
            $res = $m_user->addInfo($data);
            if(!$res){
                $this->to_back(92007);
            }
            $userinfo = array('user_id'=>$res,'openid'=>$openid);
        }else{
            $data = array('small_app_id'=>5,'openid'=>$openid,'status'=>1);
            $where = array('id'=>$userinfo['user_id']);
            $m_user->updateInfo($where,$data);
            $userinfo = array('user_id'=>$userinfo['user_id'],'openid'=>$openid);
        }
        return $userinfo;
    }

}