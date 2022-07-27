<?php
namespace Smallsale21\Controller;
use \Common\Controller\CommonController as CommonController;

class ConfigController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getConfig':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1002,'action'=>1002);
                break;
        }
        parent::_init_();
    }

    public function getConfig(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];
        $action = $this->params['action'];
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
                    $data = array('is_subscribe'=>$is_subscribe);
                    $m_user->updateInfo(array('id'=>$user_info['id']),$data);
                }else{
                    $subscribe_status = 2;
                }
            }
        }

        $is_have_adv = 0;
        $m_ads = new \Common\Model\AdsModel();
        $ads_where = array('hotel_id'=>$hotel_id,'state'=>1,'is_online'=>1,'type'=>3);
        $res_ads = $m_ads->getWhere($ads_where, 'id,media_id');
        if(!empty($res_ads)){
            $is_have_adv = 1;
        }
        $m_hotelext = new \Common\Model\HotelExtModel();
        $res_hotelext = $m_hotelext->getOnerow(array('hotel_id'=>$hotel_id));

        $is_activity = intval($res_hotelext['is_activity']);
        $is_activity = 0;
        $activity_next_time = time() + 7200;
        $day = 0;
        $hour = date('G',$activity_next_time);
        $activity_lottery_time = array($day,intval($hour));

        $minute = intval(date('i'));
        $last_m = 10 - $minute%10;
        $now_time = time()+($last_m*60);
        $initiate_hour = date('G',$now_time);
        $initiate_minute = date('i',$now_time);
        $activity_initiate_time = array($day,intval($initiate_hour),intval($initiate_minute/10));

        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = C('SAPP_SALE').'openmoneytask:'.date('Ymd').':'.$openid;
        $res_cache = $redis->get($cache_key);
        $is_open_money_task = 0;
        $money_task_img_path = 'http://'.C('OSS_HOST').'/media/resource/EMa5QMdzEW.png';
        $money_task_img = '';
        if(!empty($res_cache)){
            $is_open_money_task = intval($res_cache);
            if($is_open_money_task==1){
                $money_task_img = $money_task_img_path;
                $is_open_money_task = 0;
            }
        }else{
            if(!empty($action)){
                $m_staff = new \Common\Model\Integral\StaffModel();
                $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
                $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,a.level,a.permission,a.merchant_id,merchant.type,merchant.hotel_id',$where);
                if($res_staff[0]['type']==2){
                    $m_task = new \Common\Model\Integral\TaskHotelModel();
                    $where = array('a.hotel_id'=>$hotel_id,'task.type'=>2,'task.task_type'=>21,'task.status'=>1,'task.flag'=>1);
                    $where['task.end_time'] = array('egt',date('Y-m-d H:i:s'));
                    $res_task = $m_task->getHotelTasks('a.id',$where);
                    if(!empty($res_task)){
                        $is_open_money_task = 1;
                    }else{
                        $is_open_money_task = 0;
                    }
                }else{
                    $is_open_money_task = 0;
                }
                $redis->set($cache_key,$is_open_money_task,86400);
                if($is_open_money_task==1){
                    $money_task_img = $money_task_img_path;
                }
            }
        }
        if(empty($money_task_img)){
            $m_usertask = new \Common\Model\Smallapp\UserTaskModel();
            $where = array('openid'=>$openid,'status'=>2);
            $res_usertask = $m_usertask->getDataList('*',$where,'id desc',0,1);
            if($res_usertask['total']>0){
                $money_task_img = $money_task_img_path;
            }
        }
        $activity_wait_time = array();
        for ($i=1;$i<7;$i++){
            $i_time = $i*10;
            $is_select = false;
            if($i==3){
                $is_select = true;
            }
            $activity_wait_time[]=array('name'=>"{$i_time}分钟",'value'=>$i_time,'is_select'=>$is_select);
        }

        $res_data = array('is_have_adv'=>$is_have_adv,'subscribe_status'=>$subscribe_status,
            'is_activity'=>$is_activity,'activity_lottery_time'=>$activity_lottery_time,
            'is_open_money_task'=>$is_open_money_task,'money_task_img'=>$money_task_img,
            'activity_initiate_time'=>$activity_initiate_time,'activity_wait_time'=>$activity_wait_time);
        $this->to_back($res_data);
    }




}