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
                $this->valid_fields = array('mobile'=>1002,'openid'=>1002);
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
                $this->valid_fields = array('type'=>1002,'idate'=>1002,'openid'=>1001,'page'=>1001);
                break;
            case 'employeelist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001,'pagesize'=>1000);
                break;
            case 'removeEmployee':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'invite_id'=>1001);
                break;
            case 'invite':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'integraltypes':
                $this->is_verify = 0;
                break;
            case 'bindmobile':
                $this->is_verify = 1;
                $this->valid_fields = array('mobile'=>1001,'verify_code'=>1001,'openid'=>1001);
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
        $hotel_id = 0;
        $code_type = 0;
        if(!empty($userinfo['openid'])){
            $m_hotel_invite_code = new \Common\Model\Smallapp\HotelInviteCodeModel();
            $rts = $m_hotel_invite_code->field('hotel_id,type')->where(array('openid'=>$userinfo['openid'],'flag'=>0))->find();
            if(!empty($rts)){
                $hotel_id = $rts['hotel_id'];
                $userinfo['role_type'] = $rts['type'];
                $code_type = $rts['type'];
            }
        }
        /*if($userinfo['is_wx_auth']!=3){
            $hotel_id = 0;
        }*/
        $userinfo['hotel_id'] = $hotel_id;
        $userinfo['hotel_has_room'] = 0;
        $m_hotel = new \Common\Model\HotelModel();
        $res_room = $m_hotel->getRoomNumByHotelId($hotel_id);
        if($res_room){
            $userinfo['hotel_has_room'] = 1;
        }
        if($code_type==3){
            $userinfo['hotel_id'] = -1;
            $userinfo['hotel_has_room'] = 1;
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
        $openid = $this->params['openid'];
        $m_hotel_invite = new \Common\Model\HotelInviteCodeModel();
        $fields = 'id';
        $where = array();
        if($openid){
            $where['openid'] = $openid;
        }
        if($mobile){
            $where['bind_mobile'] = $mobile;
        }
        if(empty($where)){
            $this->to_back(92008);
        }
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
            $data['mobile']    = '';
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

            $hotel_has_room = 0;
            $m_hotel = new \Common\Model\HotelModel();
            $res_room = $m_hotel->getRoomNumByHotelId($rts['hotel_id']);
            if($res_room){
                $hotel_has_room = 1;
            }
            if($rts['type']==3){
                $data['hotel_id'] = -1;
                $hotel_has_room = 1;
            }
            $data['hotel_has_room'] = $hotel_has_room;
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
            $pre_data = json_decode($res_cache,true);
            $pre_time = $pre_data['nowtime'];
            $signinfo = $this->checkSigninTime($pre_time);
            if(!$signinfo['is_signin']){
                $res_data = array('status'=>1);
                $where = array('openid'=>$pre_data['openid'],'small_app_id'=>5);
                $fields = 'id user_id,openid,avatarUrl,nickName';
                $res_user = $m_user->getOne($fields, $where);
                $res_data['user'] = $res_user;
                $this->to_back($res_data);
            }
            $pre_id = $pre_data['id'];
            $m_usersign = new \Common\Model\Smallapp\UserSigninModel();
            $res_usersign = $m_usersign->getInfo(array('id'=>$pre_id));
            if($res_usersign['signout_time']=='0000-00-00 00:00:00'){
                $m_usersign->updateData(array('id'=>$pre_id),array('signout_time'=>$signinfo['signout_time']));
            }
        }

        $m_usersign = new \Common\Model\Smallapp\UserSigninModel();
        $add_data = array('openid'=>$openid,'box_mac'=>$box_mac,'signin_time'=>date('Y-m-d H:i:s'));
        $id = $m_usersign->addData($add_data);

        $cache_data = array('id'=>$id,'openid'=>$openid,'box_mac'=>$box_mac,'nowtime'=>time());
        $redis->set($cache_key,json_encode($cache_data),18000);

        $res_data = array('status'=>2);
        $where = array('openid'=>$openid,'small_app_id'=>5);
        $fields = 'id user_id,openid,avatarUrl,nickName';
        $res_user = $m_user->getOne($fields, $where);
        $res_data['user'] = $res_user;
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
            $res_usersignin = $m_usersign->getDataList('id,openid,box_mac,signin_time,signout_time',$where,'id desc',0,1);
            if($res_usersignin['total']){
                $sign_openid = $res_usersignin['list'][0]['openid'];
                if($res_usersignin['list'][0]['signout_time']=='0000-00-00 00:00:00'){
                    $pre_time = strtotime($res_usersignin['list'][0]['signin_time']);
                    $signinfo = $this->checkSigninTime($pre_time);
                    $is_signin = $signinfo['is_signin'];
                    if($is_signin){
                        $sign_id = $res_usersignin['list'][0]['id'];
                        $m_usersign->updateData(array('id'=>$sign_id),array('signout_time'=>$signinfo['signout_time']));
                    }
                }else{
                    $is_signin = 1;
                }
                if(!$is_signin){
                    $where = array('openid'=>$sign_openid,'small_app_id'=>5);
                    $fields = 'id user_id,openid,avatarUrl,nickName';
                    $res_user = $m_user->getOne($fields, $where);
                    $info['status'] = 2;
                    $info['user'] = $res_user;
                }
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
        $data = array('nickName'=>$res_user['nickName'],'avatarUrl'=>$res_user['avatarUrl'],'integral'=>$integral,'is_open_integral'=>0);
        $month_integral = 0;
        $next_month_integral = 0;

        $m_userintegral_record = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $fields = 'sum(integral) as total_integral';
        $where = array('openid'=>$openid,'type'=>3);
        $month_date = date("Ym", strtotime("-1 month"));
        $where['DATE_FORMAT(integral_time, "%Y%m")'] = $month_date;
        $res_month_integral = $m_userintegral_record->getDataList($fields,$where,'id desc');
        if(!empty($res_month_integral)){
            $month_integral = intval($res_month_integral[0]['total_integral']);
        }

        $month_date = date("Ym");
        $where['DATE_FORMAT(integral_time, "%Y%m")'] = $month_date;
        $res_month_integral = $m_userintegral_record->getDataList($fields,$where,'id desc');
        if(!empty($res_month_integral)){
            $next_month_integral = intval($res_month_integral[0]['total_integral']);
        }

        $data['month_integral'] = $month_integral;
        $data['next_month_integral'] = $next_month_integral;

        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $res_invite = $m_hotel_invite_code->getOne('hotel_id',array('openid'=>$openid));
        $hotel_id = $res_invite['hotel_id'];
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $fields = 'g.id as goods_id';
        $where = array('h.hotel_id'=>$hotel_id,'g.status'=>2);
        $where['g.type']= array('in',array(30,31));
        $orderby = 'g.id desc';
        $limit = "0,1";
        $res_goods = $m_hotelgoods->getList($fields,$where,$orderby,$limit);
        if(!empty($res_goods)){
            $data['is_open_integral'] = 1;
        }
        $this->to_back($data);
    }

    public function integralrecord(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $type = $this->params['type'];
        $idate = $this->params['idate'];

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
        $fields = 'room_name,integral,content,type,integral_time,goods_id';
        $where = array('openid'=>$openid);
        if($type){
            $where['type'] = $type;
        }
        if(empty($idate))   $idate = date('Ym');
        if($idate){
            $where['DATE_FORMAT(integral_time, "%Y%m")'] = $idate;
        }
        $res_record = $m_userintegral_record->getDataList($fields,$where,0,$all_nums);
        $datalist = array();
        $all_types = C('INTEGRAL_TYPES');
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        foreach ($res_record as $v){
            $add_time = date('Y-m-d',strtotime($v['integral_time']));
            $info = array('room_name'=>$v['room_name'],'integral'=>$v['integral'],'add_time'=>$add_time,'type'=>$v['type']);
            switch ($v['type']){
                case 1:
                    $content = $all_types[1]."{$v['content']}小时";
                    break;
                case 2:
                    $content = $all_types[2]."{$v['content']}人";
                    break;
                case 3:
                    $goods_id = $v['goods_id'];
                    $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
                    $content = $all_types[3]."{$res_goods['name']} {$v['content']}件";
                    if($info['integral']==0){
                        $info['integral']='计算中...';
                    }
                    break;
                case 4:
                    $content = $all_types[4];
                    break;
                default:
                    $content = "";
            }
            $info['content'] = $content;
            $datalist[] = $info;

        }
        $data = array('datalist'=>$datalist);
        $this->to_back($data);
    }

    public function integraltypes(){
        $all_types = C('INTEGRAL_TYPES');
        $type_list = array();
        foreach ($all_types as $k=>$v){
            $type_list[] = array('id'=>$k,'name'=>$v);
        }
        array_unshift($type_list,array('id'=>0,'name'=>'全部'));
        $type_name_list = array_values($all_types);
        array_unshift($type_name_list,'全部');

        $sale_date = C('SALE_DATE');
        $end_date = date('Y-m');
        $start    = new \DateTime($sale_date);
        $end      = new \DateTime($end_date);
        $interval = \DateInterval::createFromDateString('1 month');
        $period   = new \DatePeriod($start, $interval, $end);
        $date_list = array();
        $date_name_list = array();
        foreach ($period as $dt) {
            $name = $dt->format("Y年m月");
            $date_list[] = array('id'=>$dt->format("Ym"),'name'=>$name);
            $date_name_list[]=$name;
        }
        $date_list[] = array('id'=>date('Ym',strtotime($end_date)),'name'=>date('Y年m月',strtotime($end_date)));
        $date_name_list[] = date('Y年m月',strtotime($end_date));
        $date_key = count($date_name_list)-1;
        $data = array('type_list'=>$type_list,'type_name_list'=>$type_name_list,'date_list'=>$date_list,'date_name_list'=>$date_name_list);
        $data['date_key'] = $date_key;
        $this->to_back($data);
    }

    public function employeelist(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $pagesize = intval($this->params['pagesize']);
        if(empty($pagesize)){
            $pagesize = 15;
        }
        $m_hotel_invite_code = new \Common\Model\Smallapp\HotelInviteCodeModel();
        $where = array('openid'=>$openid,'state'=>1,'flag'=>0);
        $res_invite_code = $m_hotel_invite_code->getInfo($where);
        if($res_invite_code['type']!=2){
            $this->to_back(93001);
        }
        $all_nums = $page * $pagesize;
        $where = array('invite_id'=>$res_invite_code['id'],'state'=>1,'flag'=>0,'type'=>1);
        $res_invites = $m_hotel_invite_code->getDataList('openid',$where,'id desc',0,$all_nums);

        $datalist = array();
        if($res_invites['total']){
            $m_user = new \Common\Model\Smallapp\UserModel();
            foreach ($res_invites['list'] as $v){
                $where = array('openid'=>$v['openid']);
                $fields = 'openid,avatarUrl,nickName';
                $res_user = $m_user->getOne($fields, $where);
                $res_user['invite_id'] = $res_invite_code['id'];
                $datalist[] = $res_user;
            }
        }
        $data = array('datalist'=>$datalist);
        $this->to_back($data);
    }

    public function removeEmployee(){
        $openid = $this->params['openid'];
        $invite_id = $this->params['invite_id'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 5;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_hotel_invite_code = new \Common\Model\Smallapp\HotelInviteCodeModel();
        $where = array('openid'=>$openid,'state'=>1,'flag'=>0);
        $res_invite_code = $m_hotel_invite_code->getInfo($where);
        if(empty($res_invite_code) || $res_invite_code['invite_id']!=$invite_id){
            $this->to_back(93002);
        }
        $m_hotel_invite_code->updateData(array('id'=>$res_invite_code['id']),array('flag'=>1));
        $this->to_back(array());
    }

    public function invite(){
        $openid = $this->params['openid'];
        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $fields = 'id,hotel_id,bind_mobile,openid,type';
        $where = array('openid'=>$openid,'state'=>1,'flag'=>0);
        $res_invite_code = $m_hotel_invite_code->getOne($fields,$where);
        if($res_invite_code['type']!=2){
            $this->to_back(93001);
        }

        $cache_key = C('SAPP_SALE_INVITE_QRCODE');
        $uniq_id = uniqid('',true);
        $invite_cache_key = $res_invite_code['id'].'&'.$uniq_id;
        $code_key = $cache_key.$res_invite_code['id'].":$invite_cache_key";

        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $redis->set($code_key,$res_invite_code['id'],3600*4);
        $qrinfo = encrypt_data($invite_cache_key);
        $host_name = C('HOST_NAME');
        $qrcode_url = $host_name."/smallsale/qrcode/inviteQrcode?qrinfo=$qrinfo";
        $res = array('qrcode_url'=>$qrcode_url,'qrcode'=>$qrinfo);
        $this->to_back($res);
    }
    public function bindmobile(){
        $mobile = $this->params['mobile'];
        $verify_code = $this->params['verify_code'];
        $openid = $this->params['openid'];
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = 'smallappsale_bindmobile_vcode_'.$mobile;
        $v_code_info = $redis->get($cache_key);
        if(empty($v_code_info)){//手机验证码错误或已失效
            $this->to_back(93009);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $nums = $m_user->countNum(array('openid'=>$openid,'status'=>1,'small_app_id'=>5));
        if(empty($nums)){
            $this->to_back(93012);
        }
        
        
        $nums = $m_user->countNum(array('mobile'=>$mobile,'status'=>1,'small_app_id'=>5));
        if(!empty($nums)){
            $this->to_back(93013);
        }
        $m_user->startTrans();
        $data= [];
        $data['mobile'] = $mobile;
        $rt = $m_user->updateInfo(array('openid'=>$openid,'small_app_id'=>5), $data);
        $m_hotel_invite_code = new \Common\Model\Smallapp\HotelInviteCodeModel();
        $data = [];
        $data['bind_mobile'] = $mobile;
        $rts = $m_hotel_invite_code->updateData(array('openid'=>$openid), $data);
        if($rt && $rts){
            $m_user->commit();
            $this->to_back(10000);
        }else {
            $m_user->rollback();
            $this->to_back(93010);
        }
    }

    private function checkSigninTime($signin_time){
        $is_signin = 0;
        $feast_time = C('FEAST_TIME');

        $pre_time = date('Y-m-d H:i',$signin_time);
        $pre_date = date('Y-m-d',$signin_time);

        $now_time = date('Y-m-d H:i');
        $lunch_stime = $pre_date.' '.$feast_time['lunch'][0];
        $lunch_etime = $pre_date.' '.$feast_time['lunch'][1];

        $dinner_stime = $pre_date.' '.$feast_time['dinner'][0];
        $dinner_etime = $pre_date.' '.$feast_time['dinner'][1];

        if($pre_time<$lunch_stime){
            $over_time = $lunch_etime;
        }elseif($pre_time>=$lunch_stime && $pre_time<=$lunch_etime){
            $over_time = $lunch_etime;
        }elseif($pre_time>$lunch_etime){
            $over_time = $dinner_etime;
        }else{
            $over_time = $dinner_etime;
        }
        if($now_time > $over_time){
            $is_signin = 1;
        }
        $res = array('is_signin'=>$is_signin,'signout_time'=>$over_time);
        return $res;
    }
}