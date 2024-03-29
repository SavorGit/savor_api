<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class ActivityapplyModel extends BaseModel{
	protected $tableName='smallapp_activityapply';

	public function getApplylist($fields,$where,$orderby,$group=''){
        $data = $this->field($fields)->where($where)->order($orderby)->group($group)->select();
        return $data;
    }

    public function getApplyDatas($fields,$where,$order,$limit,$group){
        $data = $this->alias('a')
            ->join('savor_smallapp_activity_prize prize on a.prize_id=prize.id','left')
            ->join('savor_smallapp_activity activity on a.activity_id=activity.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }

    public function receiveTastewine($hotel_id,$box_mac,$openid){
        $oss_host = get_oss_host();
        $taste_wine = array('is_pop_wind'=>false,'status'=>0,'height_img'=>'','width_img'=>'','message'=>'','tips'=>'');
        if($hotel_id==0 || empty($box_mac) || empty($openid)){
            return $taste_wine;
        }

        $m_activityhotel = new \Common\Model\Smallapp\ActivityhotelModel();
        $res_taste = $m_activityhotel->getHotelTastewineActivity($hotel_id);
        if(empty($res_taste)){
            return $taste_wine;
        }
        $taste_wine_activity_id = $res_taste['activity']['activity_id'];
        $people_num = $res_taste['activity']['people_num'];
        $bottle_num = $res_taste['activity']['bottle_num'];
        $join_num = $res_taste['activity']['join_num'];
        $meal_get_num = $res_taste['activity']['meal_get_num'];
        $box_get_num = $res_taste['activity']['box_get_num'];
        $meal_stime = $res_taste['meal_stime'];
        $meal_etime = $res_taste['meal_etime'];
        $where = array('activity_id'=>$taste_wine_activity_id,'openid'=>$openid);
        $where['add_time'] = array(array('egt',$meal_stime),array('elt',$meal_etime));
        $res_activity_apply = $this->getApplylist('*',$where,'id desc','');
        if(!empty($res_activity_apply)){
            if($res_activity_apply[0]['status']==1){
                $taste_wine['activity_id'] = $taste_wine_activity_id;
                $taste_wine['is_pop_wind'] = true;
                $taste_wine['width_img'] = $oss_host.$res_taste['activity']['image_url'];
                $taste_wine['height_img'] = $oss_host.$res_taste['activity']['portrait_image_url'];
                $taste_wine['status'] = 2;
                $taste_wine['message'] = '恭喜您领到本饭局品鉴酒';
                $taste_wine['join_time'] = date('Y.m.d H:i:s',strtotime($res_activity_apply[0]['add_time']));
                $taste_wine['tips'] = '已通知餐厅为您送酒，为节省等待时间，您可直接向服务员询问';
            }
        }else{
            $hotel_all_num = $bottle_num*$people_num;
            $bwhere = array('activity_id'=>$taste_wine_activity_id,'hotel_id'=>$hotel_id);
            $res_activity_allhotel_apply = $this->getApplylist('count(*) as num',$bwhere,'id desc','');
            $taste_wine_all_hotel_num = 0;
            if(!empty($res_activity_allhotel_apply)){
                $taste_wine_all_hotel_num = $res_activity_allhotel_apply[0]['num'];
            }
            if($taste_wine_all_hotel_num>=$hotel_all_num){
                return $taste_wine;
            }

            $bwhere['add_time'] = array(array('egt',$meal_stime),array('elt',$meal_etime));
            $res_activity_hotel_apply = $this->getApplylist('count(*) as num',$bwhere,'id desc','');
            $taste_wine_hotel_num = 0;
            if(!empty($res_activity_hotel_apply)){
                $taste_wine_hotel_num = $res_activity_hotel_apply[0]['num'];
            }
            if($taste_wine_hotel_num>=$meal_get_num){
                return $taste_wine;
            }
            $bwhere['box_mac'] = $box_mac;
            $res_activity_box_apply = $this->getApplylist('count(*) as num',$bwhere,'id desc','');
            $taste_wine_box_num = 0;
            if(!empty($res_activity_box_apply)){
                $taste_wine_box_num = $res_activity_box_apply[0]['num'];
            }
            if($taste_wine_box_num>=$box_get_num){
                return $taste_wine;
            }

            $u_fields = 'count(a.id) as num';
            $u_where = array('a.openid'=>$openid,'activity.type'=>6);
            $u_where['a.add_time'] = array('egt','2023-03-01 00:00:00');
            $res_activity_user_apply = $this->getApplyDatas($u_fields,$u_where,'a.id desc','','');
            $taste_user_wine_apply_num = 0;
            if(!empty($res_activity_user_apply)){
                $taste_user_wine_apply_num = $res_activity_user_apply[0]['num'];
            }
            if($taste_user_wine_apply_num>=$join_num){
                return $taste_wine;
            }
            $taste_wine['activity_id'] = $taste_wine_activity_id;
            $taste_wine['is_pop_wind'] = true;
            $taste_wine['width_img'] = $oss_host.$res_taste['activity']['image_url'];
            $taste_wine['height_img'] = $oss_host.$res_taste['activity']['portrait_image_url'];
        }

        return $taste_wine;
    }

    public function finishPrizeTask($openid,$action){
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(1);
        $cache_key = C('SAPP_LOTTERY_TASK').$openid;
        $res = $redis->get($cache_key);
        if(!empty($res)){
            $task_data = json_decode($res,true);
            $now_time = date('Y-m-d H:i:s');
            if($now_time>=$task_data['start_time'] && $now_time<=$task_data['end_time']){
                $activityapply_id = $task_data['activityapply_id'];
                if($task_data['task']['demand_banner_num']>0){
                    if($action==14){
                        $this->where(array('id'=>$activityapply_id))->setInc('demand_banner_num',1);
                    }
                }
                if($task_data['task']['demand_hotplay_num']>0){
                    if(in_array($action,array(16,17))){
                        $this->where(array('id'=>$activityapply_id))->setInc('demand_hotplay_num',1);
                    }
                }
                if($task_data['task']['interact_num']>0){
                    $interact_actions = array(
                        '2'=>'视频投屏/滑动',
                        '3'=>'切片视频投屏',
                        '4'=>'多图投屏',
                        '5'=>'视频点播',
                        '8'=>'重投',
                        '30'=>'投屏文件',
                        '31'=>'投屏文件图片',
                        '41'=>'投屏欢迎词',
                        '42'=>'商务宴请欢迎词',
                        '43'=>'生日聚会欢迎词',
                        '44'=>'分享文件到电视',
                        '45'=>'分享名片到电视',
                        '32'=>'商务宴请投屏文件图片',
                        '46'=>'商务宴请图片投屏',
                        '47'=>'商务宴请视频投屏',
                        '48'=>'生日聚会图片投屏',
                        '49'=>'生日聚会视频投屏',
                        '52'=>'评论',
                        '55'=>'首页致欢迎词',
                    );
                    if(isset($interact_actions[$action])){
                        $this->where(array('id'=>$activityapply_id))->setInc('interact_num',1);
                    }
                }
                $res_apply = $this->getInfo(array('id'=>$activityapply_id));
                if($res_apply['status']==4 && $res_apply['demand_banner_num']>=$task_data['task']['demand_banner_num'] && $res_apply['demand_hotplay_num']>=$task_data['task']['demand_hotplay_num'] && $res_apply['interact_num']>=$task_data['task']['interact_num']){
                    $this->updateData(array('id'=>$activityapply_id),array('status'=>5));
                }
            }
        }
        return true;
    }

    public function getTastewine($hotel_id){
        $m_activityhotel = new \Common\Model\Smallapp\ActivityhotelModel();
        $fields = 'a.activity_id,activity.image_url,activity.portrait_image_url,activity.lunch_start_time,activity.lunch_end_time,
        activity.dinner_start_time,activity.dinner_end_time,activity.wine_ml,activity.meal_get_num,activity.box_get_num,activity.bottle_num,activity.join_num,activity.finance_goods_id';
        $where = array('a.hotel_id'=>$hotel_id,'activity.type'=>6,'activity.status'=>1);
        $where['activity.start_time'] = array('elt',date('Y-m-d H:i:s'));
        $where['activity.end_time'] = array('egt',date('Y-m-d H:i:s'));
        $res_activityhotel = $m_activityhotel->getActivityhotelDatas($fields,$where,'a.id desc','0,1','');
        if(empty($res_activityhotel[0]['activity_id'])){
            return array();
        }
        $lunch_stime = date("Y-m-d {$res_activityhotel[0]['lunch_start_time']}");
        $lunch_etime = date("Y-m-d {$res_activityhotel[0]['lunch_end_time']}");
        $dinner_stime = date("Y-m-d {$res_activityhotel[0]['dinner_start_time']}");
        $dinner_etime = date("Y-m-d {$res_activityhotel[0]['dinner_end_time']}");
        $meal_type = '';
        $meal_stime = $meal_etime = '';
        $now_time = date('Y-m-d H:i:s');
        if($now_time>=$lunch_stime && $now_time<=$lunch_etime){
            $meal_type = 'lunch';
            $meal_stime = $lunch_stime;
            $meal_etime = $lunch_etime;
        }elseif($now_time>=$dinner_stime && $now_time<=$dinner_etime){
            $meal_type = 'dinner';
            $meal_stime = $dinner_stime;
            $meal_etime = $dinner_etime;
        }
        if(empty($meal_type)){
            return array();
        }
        $where = array('activity_id'=>$res_activityhotel[0]['activity_id'],'hotel_id'=>$hotel_id,'status'=>1);
        $where['add_time'] = array(array('egt',$meal_stime),array('elt',$meal_etime));
        $res_activity_apply = $this->getApplylist('id as activityapply_id,box_name,mobile',$where,'id desc','');
        if(empty($res_activity_apply)){
            return array();
        }
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $fields = 'g.id,g.finance_goods_id,g.name,g.detail_imgs';
        $res_goods = $m_hotelgoods->getGoodsList($fields,array('h.hotel_id'=>$hotel_id,'g.finance_goods_id'=>$res_activityhotel[0]['finance_goods_id']),'','0,1');
        $res_data = array('apply_list'=>$res_activity_apply,'goods'=>$res_goods[0],'activity'=>$res_activityhotel[0]);
        return $res_data;
    }
}