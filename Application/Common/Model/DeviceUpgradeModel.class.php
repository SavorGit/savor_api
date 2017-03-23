<?php
namespace Common\Model;
use Think\Model;

class DeviceUpgradeModel extends Model{
	protected $tableName = 'device_upgrade';
	
	public function getLastOneByDevice($device_type){
	    $info = $this->where('device_type='.$device_type)->order('create_time desc')->find();
	    return $info;
	}
}