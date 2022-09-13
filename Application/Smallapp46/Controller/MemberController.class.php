<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class MemberController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'joinvip':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'mobile'=>1001,'source'=>1001,'activity_id'=>1002,'idcode'=>1002);
                break;
            case 'join':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'mobile'=>1001,'source'=>1001,'sale_openid'=>1002,'idcode'=>1002);
                break;
        }
        parent::_init_();
    }

    public function joinvip(){
        $openid = $this->params['openid'];
        $mobile = $this->params['mobile'];
        $source = $this->params['source'];//来源1销售经理发起抽奖 2扫瓶码
        $activity_id = $this->params['activity_id'];
        $idcode = $this->params['idcode'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        if($source==1){
            if(empty($activity_id)){
                $this->to_back(1001);
            }
            $m_activity = new \Common\Model\Smallapp\ActivityModel();
            $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
            $sale_openid = $res_activity['openid'];
            $where = array('a.openid'=>$sale_openid,'a.status'=>1,'merchant.status'=>1);
            $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id';
            $res_staff = $m_staff->getMerchantStaff($fields,$where);
            if(empty($res_staff)){
                $this->to_back(93001);
            }
        }else{
            $m_stock_record = new \Common\Model\Finance\StockRecordModel();
            $record_info = $m_stock_record->getALLDataList('*',array('idcode'=>$idcode,'dstatus'=>1),'id desc','0,1','');
            if($record_info[0]['type']<5){
                $this->to_back(93096);
            }
            $sale_openid = $record_info[0]['op_openid'];
            $where = array('a.openid'=>$sale_openid,'a.status'=>1,'merchant.status'=>1);
            $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id';
            $res_staff = $m_staff->getMerchantStaff($fields,$where);
            if(empty($res_staff)){
                $this->to_back(93001);
            }
        }
        $where = array('openid'=>$openid);
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res_user = $m_user->getOne('id,mobile,vip_level,buy_wine_num', $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }

        $buy_wine_num = intval($res_user['buy_wine_num']);
        $now_vip_level = 0;
        if($source==1){
            $m_activity_apply = new \Common\Model\Smallapp\ActivityapplyModel();
            $where = array('a.activity_id'=>$activity_id,'a.openid'=>$openid,'activity.type'=>14);
            $res_apply = $m_activity_apply->getApplyDatas('a.*,prize.type as prize_type',$where,'a.id desc','0,1','');
            if($res_user['vip_level']==0){
                if(!empty($res_apply) && in_array($res_apply[0]['prize_type'],array(1,2,4,5))){
                    $now_vip_level = 2;
                    $buy_wine_num = $buy_wine_num+1;
                }else{
                    $now_vip_level = 1;
                }
                $data = array('mobile'=>$mobile,'vip_level'=>$now_vip_level,'invite_openid'=>$sale_openid,'invite_time'=>date('Y-m-d H:i:s'));
                if($buy_wine_num){
                    $data['buy_wine_num'] = $buy_wine_num;
                }
                $m_user->updateInfo(array('id'=>$res_user['id']),$data);
            }else{
                if(!empty($res_apply) && in_array($res_apply[0]['prize_type'],array(1,2,4,5))){
                    $level_buy_num = C('VIP_3_BUY_WINDE_NUM');
                    $buy_wine_num = $buy_wine_num+1;
                    if($buy_wine_num==1){
                        $now_vip_level = 2;
                    }elseif($buy_wine_num==$level_buy_num){
                        $now_vip_level = 3;
                    }
                    $data = array('buy_wine_num'=>$buy_wine_num);
                    if($now_vip_level){
                        $data['vip_level'] = $now_vip_level;
                    }
                    $m_user->updateInfo(array('id'=>$res_user['id']),$data);
                }
            }
        }else{
            if($res_user['vip_level']==0){
                $now_vip_level = 1;
                $data = array('mobile'=>$mobile,'vip_level'=>$now_vip_level,'invite_openid'=>$sale_openid,'invite_time'=>date('Y-m-d H:i:s'));
                $m_user->updateInfo(array('id'=>$res_user['id']),$data);
            }
        }
        if($res_user['vip_level']==0){
            $m_userintegral = new \Common\Model\Smallapp\UserIntegralrecordModel();
            $m_userintegral->finishInviteVipTask($sale_openid);

            $m_message = new \Common\Model\Smallapp\MessageModel();
            $m_message->recordMessage($sale_openid,$res_user['id'],9);
        }

        $coupon_list = array();
        if($now_vip_level>0){
            $m_sys_config = new \Common\Model\SysConfigModel();
            $sys_info = $m_sys_config->getAllconfig();
            $vip_coupons = json_decode($sys_info['vip_coupons'],true);
            if(!empty($vip_coupons) && !empty($vip_coupons[$now_vip_level])){
                $m_coupon = new \Common\Model\Smallapp\CouponModel();
                $res_all_coupon = $m_coupon->getALLDataList('*',array('id'=>array('in',$vip_coupons[$now_vip_level])),'id desc','','');
                $m_coupon_hotel = new \Common\Model\Smallapp\CouponHotelModel();
                $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
                foreach ($res_all_coupon as $v){
                    $res_coupon = $v;
                    if($res_coupon['start_hour']>0){
                        $now_stime = time()+($res_coupon['start_hour']*3600);
                        $start_time = date('Y-m-d H:i:s',$now_stime);
                    }else{
                        $start_time = $res_coupon['start_time'];
                    }
                    $res_coupon_hotel = $m_coupon_hotel->getDataList('hotel_id',array('coupon_id'=>$res_coupon['id']),'id desc');

                    if(!empty($res_coupon_hotel)){
                        if(count($res_coupon_hotel)==1){
                            $hotel_id = $res_coupon_hotel[0]['hotel_id'];
                        }else{
                            $hotel_id = 0;
                        }
                    }else{
                        $hotel_id = $res_staff[0]['hotel_id'];
                    }
                    $coupon_data = array('openid'=>$openid,'coupon_id'=>$res_coupon['id'],'money'=>$res_coupon['money'],'hotel_id'=>$hotel_id,
                        'min_price'=>$res_coupon['min_price'],'max_price'=>$res_coupon['max_price'],
                        'start_time'=>$start_time,'end_time'=>$res_coupon['end_time'],'ustatus'=>1,'type'=>2,'vip_level'=>$now_vip_level);
                    $coupon_user_id = $m_user_coupon->add($coupon_data);

                    if($res_coupon['min_price']>0){
                        $min_price = "满{$res_coupon['min_price']}可用";
                    }else{
                        $min_price = '无门槛立减券';
                    }
                    $start_time = date('Y.m.d H:i',strtotime($start_time));
                    $end_time = date('Y.m.d H:i',strtotime($res_coupon['end_time']));

                    $range_goods = array();
                    if($res_coupon['use_range']==1){
                        $range_str = '全部活动酒水';
                    }else{
                        $range_str = '部分活动酒水';
                        $range_finance_goods_ids = explode(',',trim($res_coupon['range_finance_goods_ids'],','));
                        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
                        if($hotel_id){
                            $res_data = $m_hotelgoods->getStockGoodsList($hotel_id,0,1000);
                        }else{
                            $res_data = $m_hotelgoods->getALLhotelStockGoodsList($res_coupon_hotel);
                        }
                        if(!empty($res_data)){
                            foreach ($res_data as $gv){
                                if(in_array($gv['finance_goods_id'],$range_finance_goods_ids)){
                                    $range_goods[]=$gv['name'];
                                }
                            }
                        }
                    }
                    if($hotel_id){
                        $hotel_num = 1;
                        $m_hotel = new \Common\Model\HotelModel();
                        $res_hotel = $m_hotel->getInfoById($hotel_id,'name');
                        $hotel_name = $res_hotel['name'];
                    }else{
                        $hotel_num = count($res_coupon_hotel);
                        $hotel_name = '多家餐厅可用';
                    }

                    $info = array('coupon_user_id'=>$coupon_user_id,'money'=>$res_coupon['money'],'min_price'=>$min_price,'hotel_num'=>$hotel_num,
                        'hotel_name'=>$hotel_name,'range_str'=>$range_str,'range_goods'=>$range_goods,'start_time'=>$start_time,'end_time'=>$end_time
                    );
                    $coupon_list[]=$info;
                }
            }
        }

        $resp_data = array('vip_level'=>$res_user['vip_level'].'-'.$now_vip_level,'coupon_list'=>$coupon_list);
        $this->to_back($resp_data);
    }

    public function join(){
        $openid = $this->params['openid'];
        $mobile = $this->params['mobile'];
        $source = $this->params['source'];//来源1销售经理发起抽奖 2扫瓶码
        $activity_id = $this->params['activity_id'];
        $idcode = $this->params['idcode'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        if($source==1){
            if(empty($activity_id)){
                $this->to_back(1001);
            }
            $m_activity = new \Common\Model\Smallapp\ActivityModel();
            $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
            $sale_openid = $res_activity['openid'];
            $where = array('a.openid'=>$sale_openid,'a.status'=>1,'merchant.status'=>1);
            $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
            $res_staff = $m_staff->getMerchantStaff($fields,$where);
            if(empty($res_staff)){
                $this->to_back(93001);
            }
        }else{
            $m_stock_record = new \Common\Model\Finance\StockRecordModel();
            $record_info = $m_stock_record->getALLDataList('*',array('idcode'=>$idcode,'dstatus'=>1),'id desc','0,1','');
            if($record_info[0]['type']<5){
                $this->to_back(93096);
            }
            $sale_openid = $record_info[0]['op_openid'];
            $where = array('a.openid'=>$sale_openid,'a.status'=>1,'merchant.status'=>1);
            $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
            $res_staff = $m_staff->getMerchantStaff($fields,$where);
            if(empty($res_staff)){
                $this->to_back(93001);
            }
        }
        $where = array('openid'=>$openid);
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res_user = $m_user->getOne('id,mobile,is_vip', $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $res_user['is_vip'] = 1;

        $m_hotel = new \Common\Model\HotelModel();
        $status = 2;//状态 1成为VIP 2已经是VIP
        $coupon_list = array();
        if($res_user['is_vip']==0){
            $status = 1;
            $data = array('mobile'=>$mobile,'is_vip'=>1,'invite_openid'=>$sale_openid,'invite_time'=>date('Y-m-d H:i:s'));
            $m_user->updateInfo(array('id'=>$res_user['id']),$data);
            $m_sys_config = new \Common\Model\SysConfigModel();
            $sys_info = $m_sys_config->getAllconfig();
            $vip_coupons = json_decode($sys_info['vip_coupons'],true);
            if(!empty($vip_coupons)){
                $m_coupon = new \Common\Model\Smallapp\CouponModel();
                $res_all_coupon = $m_coupon->getALLDataList('*',array('id'=>array('in',$vip_coupons)),'id desc','','');
                $m_coupon_hotel = new \Common\Model\Smallapp\CouponHotelModel();
                $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
                foreach ($res_all_coupon as $v){
                    $res_coupon = $v;
                    if($res_coupon['start_hour']>0){
                        $now_stime = time()+($res_coupon['start_hour']*3600);
                        $start_time = date('Y-m-d H:i:s',$now_stime);
                    }else{
                        $start_time = $res_coupon['start_time'];
                    }
                    $res_coupon_hotel = $m_coupon_hotel->getDataList('hotel_id',array('coupon_id'=>$res_coupon['id']),'id desc');
                    if(!empty($res_coupon_hotel)){
                        if(count($res_coupon_hotel)==1){
                            $hotel_id = $res_coupon_hotel[0]['hotel_id'];
                        }else{
                            $hotel_id = 0;
                        }
                    }else{
                        $hotel_id = $res_staff[0]['hotel_id'];
                    }

                    $coupon_data = array('openid'=>$openid,'coupon_id'=>$res_coupon['id'],'money'=>$res_coupon['money'],'hotel_id'=>0,
                        'min_price'=>$res_coupon['min_price'],'max_price'=>$res_coupon['max_price'],
                        'start_time'=>$start_time,'end_time'=>$res_coupon['end_time'],'ustatus'=>1,'type'=>2);
                    $coupon_user_id = $m_user_coupon->add($coupon_data);

                    if($res_coupon['min_price']>0){
                        $min_price = "满{$res_coupon['min_price']}可用";
                    }else{
                        $min_price = '无门槛立减券';
                    }
                    $start_time = date('Y.m.d H:i',strtotime($start_time));
                    $end_time = date('Y.m.d H:i',strtotime($res_coupon['end_time']));

                    $range_goods = array();
                    if($res_coupon['use_range']==1){
                        $range_str = '全部活动酒水';
                    }else{
                        $range_str = '部分活动酒水';
                        $range_finance_goods_ids = explode(',',trim($res_coupon['range_finance_goods_ids'],','));
                        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
                        if($hotel_id){
                            $res_data = $m_hotelgoods->getStockGoodsList($res_coupon['hotel_id'],0,1000);
                        }else{
                            $res_data = $m_hotelgoods->getALLhotelStockGoodsList($res_coupon_hotel);
                        }
                        if(!empty($res_data)){
                            foreach ($res_data as $gv){
                                if(in_array($gv['finance_goods_id'],$range_finance_goods_ids)){
                                    $range_goods[]=$gv['name'];
                                }
                            }
                        }
                    }
                    if($hotel_id){
                        $hotel_num = 1;
                        $res_hotel = $m_hotel->getInfoById($hotel_id,'name');
                        $hotel_name = $res_hotel['name'];
                    }else{
                        $hotel_num = count($res_coupon_hotel);
                        $hotel_name = '多家餐厅可用';
                    }

                    $info = array('coupon_user_id'=>$coupon_user_id,'money'=>$res_coupon['money'],'min_price'=>$min_price,'hotel_num'=>$hotel_num,
                        'hotel_name'=>$hotel_name,'range_str'=>$range_str,'range_goods'=>$range_goods,'start_time'=>$start_time,'end_time'=>$end_time
                    );
                    $coupon_list[]=$info;
                }
            }
            $m_userintegral = new \Common\Model\Smallapp\UserIntegralrecordModel();
            $m_userintegral->finishInviteVipTask($sale_openid);

            $m_message = new \Common\Model\Smallapp\MessageModel();
            $m_message->recordMessage($sale_openid,$res_user['id'],9);
        }
        $resp_data = array('status'=>$status,'coupon_list'=>$coupon_list);
        $this->to_back($resp_data);
    }
}