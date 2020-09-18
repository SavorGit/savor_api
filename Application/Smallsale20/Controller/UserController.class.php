<?php
namespace Smallsale20\Controller;
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
                $this->valid_fields = array('openid'=>1001);
                $this->is_verify = 1;
                break;
            case 'bindmobile':
                $this->is_verify = 1;
                $this->valid_fields = array('mobile'=>1001,'verify_code'=>1001,'openid'=>1001);
                break;
            case 'edit':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'avatar_url'=>1002,'name'=>1002);
                break;
        }
        parent::_init_();
    }
    public function isRegister(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 5;
        $userinfo = $m_user->getOne('id user_id,openid,mobile,avatarUrl,nickName,gender,status,role_id,is_wx_auth', $where);
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
        $service_model_id = 0;
        $mtype = 0;
        if(!empty($userinfo['openid'])){
            $m_staff = new \Common\Model\Integral\StaffModel();
            $fields = 'mt.hotel_id,mt.type,mt.mtype,mt.service_model_id,a.level';
            $rts = $m_staff->alias('a')
                           ->field($fields)
                           ->join('savor_integral_merchant mt on mt.id=a.merchant_id','left')
                           ->where(array('a.openid'=>$openid,'a.status'=>'1','mt.status'=>1))
                           ->find();
            if(!empty($rts)){
                $hotel_id = $rts['hotel_id'];
                $userinfo['role_type'] = $rts['level'];
                $code_type = $rts['type'];
                $service_model_id = $rts['service_model_id'];
                $mtype = $rts['mtype'];
            }
        }
        if(isset($userinfo['role_id']) && $userinfo['role_id']==3){
            $userinfo['role_type'] = 4;
            unset($userinfo['role_id']);
        }
        if($mtype==2){
            $userinfo['role_type'] = 5;//4是代购人员 5非合作商家
        }
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
        if($userinfo['hotel_id']!=0){
            $userinfo = $this->getServiceModel($userinfo,$rts['service_model_id']);
        }
        $hotel_type = 0;
        if($hotel_id){
            $res_hotel = $m_hotel->getOneById('id,flag,type',$hotel_id);
            $hotel_type = $res_hotel['type'];
            if($hotel_type==2 && $res_hotel['flag']!=0){
                $this->to_back(93041);
            }
        }
        $userinfo['hotel_type'] = $hotel_type;

        $subscribe_status = 0;//1无openID 2未关注公众号 3已关注公众号
        if($openid){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $where = array('openid'=>$openid);
            $user_info = $m_user->getOne('id,avatarUrl,nickName,wx_mpopenid,is_subscribe',$where,'id desc');
            if(empty($user_info['wx_mpopenid'])){
                $subscribe_status = 1;
            }else{
                $wechat = new \Common\Lib\Wechat();
                $access_token = $wechat->getWxAccessToken();
                $res = $wechat->getWxUserDetail($access_token,$user_info['wx_mpopenid']);
                if(isset($res['openid']) && isset($res['subscribe'])){
                    $is_subscribe = intval($res['subscribe']);
                    if($is_subscribe){
                        $subscribe_status = 3;
                    }else{
                        $subscribe_status = 2;
                    }
                    $m_user->updateInfo(array('id'=>$user_info['id']),array('is_subscribe'=>$is_subscribe));
                }else{
                    $subscribe_status = 2;
                }
            }
        }
        $userinfo['subscribe_status'] = $subscribe_status;
        $data['userinfo'] = $userinfo;
        $this->to_back($data);
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
        $data['official_account_article_url'] =C('OFFICIAL_ACCOUNT_ARTICLE_URL');
        $this->to_back($data);
    }
    
    public function registerCom(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 5;
        $userinfo = $m_user->getOne('openid,mobile,role_id', $where);
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
            //$m_hotel_invite_code = new \Common\Model\Smallapp\HotelInviteCodeModel();
            //$rts = $m_hotel_invite_code->field('hotel_id')->where(array('bind_mobile'=>$userinfo['mobile'],'flag'=>0))->find();
            $m_staff = new \Common\Model\Integral\StaffModel();
            
            $fields = 'm.hotel_id,m.type,m.mtype,m.service_model_id,a.level';
            $rts = $m_staff->alias('a')
                           ->field($fields)
                           ->join('savor_integral_merchant m on m.id=a.merchant_id','left')
                           ->where(array('a.openid'=>$openid,'a.status'=>'1','m.status'=>1))
                           ->find();
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
            }else {
                $data['role_type'] = $rts['level'];
            }
            $data['hotel_has_room'] = $hotel_has_room;
            $data = $this->getServiceModel($data,$rts['service_model_id']);
            if($userinfo['role_id']==3){
                $data['hotel_id']=0;
                $data['role_type']=4;
            }
            if($rts['mtype']==2){
                $userinfo['role_type'] = 5;//4是代购人员 5非合作商家
            }
            
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
        $box_info = $m_box->getBoxInfo('a.id as box_id,d.id as hotel_id', $map);
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
        }else{
            $pre_time = time();
            $signinfo = $this->checkSigninTime($pre_time);
        }

        $m_usersign = new \Common\Model\Smallapp\UserSigninModel();
        $where = array('openid'=>$openid,'box_mac'=>$box_mac);
        $now_date = date('Y-m-d');
        $begin_time = $now_date." 00:00:00";
        $end_time = $now_date." 23:59:59";
        $where['add_time'] = array(array('egt',$begin_time),array('elt',$end_time), 'and');
        $res_sign = $m_usersign->getDataList('id',$where,'id desc',0,1);
        if($res_sign['total']>=2 || $signinfo['is_signin']==2){
            $this->to_back(93052);
        }

        $add_data = array('openid'=>$openid,'box_mac'=>$box_mac,'signin_time'=>date('Y-m-d H:i:s'));
        $id = $m_usersign->addData($add_data);

        $cache_data = array('id'=>$id,'openid'=>$openid,'box_mac'=>$box_mac,'nowtime'=>time());
        $redis->set($cache_key,json_encode($cache_data),18000);

        $m_taskuser = new \Common\Model\Integral\TaskuserModel();
        $m_taskuser->getTask($openid,$box_info[0]['hotel_id']);

        $m_ads = new \Common\Model\AdsModel();
        $ads_where = array('hotel_id'=>$box_info[0]['hotel_id'],'state'=>1,'is_online'=>1);
        $res_ads = $m_ads->getWhere($ads_where, 'id,media_id');
        if(!empty($res_ads)){
            $media_ids = array();
            foreach ($res_ads as $av){
                $media_ids[]=$av['media_id'];
            }
            shuffle($media_ids);
            $media_id = $media_ids[0];
            $m_media = new \Common\Model\MediaModel();
            $res_media = $m_media->getMediaInfoById($media_id);
            $file_info = pathinfo($res_media['oss_path']);
            $filename = $file_info['basename'];

            $nowtime = getMillisecond();
            $message = array('action'=>5,'url'=>$res_media['oss_addr'],'filename'=>$filename,
                'forscreen_id'=>$nowtime,'resource_id'=>$nowtime);
            $m_netty = new \Common\Model\NettyModel();
            $res_netty = $m_netty->pushBox($box_mac,json_encode($message));
            if(isset($res_netty['error_code']) && $res_netty['error_code']==90109){
                $m_netty->pushBox($box_mac,json_encode($message));
            }

            $imgs = array($res_media['oss_path']);
            $data = array('openid'=>$openid,'box_mac'=>$box_mac,'action'=>5,'forscreen_char'=>'','forscreen_id'=>$nowtime,
                'mobile_brand'=>'iPhone','mobile_model'=>'iPhone XR','resource_id'=>$nowtime,'imgs'=>json_encode($imgs),'small_app_id'=>5);
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(5);
            $cache_key = C('SAPP_SCRREN').":".$box_mac;
            $redis->rpush($cache_key, json_encode($data));
        }

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
        $res = $m_box->getBoxListByHotelRelation($fields,$hotel_id);
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
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth,role_id';
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
        $where = array('openid'=>$openid,'type'=>3,'source'=>3);
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

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id as staff_id,a.level,merchant.id as merchant_id,merchant.hotel_id,merchant.is_purchase,merchant.mtype';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        $data['merchant_id'] = $res_staff[0]['merchant_id'];
        $data['is_purchase'] = intval($res_staff[0]['is_purchase']);
        $data['staff_level'] = intval($res_staff[0]['level']);

        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $where = array('merchant_id'=>$data['merchant_id'],'status'=>1);
        $dish_num = $m_goods->countNum($where);
        $data['dish_num'] = intval($dish_num);

        $m_order = new \Common\Model\Smallapp\OrderModel();
        $where = array('merchant_id'=>$data['merchant_id'],'otype'=>3);
        $order_all_num = $m_order->countNum($where);
        $data['dishorder_all_num'] = intval($order_all_num);
        if($data['dishorder_all_num']){
            $where = array('merchant_id'=>$data['merchant_id'],'otype'=>3);
            $where['status'] = array('in',array(1,13,14,15,16));
            $order_process_num = $m_order->countNum($where);
            $data['dishorder_process_num'] = intval($order_process_num);
        }else{
            $data['dishorder_process_num'] = 0;
        }

        $m_order = new \Common\Model\Smallapp\OrderModel();
        $where = array('merchant_id'=>$data['merchant_id'],'otype'=>5);
        $shoporder_all_num = $m_order->countNum($where);
        $data['shoporder_all_num'] = intval($shoporder_all_num);
        if($data['shoporder_all_num']){
            $shopwhere = array('merchant_id'=>$data['merchant_id'],'otype'=>5);
            $shopwhere['status'] = array('in',array('51','52'));
            $shoporder_process_num = $m_order->countNum($shopwhere);
            $data['shoporder_process_num'] = intval($shoporder_process_num);
        }else{
            $data['shoporder_process_num'] = 0;
        }
        $data['shoporder_all_num'] = 0;
        $data['shoporder_process_num'] = 0;

        $hotel_id = $res_staff[0]['hotel_id'];
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
        $income_fee = $withdraw_fee = 0;
        if($res_user['role_id']==3){
            $m_income = new \Common\Model\Smallapp\UserincomeModel();
            $fields = 'sum(income_fee) as total_income_fee';
            $where = array('user_id'=>$res_user['user_id'],'is_withdraw'=>0);
            $res_income = $m_income->getDataList($fields,$where,'id desc');
            if(!empty($res_income[0]['total_income_fee'])){
                $income_fee = $res_income[0]['total_income_fee'];
            }
            $fields = 'sum(income_fee) as total_income_fee';
            $where = array('user_id'=>$res_user['user_id'],'is_withdraw'=>0);
            $day_time = date("Y-m-d H:i:s",strtotime("-7 day"));
            $where['add_time'] = array('elt',$day_time);
            $res_income = $m_income->getDataList($fields,$where,'id desc');
            if(!empty($res_income[0]['total_income_fee'])){
                $withdraw_fee = $res_income[0]['total_income_fee'];
            }
        }
        $data['income_fee'] = $income_fee;
        $data['withdraw_fee'] = $withdraw_fee;
        $score = 0;
        if($data['staff_level']==2){
            $condition = array('staff_id'=>$res_staff[0]['staff_id'],'status'=>1);
            $m_comment = new \Common\Model\Smallapp\CommentModel();
            $res_score = $m_comment->getCommentInfo('avg(score) as score',$condition);
            if(!empty($res_score) && $res_score[0]['score']>=1){
                $score = sprintf("%01.1f",$res_score[0]['score']);
            }
        }
        $data['score'] = $score;

        $this->to_back($data);
    }

    public function edit(){
        $openid = $this->params['openid'];
        $name = $this->params['name'];
        $avatar_url = $this->params['avatar_url'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 5;
        $fields = 'id,openid,avatarUrl,nickName,gender,status';
        $res_user = $m_user->getOne($fields, $where);
        if (empty($res_user)) {
            $this->to_back(92010);
        }
        if($name || $avatar_url){
            if($name){
                $data = array('nickName'=>$name);
            }else{
                $avatar_url = 'https://'.C('OSS_HOST').'/'.$avatar_url;
                $data = array('avatarUrl'=>$avatar_url);
            }
            $m_user->updateInfo(array('id'=>$res_user['id']),$data);
            $data = array('message'=>'修改成功');
            $this->to_back($data);
        }else{
            $data = array('message'=>'修改失败');
            $this->to_back($data);
        }
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
        $fields = 'room_name,integral,content,type,integral_time,goods_id,source';
        $where = array('openid'=>$openid);
        if($type){
            $where['type'] = $type;
        }
        if(empty($idate))   $idate = date('Ym');
        if($idate){
            $where['DATE_FORMAT(integral_time, "%Y%m")'] = $idate;
        }
        $where['integral'] = array('gt',0);
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
                    $res_goods = $m_goods->getInfo(array('id'=>$v['goods_id']));
                    $content = $all_types[3]."{$res_goods['name']} {$v['content']}件";
                    if($info['integral']==0){
                        $info['integral']='计算中...';
                    }
                    break;
                case 4:
                case 5:
                    $res_goods = $m_goods->getInfo(array('id'=>$v['goods_id']));
                    $info['room_name'] = $res_goods['name'];
                    $content = $all_types[$v['type']];
                    break;
                case 6:
                    $content = $all_types[$v['type']];
                    break;
                default:
                    $content = "";
            }
            if($v['source']==4 && $content){
                $content = $all_types[$v['type']].'(分润)';
            }
            $info['content'] = $content;
            $datalist[] = $info;

        }
        $data = array('datalist'=>$datalist);
        $this->to_back($data);
    }

    public function integraltypes(){
        $openid = $this->params['openid'];
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

        $has_integral_date = 0;
        $m_userintegral_record = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $where = array('openid'=>$openid);
        $where['integral'] = array('gt',0);
        $res_record = $m_userintegral_record->getDataList('id,add_time',$where,'id desc',0,1);
        if($res_record['total']){
            $has_integral_date = date('Ym',strtotime($res_record['list'][0]['add_time']));
        }

        $date_key = 1200;
        foreach ($period as $k=>$dt) {
            $name = $dt->format("Y年m月");
            $dt_date = $dt->format("Ym");
            $date_list[] = array('id'=>$dt_date,'name'=>$name);
            $date_name_list[]=$name;
            if($dt_date==$has_integral_date){
                $date_key = $k;
            }
        }
        $date_list[] = array('id'=>date('Ym',strtotime($end_date)),'name'=>date('Y年m月',strtotime($end_date)));
        $date_name_list[] = date('Y年m月',strtotime($end_date));
        if($date_key==1200){
            $date_key = count($date_name_list)-1;
        }
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
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.openid,a.merchant_id,merchant.type',$where);
        if(empty($res_staff) || $res_staff[0]['type']!=2){
            $this->to_back(93001);
        }
        $all_nums = $page * $pagesize;
        $res_invites = $m_staff->getStaffsByOpenid($openid,0,$all_nums);
        $datalist = array();
        if(!empty($res_invites)){
            $m_user = new \Common\Model\Smallapp\UserModel();
            foreach ($res_invites as $v){
                $where = array('openid'=>$v['openid']);
                $fields = 'openid,avatarUrl,nickName';
                $res_user = $m_user->getOne($fields, $where);
                $res_user['invite_id'] = $v['parent_id'];
                $res_user['level'] = $v['level'];
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
        $where = array('openid'=>$openid,'small_app_id'=>5);
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        $staff_info = $m_staff->getInfo(array('openid'=>$openid,'parent_id'=>$invite_id,'status'=>1));
        if(empty($staff_info)){
            $this->to_back(93002);
        }
        $where = array('openid'=>$openid,'parent_id'=>$invite_id,'status'=>1);
        $m_staff->updateData($where, array('status'=>2));
        $this->to_back(array());
    }

    public function invite(){
        $openid = $this->params['openid'];
        /* $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $fields = 'id,hotel_id,bind_mobile,openid,type';
        $where = array('openid'=>$openid,'state'=>1,'flag'=>0);
        $res_invite_code = $m_hotel_invite_code->getOne($fields,$where); */
        
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'mt.status'=>1);
        $res_invite_code = $m_staff->alias('a')
                                   ->field('a.id,mt.hotel_id,a.openid,mt.type')
                                   ->join('savor_integral_merchant mt on mt.id=a.merchant_id','left')
                                   ->where($where)->find();
        
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
        
        $data= array('mobile'=>$mobile);
        $rt = $m_user->updateInfo(array('openid'=>$openid,'small_app_id'=>5), $data);
        if($rt){
            $this->to_back(10000);
        }else {
            $this->to_back(93010);
        }
    }
    /**
     * @desc 检查手机号是否分配邀请码
     */
    public function checkUser(){
        $this->to_back(10000);
        /* $mobile = $this->params['mobile'];
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
        } */
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
        }elseif($pre_time>$lunch_etime && $pre_time<=$dinner_etime){
            $over_time = $dinner_etime;
        }else{
            $over_time = '';
            $is_signin = 2;
        }
        if(!empty($over_time) && $now_time>$over_time){
            $is_signin = 1;
        }
        $res = array('is_signin'=>$is_signin,'signout_time'=>$over_time);
        return $res;
    }
}