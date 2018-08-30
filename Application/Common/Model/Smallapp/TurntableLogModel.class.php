<?php
/**
 * @desc 小程序投屏日志记录
 */
namespace Common\Model\Smallapp;
use Think\Model;

class TurntableLogModel extends Model
{
	protected $tableName='smallapp_turntable_log';
	
	public function addInfo($data,$type=1){
	    if($type==1){
	        $ret = $this->add($data);
	        
	    }else {
	        $ret = $this->addAll($data);
	    }
	    return $ret;
	}
	public function update_join_info($activity_id){
	    $now_time = date('Y-m-d H:i:s');
	    $sql ="update savor_smallapp_turntable_log 
	           set `join_num`=`join_num`+1,update_time='".$now_time."'
	           where `activity_id`=".$activity_id." limit 1";
	    $ret =$this->execute($sql);
	    return $ret;
	}
	public function update_start_info($activity_id,$is_start){
	    $now_time = date('Y-m-d H:i:s');
	    $sql = "update savor_smallapp_turntable_log
	            set `is_start`=1
	            where `activity_id`=".$activity_id." limit 1";
	    $ret = $this->execute($sql);
	    return $ret;
	    
	}
}