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
	    $map['date_time'] = $date; 
	    return $this->where($map)->count();
	}
	public function addInfo($data){
	    return $this->add($data);
	}
}