<?php
namespace Smallsale\Controller;
use \Common\Controller\CommonController;
use Common\Lib\Smallapp_api;
use Common\Lib\SavorRedis;

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
                $this->valid_fields = array('openid'=>1001,'avatarUrl'=>1000,'nickName'=>1000,
                    'gender'=>1000,'session_key'=>1001,'iv'=>1001,'encryptedData'=>1001,
                );
                break;
            case 'signin':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1000);
                break;
            case 'getSigninBoxList':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                break;
            case 'center':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'integralrecord':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001);
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
        $where['small_app_id'] = 5;
        $userinfo = $m_user->getOne('id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth', $where);
        $data = array();
        if(empty($userinfo)){
            $data['openid'] = $openid;
            $data['status'] = 1;
            $data['small_app_id'] = 5;
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
        $where['small_app_id'] = 5;
        $userinfo = $m_user->getOne('openid,mobile', $where);
        //$nums = $m_user->countNum($where);
        if(empty($userinfo)){
            $data['openid']    = $openid;
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['is_wx_auth']= 2;
            $data['small_app_id'] = 5;
            $m_user->addInfo($data);
            $this->to_back($data);
        }else {
            $data = array();
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['is_wx_auth']= 2;
            $data['small_app_id'] = 5;
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
        $m_small_app = new Smallapp_api($flag = 5);
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
        $where['small_app_id'] = 5;
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
            $data['small_app_id'] = 5;
            $m_user->addInfo($data);
            $this->to_back($data);
        }else {
            $data = array();
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['unionId']   = $encryptedData['unionId'];
            $data['is_wx_auth']= 3;
            $data['small_app_id'] = 5;
            $m_user->updateInfo($where, $data);
            $data['openid'] = $openid;
            $data['mobile'] = $userinfo['mobile'];
            $m_hotel_invite_code = new \Common\Model\Smallapp\HotelInviteCodeModel();
            $rts = $m_hotel_invite_code->field('hotel_id')->where(array('bind_mobile'=>$userinfo['mobile'],'flag'=>0))->find();
            $data['hotel_id'] = $rts['hotel_id'];
            $this->to_back($data);
        }
    }

    public function signin(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 5;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_box = new \Common\Model\BoxModel();
        $map = array();
        $map['a.mac'] = $box_mac;
        $map['a.state'] = 1;
        $map['a.flag']  = 0;
        $map['d.state'] = 1;
        $map['d.flag']  = 0;
        $box_info = $m_box->getBoxInfo('a.id as box_id', $map);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = C('SAPP_SALE').'signin:'.$box_mac;
        $res_cache = $redis->get($cache_key);
        if(!empty($res_cache)){
            $feast_time = C('FEAST_TIME');
            $pre_data = json_decode($res_cache,true);
            $pre_time = date('Y-m-d H:i',$pre_data['nowtime']);

            $now_time = date('Y-m-d H:i');
            $now_date = date('Y-m-d');
            $lunch_stime = $now_date.' '.$feast_time['lunch'][0];
            $lunch_etime = $now_date.' '.$feast_time['lunch'][1];

            $dinner_stime = $feast_time['dinner'][0];
            $dinner_etime = $feast_time['dinner'][1];

            if($pre_time<$lunch_stime){
                $over_time = $lunch_etime;
            }elseif($pre_time>=$lunch_stime && $pre_time<=$lunch_etime){
                $over_time = $lunch_etime;
            }elseif($pre_time>$lunch_etime){
                $over_time = $dinner_etime;
            }else{
                $over_time = $dinner_etime;
            }
            if($now_time<$over_time){
                $this->to_back(92011);
            }
            $pre_id = $pre_data['id'];
            $m_usersign = new \Common\Model\Smallapp\UserSigninModel();
            $res_usersign = $m_usersign->getInfo(array('id'=>$pre_id));
            if($res_usersign['signout_time']=='0000-00-00 00:00:00'){
                $m_usersign->updateData(array('id'=>$pre_id),array('signout_time'=>date('Y-m-d H:i:s')));
            }
        }

        $m_usersign = new \Common\Model\Smallapp\UserSigninModel();
        $add_data = array('openid'=>$openid,'box_mac'=>$box_mac,'signin_time'=>date('Y-m-d H:i:s'));
        $id = $m_usersign->addData($add_data);

        $cache_data = array('id'=>$id,'openid'=>$openid,'box_mac'=>$box_mac,'nowtime'=>time());
        $redis->set($cache_key,json_encode($cache_data),18000);

        $res_data = array('message'=>'签到成功');
        $this->to_back($res_data);
    }

    public function getSigninBoxList(){
        $hotel_id = $this->params['hotel_id'];
        $openid = $this->params['openid'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 5;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_box = new \Common\Model\BoxModel();
        $fields = 'a.name,a.mac';
        $res = $m_box->getBoxListByHotelid($fields,$hotel_id);
        $m_usersign = new \Common\Model\Smallapp\UserSigninModel();
        $box_list = array();
        foreach ($res as $v){
            $info = array('name'=>$v['name'],'box_mac'=>$v['mac'],'status'=>1,'user'=>array());
            $where = array('box_mac'=>$v['mac']);
            $res_usersignin = $m_usersign->getDataList('openid,box_mac',$where,'id desc',0,1);
            if($res_usersignin['total']){
                $sign_openid = $res_usersignin['list'][0]['openid'];
                $where = array('openid'=>$sign_openid,'small_app_id'=>5);
                $fields = 'id user_id,openid,avatarUrl,nickName';
                $res_user = $m_user->getOne($fields, $where);
                $info['status'] = 2;
                $info['user'] = $res_user;
            }
            $box_list[] = $info;
        }
        $this->to_back($box_list);
    }

    public function center(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 5;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
        $res_userintegral = $m_userintegral->getInfo(array('openid'=>$openid));
        $integral = 0;
        if(!empty($res_userintegral)){
            $integral = intval($res_userintegral['integral']);
        }
        $data = array('nickName'=>$res_user['nickName'],'avatarUrl'=>$res_user['avatarUrl'],'integral'=>$integral);
        $this->to_back($data);
    }

    public function integralrecord(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $pagesize = 15;
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 5;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $all_nums = $page * $pagesize;
        $m_userintegral_record = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $fields = 'room_name,integral,content,type,add_time';
        $where = array('openid'=>$openid);
        $res_record = $m_userintegral_record->getDataList($fields,$where,0,$all_nums);
        $datalist = array();
        foreach ($res_record as $v){
            $v['add_time'] = date('Y-m-d',strtotime($v['add_time']));
            $datalist[] = $v;
        }
        $datalist[] = array('room_name'=>'VIP包间1','integral'=>30,'content'=>'开机3小时','add_time'=>'2019-07-24');
        $datalist[] = array('room_name'=>'VIP包间2','integral'=>80,'content'=>'互动10人','add_time'=>'2019-07-24');
        $datalist[] = array('room_name'=>'VIP包间3','integral'=>1000,'content'=>'销售商品','add_time'=>'2019-07-24');
        $datalist[] = array('room_name'=>'VIP包间4','integral'=>-1000,'content'=>'兑换','add_time'=>'2019-07-24');
        $data = array('datalist'=>$datalist);
        $this->to_back($data);
    }
}