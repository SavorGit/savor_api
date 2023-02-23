<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class SellwineActivityHotelModel extends BaseModel{
	protected $tableName='sellwine_activity_hotel';

	public function getSellwineActivity($hotel_id,$openid,$source=1,$finance_goods_id=0){
	    /*
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,unionId,mobile',$where,'');
        if(!empty($user_info['unionId'])){
            $where = array('unionId'=>$user_info['unionId'],'small_app_id'=>5);
            $res_sale_user = $m_user->getOne('id,openid,unionId',$where,'');
            if(!empty($res_sale_user)){
                return array();
            }
        }
        if(!empty($user_info['mobile'])){
            $where = array('mobile'=>$user_info['mobile'],'small_app_id'=>5);
            $res_sale_user = $m_user->getOne('id,openid,unionId',$where,'');
            if(!empty($res_sale_user)){
                return array();
            }
        }
	    */
	    //$source 1弹框 2获取当前饭点内活动数据
        $now_time = date('Y-m-d H:i:s');
        $fields = 'a.activity_id,activity.start_date,activity.end_date,activity.lunch_start_time,activity.lunch_end_time,
        activity.dinner_start_time,activity.dinner_end_time,activity.daily_money_limit,activity.money_limit,activity.media_id';
        $where = array('a.hotel_id'=>$hotel_id,'a.status'=>1,'activity.status'=>1);
        $where['activity.start_date'] = array('elt',$now_time);
        $where['activity.end_date'] = array('egt',$now_time);
        $res_data = $this->alias('a')
            ->join('savor_sellwine_activity activity on a.activity_id=activity.id','left')
            ->field($fields)
            ->where($where)
            ->order('a.activity_id desc')
            ->limit('0,1')
            ->select();
        $activity_data = array();
        if(!empty($res_data)){
            $activity_id = $res_data[0]['activity_id'];
            $daily_money_limit = $res_data[0]['daily_money_limit'];
            $money_limit = $res_data[0]['money_limit'];

            $lunch_stime = date("Y-m-d {$res_data[0]['lunch_start_time']}");
            $lunch_etime = date("Y-m-d {$res_data[0]['lunch_end_time']}");
            $dinner_stime = date("Y-m-d {$res_data[0]['dinner_start_time']}");
            $dinner_etime = date("Y-m-d {$res_data[0]['dinner_end_time']}");
            $meal_type = '';
            $meal_stime = $meal_etime = '';
            if($now_time>=$lunch_stime && $now_time<=$lunch_etime){
                $meal_type = 'lunch';
                $meal_stime = $lunch_stime;
                $meal_etime = $lunch_etime;
            }elseif($now_time>=$dinner_stime && $now_time<=$dinner_etime){
                $meal_type = 'dinner';
                $meal_stime = $dinner_stime;
                $meal_etime = $dinner_etime;
            }
            if($meal_type){
                if($source==1){
                    $m_order = new \Common\Model\Smallapp\OrderModel();
                    $where = array('openid'=>$openid,'otype'=>9,'sellwine_activity_id'=>$activity_id);
                    $where['add_time'] = array(array('egt',$meal_stime),array('elt',$meal_etime));
                    $res_order = $m_order->getALLDataList('id,idcode',$where,'id desc','0,1','');
                    $is_new_order = 1;
                    $m_sellwine_redpacket = new \Common\Model\Smallapp\SellwineActivityRedpacketModel();
                    if(!empty($res_order)){
                        $order_id = $res_order[0]['id'];
                        if(empty($res_order[0]['idcode'])){
                            $is_new_order = 0;
                            $activity_data = array('type'=>2,'order_id'=>$order_id,'message'=>'您有待领取的现金红包');

                            $rwhere = array('openid'=>$openid,'sellwine_activity_id'=>$activity_id);
                            $res_data = $m_sellwine_redpacket->getDataList('sum(money) as total_money',$rwhere,'');
                            $total_money = intval($res_data[0]['total_money']);
                            if($total_money>=$money_limit){
                                $activity_data = array();
                            }else{
                                $rwhere['DATE(add_time)'] = date('Y-m-d');
                                $daily_money = intval($res_data[0]['total_money']);
                                if($daily_money>=$daily_money_limit){
                                    $activity_data = array();
                                }
                            }
                        }else{
                            $res_sellwine_redpacket = $m_sellwine_redpacket->getInfo(array('order_id'=>$order_id));
                            if(empty($res_sellwine_redpacket)){
                                $is_new_order = 0;
                                $activity_data = array('type'=>3,'order_id'=>$order_id,'idcode'=>$res_order[0]['idcode']);
                            }
                        }
                    }
                    if($is_new_order==1){
                        $m_sell_activity_goods = new \Common\Model\Smallapp\SellwineActivityGoodsModel();
                        $where = array('activity_id'=>$activity_id,'status'=>1);
                        $res_goods = $m_sell_activity_goods->getALLDataList('finance_goods_id,money',$where,'money desc','','');
                        $redis = new \Common\Lib\SavorRedis();
                        $redis->select(9);
                        $key = C('FINANCE_HOTELSTOCK').':'.$hotel_id;
                        $res_cache = $redis->get($key);
                        if(!empty($res_cache)) {
                            $hotel_stock = json_decode($res_cache, true);
                            $is_has_stock = 0;
                            foreach ($res_goods as $v){
                                if(in_array($v['finance_goods_id'],$hotel_stock['goods_ids'])){
                                    $is_has_stock = 1;
                                    break;
                                }
                            }
                            if($is_has_stock){
                                $money = intval($res_goods[0]['money']);
                                $tips = date('H:i',strtotime($meal_etime)).'之前下单';
                                $message = "每瓶酒最多可获得{$money}元现金红包";
                                $m_media = new \Common\Model\MediaModel();
                                $res_media = $m_media->getMediaInfoById($res_data[0]['media_id']);
                                $activity_data = array('type'=>1,'activity_id'=>$activity_id,'image_url'=>$res_media['oss_addr'],'tips'=>$tips,'message'=>$message);
                            }
                        }
                    }

                }else{
                    $m_sell_activity_goods = new \Common\Model\Smallapp\SellwineActivityGoodsModel();
                    $where = array('activity_id'=>$activity_id,'status'=>1);
                    $res_goods = $m_sell_activity_goods->getALLDataList('finance_goods_id,money',$where,'money desc','','');
                    $redis = new \Common\Lib\SavorRedis();
                    $redis->select(9);
                    $key = C('FINANCE_HOTELSTOCK').':'.$hotel_id;
                    $res_cache = $redis->get($key);
                    $message = '';
                    $goods_data = array();
                    if(!empty($res_cache)) {
                        $hotel_stock = json_decode($res_cache, true);
                        foreach ($res_goods as $v){
                            if(in_array($v['finance_goods_id'],$hotel_stock['goods_ids'])){
                                $goods_data[$v['finance_goods_id']]=$v;
                            }
                        }

                        if(!empty($goods_data)){
                            if($finance_goods_id){
                                if(isset($goods_data[$finance_goods_id])){
                                    $money = intval($goods_data[$finance_goods_id]['money']);
                                    $message = date('H:i',strtotime($meal_etime)).'之前下单'."每瓶可获得{$money}元现金红包";
                                }
                            }else{
                                $money = intval($res_goods[0]['money']);
                                $message = date('H:i',strtotime($meal_etime)).'之前下单'."每瓶酒最多可获得{$money}元现金红包";
                            }
                        }
                    }
                    $activity_data = $res_data[0];
                    $activity_data['goods_data'] = $goods_data;
                    $activity_data['message'] = $message;
                }

            }
        }
        return $activity_data;
    }

}