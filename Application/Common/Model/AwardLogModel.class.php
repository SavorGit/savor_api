<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class AwardLogModel extends Model
{
	protected $tableName='award_log';
	public function countAwardLog($deviceid,$date){
	    $map['deviceid'] = $deviceid;
	    $start_time = $date.' 00:00:00';
	    $end_time = $date.' 23:59:59';
	    $sql ="select count(id) as count  from `savor_award_log` where `deviceid`='".$map['deviceid']."' and `time`>='".$start_time."' and time<='".$end_time."'"; 
	    $ret = $this->query($sql);
	    return $ret[0]['count'];
	}
	public function addInfo($data){
	    return $this->add($data);
	}
}