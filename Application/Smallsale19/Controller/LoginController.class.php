<?php
namespace Smallsale19\Controller;
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
            case 'scancodeLogin':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'qrcode'=>1001);

        }
        parent::_init_();
    }
    public function login(){
        $mobile = $this->params['mobile'];
        
        $openid = $this->params['openid'];
        $verify_code = trim($this->params['verify_code']);
        $invite_code = trim($this->params['invite_code']);//邀请码
        if(!check_mobile($mobile)){//验证手机格式
            $this->to_back(92001);
        }
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = 'smallappdinner_vcode_'.$mobile;
        $cache_verify_code = $redis->get($cache_key);
        if($verify_code != $cache_verify_code){
            $this->to_back(92006);
        }
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $where = [];
        $where['a.code'] = $invite_code;
        $where['a.mobile'] = $mobile;
        $where['hotel.state'] = 1;
        $where['hotel.flag']  = 0;
        
        $merchant_info = $m_merchant->alias('a')
                                    ->join('savor_hotel hotel on hotel.id=a.hotel_id','left')
                                    ->field('a.id,a.type,a.hotel_id,hotel.name hotel_name,a.service_model_id')
                                    ->where($where)
                                    ->find();
        if(empty($merchant_info)) $this->to_back(92008);   //邀请码错误
        
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = [];
        $where['merchant_id'] = $merchant_info['id'];
        $where['status']      = 1;
        $staff_info = $m_staff->field('openid')->where($where)->find();
        
        if(!empty($staff_info) && $openid!=$staff_info['openid']){//已绑定其他用户
            $this->to_back(93013);
        }elseif(!empty($staff_info)&& $openid==$staff_info['openid']){
            //检查user表是否注册了这个用户
            $m_user = new \Common\Model\Smallapp\UserModel();
            $where = [];
            $where['openid'] = $openid;
            $where['status'] = 1;
            $where['small_app_id'] = 5;
            $userinfo = $m_user->getOne('id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth', $where);
            if(empty($userinfo)){//未注册插入user表一条数据
                $data = array('mobile'=>$mobile,'small_app_id'=>5,'openid'=>$openid,'status'=>1);
                $res = $m_user->addInfo($data);
                if(!$res){
                    $this->to_back(92007);
                }
                $userinfo = array('user_id'=>$res,'openid'=>$openid,'mobile'=>$mobile,
                                  'avatarUrl'=>$userinfo['avatarUrl'],'nickName'=>$userinfo['nickName'],
                                  'status'=>$userinfo['status'],'is_wx_auth'=>$userinfo['is_wx_auth']
                );
            }else{
                $data = array('mobile'=>$mobile,'small_app_id'=>5,'openid'=>$openid,'status'=>1);
                $where = array('id'=>$userinfo['user_id']);
                $m_user->updateInfo($where,$data);
                $userinfo = array('user_id'=>$userinfo['user_id'],'openid'=>$openid,'mobile'=>$mobile,
                                  'avatarUrl'=>$userinfo['avatarUrl'],'nickName'=>$userinfo['nickName'],
                                  'status'=>$userinfo['status'],'is_wx_auth'=>$userinfo['is_wx_auth']
                );
            } 
        }else {//插入一条数据到staff表
            $data = [];
            $data['merchant_id'] = $merchant_info['id'];
            $data['parent_id']   = 0 ;
            $data['openid']      = $openid;
            $data['beinvited_time'] = date('Y-m-d H:i:s');
            $data['level']       = 1;
            $data['status']      = 1;
            $m_staff->addData($data);
            //检查user表是否注册了这个用户
            $m_user = new \Common\Model\Smallapp\UserModel();
            $where = [];
            $where['openid'] = $openid;
            $where['status'] = 1;
            $where['small_app_id'] = 5;
            $userinfo = $m_user->getOne('id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth', $where);
            if(empty($userinfo)){//未注册插入user表一条数据
                $data = array('mobile'=>$mobile,'small_app_id'=>5,'openid'=>$openid,'status'=>1);
                $res = $m_user->addInfo($data);
                if(!$res){
                    $this->to_back(92007);
                }
                $userinfo = array('user_id'=>$res,'openid'=>$openid,'mobile'=>$mobile,
                                  'avatarUrl'=>$userinfo['avatarUrl'],'nickName'=>$userinfo['nickName'],
                                  'status'=>$userinfo['status'],'is_wx_auth'=>$userinfo['is_wx_auth']
                );
            }else{
                $data = array('mobile'=>$mobile,'small_app_id'=>5,'openid'=>$openid,'status'=>1);
                $where = array('id'=>$userinfo['user_id']);
                $m_user->updateInfo($where,$data);
                $userinfo = array('user_id'=>$userinfo['user_id'],'openid'=>$openid,'mobile'=>$mobile,
                                  'avatarUrl'=>$userinfo['avatarUrl'],'nickName'=>$userinfo['nickName'],
                                  'status'=>$userinfo['status'],'is_wx_auth'=>$userinfo['is_wx_auth']
                );
            }
        }
        //清除手机邀请码缓存
        $redis->remove($cache_key);
        if($merchant_info['type']==3){
            $userinfo['hotel_id'] = -1;
            $userinfo['hotel_name'] = '';
            $userinfo['hotel_has_room'] = 1;
            $userinfo['role_type']  = 0;
        }else{
            $m_hotel = new \Common\Model\HotelModel();
            $res_room = $m_hotel->getRoomNumByHotelId($merchant_info['hotel_id']);
            if($res_room){
                $userinfo['hotel_has_room'] = 1;
            }else {
                $userinfo['hotel_has_room'] = 0;
            }
            $userinfo['hotel_id']   = $merchant_info['hotel_id'];
            $userinfo['hotel_name'] = $merchant_info['hotel_name'];
            $userinfo['role_type']  = 1;
        }
        //商家服务
        $userinfo = $this->getServiceModel($userinfo,$merchant_info['service_model_id']);
        $hotel_type = 0;
        if($userinfo['hotel_id']>0){
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getOneById('id,flag,type',$userinfo['hotel_id']);
            $hotel_type = $res_hotel['type'];
            if($hotel_type==2 && $res_hotel['flag']!=0){
                $this->to_back(93041);
            }
        }
        $userinfo['hotel_type'] = $hotel_type;
        
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
        $manage_id = intval($decode_info[0]); //商家管理员id
        
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = [];
        $where['a.id'] = $manage_id;
        $where['a.status'] = 1;
        $where['mt.status'] = 1;
        $manage_info = $m_staff->alias('a')
                                 ->join('savor_integral_merchant mt on a.merchant_id= mt.id','left')
                                 ->field('mt.id mt_id,mt.hotel_id,mt.service_model_id,a.id,a.level')
                                 ->where($where)->find();
        
        if(empty($manage_info)){//商家管理员不存在或已下线
            $this->to_back(93015);
        }
        if(isset($manage_info['level'])){
            $tmp_level = intval($manage_info['level']);
            if($tmp_level==0){
                $level=1;
            }elseif($tmp_level==1){
                $level=2;
            }elseif($tmp_level==2){
                $level = 3;
            }else{
                $level = 3;
            }
        }else{
            $level = 1;
        }

        $staff_info = $m_staff->field('id')->where(array('openid'=>$openid,'status'=>1))->find();
        if(!empty($staff_info)){//已注册过员工
            $userinfo = $this->getUserinfo($openid);
            $userinfo['hotel_id'] = $manage_info['hotel_id'];
            $userinfo['hotel_has_room'] = 0;
            $m_hotel = new \Common\Model\HotelModel();
            $res_room = $m_hotel->getRoomNumByHotelId($manage_info['hotel_id']);
            if($res_room){
                $userinfo['hotel_has_room'] = 1;
            }
            
            $map['merchant_id'] = $manage_info['mt_id'];
            $map['parent_id']   = $manage_id;
            $map['beinvited_time'] = date('Y-m-d H:i:s');
            $map['level']       = $level;
            $m_staff->updateData(array('id'=>$staff_info['id']), $map);
        }else {//未注册过员工
            $cache_key = C('SAPP_SALE_INVITE_QRCODE');
            $code_key = $cache_key.$manage_id.":$de_qrcode";
            
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(14);
            $res_cache = $redis->get($code_key);
            if(empty($res_cache)){
                $this->to_back(93004);
            }
            $redis->remove($code_key);
            //插入staff表
            $data  = [];
            $data['merchant_id'] = $manage_info['mt_id'];
            $data['parent_id']   = $manage_id;
            $data['openid']      = $openid;
            $data['beinvited_time'] = date('Y-m-d H:i:s');
            $data['level']       = $level;
            $data['status']      =1;
            $m_staff->addData($data);

            $userinfo = $this->getUserinfo($openid);
            $userinfo['hotel_id'] = $manage_info['hotel_id'];
            
            $userinfo['hotel_has_room'] = 0;
            $m_hotel = new \Common\Model\HotelModel();
            $res_room = $m_hotel->getRoomNumByHotelId($manage_info['hotel_id']);
            if($res_room){
                $userinfo['hotel_has_room'] = 1;
            }
        }
        $userinfo = $this->getServiceModel($userinfo,$manage_info['service_model_id']);
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

    private function getServiceModel($userinfo,$service_model_id){
        $service_list = C('service_list');
        $service_list = array_keys($service_list);
        
        if($userinfo['hotel_id']==-1 || empty($service_model_id)){
            $userinfo['service'] = $service_list;
        }else {
            $m_service_mx = new \Common\Model\Integral\ServiceMxModel();
            $service_info = $m_service_mx->field('service_ids')->where(array('id'=>$service_model_id))->find();
            $service_id_arr = json_decode($service_info['service_ids'],true);
            $where = [];
            $where['id']= array('in',$service_id_arr);
            $where['status'] = 1;
            $m_service = new \Common\Model\Integral\ServiceModel(); 
            $service_ret = $m_service->field('m_name')->where($where)->select();
            $service_temp = [];
            foreach($service_ret as $key=>$v){
                if(in_array($v['m_name'],$service_list)){
                    $service_temp[] = $v['m_name'];
                }
            }
            $userinfo['service'] = $service_temp;
            
        }
        return $userinfo;
    }

}