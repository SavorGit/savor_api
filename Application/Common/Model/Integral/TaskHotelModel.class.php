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

    public function getTaskinfo($task,$utask=array()){
        $content = array();
        if($task['meal_num']>0){
            $info = array('name'=>'互动饭局数','num'=>$task['meal_num']);
            if(!empty($utask)){
                $finish_num = $utask['meal_num']>$task['meal_num']?$task['meal_num']:$utask['meal_num'];
                $info['finish_num'] = $finish_num;
            }
            $content[] = $info;
        }
        if($task['comment_num']>0){
            $info = array('name'=>'邀请客人对您进行评价','num'=>$task['comment_num']);
            if(!empty($utask)){
                $finish_num = $utask['comment_num']>$task['comment_num']?$task['comment_num']:$utask['comment_num'];
                $info['finish_num'] = $finish_num;
            }
            $content[] = $info;
        }
        if($task['interact_num']>0){
            $info = array('name'=>'邀请客人扫码进行投屏','num'=>$task['interact_num']);
            if(!empty($utask)){
                $finish_num = $utask['interact_num']>$task['interact_num']?$task['interact_num']:$utask['interact_num'];
                $info['finish_num'] = $finish_num;
            }
            $content[] = $info;
        }
        return $content;
    }
}