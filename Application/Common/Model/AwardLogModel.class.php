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
	public function countAwardLog($deviceid){
	    $map['deviceid'] = $deviceid;
	    return $this->where($map)->count();
	}
}