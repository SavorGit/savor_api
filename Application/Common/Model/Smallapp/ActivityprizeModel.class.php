<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class ActivityprizeModel extends BaseModel{
	protected $tableName='smallapp_activity_prize';

    public function getTaskinfo($task,$utask=array()){
        $content = array();
        if($task['interact_num']>0){
            $info = array('name'=>'使用互动页投屏功能','num'=>$task['interact_num']);
            if(!empty($utask)){
                $finish_num = $utask['interact_num']>$task['interact_num']?$task['interact_num']:$utask['interact_num'];
                $info['finish_num'] = $finish_num;
            }
            $content[] = $info;
        }
        if($task['demand_hotplay_num']>0){
            $info = array('name'=>'点播互动页热播内容','num'=>$task['demand_hotplay_num']);
            if(!empty($utask)){
                $finish_num = $utask['demand_hotplay_num']>$task['demand_hotplay_num']?$task['demand_hotplay_num']:$utask['demand_hotplay_num'];
                $info['finish_num'] = $finish_num;
            }
            $content[] = $info;
        }
        if($task['demand_banner_num']>0){
            $info = array('name'=>'点击互动页上方图片','num'=>$task['demand_banner_num']);
            if(!empty($utask)){
                $finish_num = $utask['demand_banner_num']>$task['demand_banner_num']?$task['demand_banner_num']:$utask['demand_banner_num'];
                $info['finish_num'] = $finish_num;
            }
            $content[] = $info;
        }
        return $content;
    }
}