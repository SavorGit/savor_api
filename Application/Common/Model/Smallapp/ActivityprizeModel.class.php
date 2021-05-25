<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class ActivityprizeModel extends BaseModel{
	protected $tableName='smallapp_activity_prize';

    public function getTaskinfo($task,$utask=array()){
        $content = array();
        if($task['interact_num']>0){
            $info = array('name'=>'使用投屏页的功能','num'=>$task['interact_num'],'type'=>1);
            if(!empty($utask)){
                $finish_num = $utask['interact_num']>$task['interact_num']?$task['interact_num']:$utask['interact_num'];
                $info['finish_num'] = $finish_num;
            }
            $content[] = $info;
        }
        if($task['demand_hotplay_num']>0){
            $info = array('name'=>'点播投屏页的热播内容','num'=>$task['demand_hotplay_num'],'type'=>2);
            if(!empty($utask)){
                $finish_num = $utask['demand_hotplay_num']>$task['demand_hotplay_num']?$task['demand_hotplay_num']:$utask['demand_hotplay_num'];
                $info['finish_num'] = $finish_num;
            }
            $content[] = $info;
        }
        if($task['demand_banner_num']>0){
            $info = array('name'=>'点击投屏页的上方图片','num'=>$task['demand_banner_num'],'type'=>3);
            if(!empty($utask)){
                $finish_num = $utask['demand_banner_num']>$task['demand_banner_num']?$task['demand_banner_num']:$utask['demand_banner_num'];
                $info['finish_num'] = $finish_num;
            }
            $content[] = $info;
        }
        return $content;
    }
}