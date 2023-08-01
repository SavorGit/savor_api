<?php
namespace Common\Model;
use Think\Model;

class DeviceVersionModel extends Model{
	protected $tableName = 'device_version';
	public function getOneByVersionAndDevice($versionCode,$device_type){
	    $map['version_code'] = $versionCode;
	    $map['device_type'] = $device_type;
	    $result = $this->where($map)->find();
	    return $result;
	}
	
	public function getOneByVersionAndDeviceInfo($versionCode,$device_type){
	    $map['version_code'] = $versionCode;
	    $map['device_type']  = $device_type;
	    $map['model']        = 0;
	    $result = $this->where($map)->find();
	    return $result;
	}
}