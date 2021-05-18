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