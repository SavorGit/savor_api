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
            ->join('savor_smallapp_activity activity on a.activity_id=activity.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }

    public function receiveTastewine($hotel_id,$openid){
        $oss_host = C('OSS_HOST');
        $taste_wine = array('is_pop_wind'=>false,'status'=>0,'height_img'=>'','width_img'=>'','message'=>'','tips'=>'');
        $now_time = date('Y-m-d H:i:s');
        $meal_time = C('MEAL_TIME');
        $lunch_stime = date("Y-m-d {$meal_time['lunch'][0]}:00");
        $lunch_etime = date("Y-m-d {$meal_time['lunch'][1]}:00");
        $dinner_stime = date("Y-m-d {$meal_time['dinner'][0]}:00");
        $dinner_etime = date("Y-m-d {$meal_time['dinner'][1]}:59");
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
            $m_activityhotel = new \Common\Model\Smallapp\ActivityhotelModel();
            $fields = 'a.activity_id,activity.image_url,activity.portrait_image_url';
            $where = array('a.hotel_id'=>$hotel_id,'activity.type'=>6);
            $where['activity.start_time'] = array('elt',date('Y-m-d H:i:s'));
            $where['activity.end_time'] = array('egt',date('Y-m-d H:i:s'));
            $res_activityhotel = $m_activityhotel->getActivityhotelDatas($fields,$where,'a.id desc','0,1','');
            if(!empty($res_activityhotel)){
                $taste_wine_activity_id = $res_activityhotel[0]['activity_id'];
                $res_activity_apply = $this->getApplylist('count(*) as num',array('activity_id'=>$taste_wine_activity_id,'openid'=>$openid),'id desc','');
                $taste_wine_apply_num= 0;
                if(!empty($res_activity_apply)){
                    $taste_wine_apply_num = $res_activity_apply[0]['num'];
                }
                if($taste_wine_apply_num<3){
                    $taste_wine['activity_id'] = $taste_wine_activity_id;
                    $taste_wine['is_pop_wind'] = true;
                    $taste_wine['width_img'] = 'http://'.$oss_host.'/'.$res_activityhotel[0]['image_url'];
                    $taste_wine['height_img'] = 'http://'.$oss_host.'/'.$res_activityhotel[0]['portrait_image_url'];
                    $where = array('activity_id'=>$taste_wine_activity_id,'openid'=>$openid);
                    $where['add_time'] = array(array('egt',$meal_stime),array('elt',$meal_etime));
                    $res_activity_apply = $this->getApplylist('*',$where,'id desc','');
                    if(!empty($res_activity_apply) && $res_activity_apply[0]['status']==1){
                        unset($where['openid']);
                        $res_activity_apply = $this->getApplylist('openid',$where,'id asc','');
                        $get_position = 0;
                        foreach ($res_activity_apply as $k=>$v){
                            if($v['openid']==$openid){
                                $get_position = $k+1;
                                break;
                            }
                        }
                        $taste_wine['status'] = 2;
                        $taste_wine['message'] = "恭喜您领到本饭局第{$get_position}份品鉴酒";
                        $taste_wine['tips'] = '请向服务员出示此页面领取';
                    }
                }
            }
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
}