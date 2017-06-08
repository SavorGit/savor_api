<?php
namespace Common\Model;
use Think\Model;

class DeviceUpgradeModel extends Model{
	protected $tableName = 'device_upgrade';
	
	public function getLastOneByDevice($device_type){
	    $info = $this->where('device_type='.$device_type)->order('create_time desc')->find();
	    return $info;
	}
	
	public function getLastSmallPtInfo($hotelid,$versionCode = '',$device_type=1){
	    $where = '';
	    if(!empty($versionCode)){
	        $where .= " and version_min<='".$versionCode."' and version_max>='".$versionCode."'";
	    }
	    $sql =" select id,version,update_type from savor_device_upgrade where device_type=$device_type 
	            and  (hotel_id LIKE CONCAT('%,".$hotelid.",%') OR hotel_id IS NULL) $where order by id desc  limit 1";
	    
	    $result = $this->query($sql);
	    return $result[0];   
	}
}