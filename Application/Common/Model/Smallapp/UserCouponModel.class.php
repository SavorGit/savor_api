<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class UserCouponModel extends BaseModel{
	protected $tableName='smallapp_usercoupon';

    public function getUsercouponDatas($fields,$where,$order,$limit=''){
        $data = $this->alias('a')
            ->join('savor_smallapp_coupon coupon on a.coupon_id=coupon.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;
    }

    public function addVipCoupon($now_vip_level,$staff_hotel_id,$openid){
        $coupon_list = array();

        $m_sys_config = new \Common\Model\SysConfigModel();
        $sys_info = $m_sys_config->getAllconfig();
        $vip_coupons = json_decode($sys_info['vip_coupons'],true);
        if(!empty($vip_coupons) && !empty($vip_coupons[$now_vip_level])){
            $m_coupon = new \Common\Model\Smallapp\CouponModel();
            $res_all_coupon = $m_coupon->getALLDataList('*',array('id'=>array('in',$vip_coupons[$now_vip_level])),'id desc','','');
            $m_coupon_hotel = new \Common\Model\Smallapp\CouponHotelModel();
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
                    $hotel_id = $staff_hotel_id;
                }
                $coupon_data = array('openid'=>$openid,'coupon_id'=>$res_coupon['id'],'money'=>$res_coupon['money'],'hotel_id'=>$hotel_id,
                    'min_price'=>$res_coupon['min_price'],'max_price'=>$res_coupon['max_price'],
                    'start_time'=>$start_time,'end_time'=>$res_coupon['end_time'],'ustatus'=>1,'type'=>2,'vip_level'=>$now_vip_level);
                $coupon_user_id = $this->add($coupon_data);

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
        return $coupon_list;
    }
}