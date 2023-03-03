<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class ActivityhotelModel extends BaseModel{
	protected $tableName='smallapp_activityhotel';

    public function getActivityhotelDatas($fields,$where,$order,$limit,$group){
        $data = $this->alias('a')
            ->join('savor_smallapp_activity activity on a.activity_id=activity.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }

    public function getHotelTastewineActivity($hotel_id,$time=''){
        $fields = 'a.activity_id,activity.image_url,activity.portrait_image_url,activity.lunch_start_time,activity.lunch_end_time,
        activity.dinner_start_time,activity.dinner_end_time,activity.meal_get_num,activity.box_get_num,activity.bottle_num,activity.people_num,activity.join_num,activity.finance_goods_id';
        $where = array('a.hotel_id'=>$hotel_id,'activity.type'=>6,'activity.status'=>1);
        $where['activity.start_time'] = array('elt',date('Y-m-d H:i:s'));
        $where['activity.end_time'] = array('egt',date('Y-m-d H:i:s'));
        $res_activityhotel = $this->getActivityhotelDatas($fields,$where,'a.id desc','0,1','');
        $res_data = array();
        if(!empty($res_activityhotel[0]['activity_id'])){
            $lunch_stime = date("Y-m-d {$res_activityhotel[0]['lunch_start_time']}");
            $lunch_etime = date("Y-m-d {$res_activityhotel[0]['lunch_end_time']}");
            $dinner_stime = date("Y-m-d {$res_activityhotel[0]['dinner_start_time']}");
            $dinner_etime = date("Y-m-d {$res_activityhotel[0]['dinner_end_time']}");
            $meal_type = '';
            $meal_stime = $meal_etime = '';
            if(empty($time)){
                $now_time = date('Y-m-d H:i:s');
            }else{
                $now_time = $time;
            }
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
                $redis = new \Common\Lib\SavorRedis();
                $redis->select(9);
                $key = C('FINANCE_HOTELSTOCK').':'.$hotel_id;
                $res_cache = $redis->get($key);
                if(!empty($res_cache)) {
                    $hotel_stock = json_decode($res_cache, true);
                    if(in_array($res_activityhotel[0]['finance_goods_id'],$hotel_stock['goods_ids'])){
                        $res_data = array('meal_type'=>$meal_type,'meal_stime'=>$meal_stime,'meal_etime'=>$meal_etime,'activity'=>$res_activityhotel[0]);
                    }
                }
            }
        }
        return $res_data;
    }

}