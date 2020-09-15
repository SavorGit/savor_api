<?php
namespace Smallsale20\Controller;
use \Common\Controller\CommonController as CommonController;

class ConfigController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getConfig':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1002);
                break;
        }
        parent::_init_();
    }

    public function getConfig(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];
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
        $activity_next_time = time() + 7200;
        $day = 0;
        $hour = date('G',$activity_next_time);
        $activity_lottery_time = array($day,intval($hour));

        $res_data = array('is_have_adv'=>$is_have_adv,'subscribe_status'=>$subscribe_status,
            'is_activity'=>$is_activity,'activity_lottery_time'=>$activity_lottery_time);
        $this->to_back($res_data);
    }




}