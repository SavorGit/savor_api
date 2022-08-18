<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class MemberController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'join':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'mobile'=>1001,'source'=>1001,'sale_openid'=>1002,'idcode'=>1002);
                break;
        }
        parent::_init_();
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
                        'start_time'=>$start_time,'end_time'=>$res_coupon['end_time'],'ustatus'=>1);
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