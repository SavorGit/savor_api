<?php
namespace Common\Model\Integral;
use Common\Model\BaseModel;

class TaskHotelModel extends BaseModel{
	protected $tableName='integral_task_hotel';

	public function getHotelTasks($fileds,$where){
        $data = $this->alias('a')
            ->field($fileds)
            ->join('savor_integral_task task on a.task_id=task.id','left')
            ->where($where)
            ->select();
        return $data;
    }

    public function getHotelTaskList($fields,$where,$order,$start,$size){
        $task_list = $this->alias('a')
            ->join('savor_integral_task task on a.task_id=task.id','left')
            ->join('savor_media media on task.media_id=media.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($start,$size)
            ->select();
        return $task_list;
    }

    public function getHotelTaskGoodsList($fields,$where,$order){
        $task_list = $this->alias('a')
            ->join('savor_integral_task task on a.task_id=task.id','left')
            ->join('savor_smallapp_dishgoods g on task.goods_id=g.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->select();
        return $task_list;
    }

    public function getTaskinfo($task,$utask=array()){
        $content = array();
        if($task['meal_num']>0){
            $info = array('name'=>'互动饭局数','num'=>$task['meal_num'],'type'=>'meal');
            if(!empty($utask)){
                $finish_num = $utask['meal_num']>$task['meal_num']?$task['meal_num']:$utask['meal_num'];
                $info['finish_num'] = $finish_num;
            }
            $content[] = $info;
        }
        if($task['comment_num']>0){
            $info = array('name'=>'邀请客人对您进行评价','num'=>$task['comment_num'],'type'=>'comment');
            if(!empty($utask)){
                $finish_num = $utask['comment_num']>$task['comment_num']?$task['comment_num']:$utask['comment_num'];
                $info['finish_num'] = $finish_num;
            }
            $content[] = $info;
        }
        if($task['interact_num']>0){
            $info = array('name'=>'邀请客人扫码进行投屏','num'=>$task['interact_num'],'type'=>'interact');
            if(!empty($utask)){
                $finish_num = $utask['interact_num']>$task['interact_num']?$task['interact_num']:$utask['interact_num'];
                $info['finish_num'] = $finish_num;
            }
            $content[] = $info;
        }
        if($task['lottery_num']>0){
            $info = array('name'=>'邀请客人抽奖','num'=>$task['lottery_num'],'type'=>'lottery');
            if(!empty($utask)){
                $finish_num = $utask['lottery_num']>$task['lottery_num']?$task['lottery_num']:$utask['lottery_num'];
                $info['finish_num'] = $finish_num;
            }
            $content[] = $info;
        }
        return $content;
    }
}