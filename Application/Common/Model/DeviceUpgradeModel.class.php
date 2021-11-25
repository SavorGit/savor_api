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
	public function getNewSmallApkInfo($hotelid,$versionCode = '',$device_type=1){
	    $where = "";
	    if(!empty($versionCode)){
	        $where .= " and du.version_min<='".$versionCode."' and du.version_max>='".$versionCode."'";
	    }
	    $sql =" select du.id,du.version,du.update_type,dv.version_name 

                from savor_device_upgrade du
                left join savor_device_version dv on du.version=dv.version_code

                where du.device_type=$device_type and dv.device_type = $device_type
	            and  (du.hotel_id LIKE CONCAT('%,".$hotelid.",%') OR du.hotel_id IS NULL) and du.state=1 $where order by du.id desc  limit 1";
	    $result = $this->query($sql);
	    return $result[0];   
	}
}