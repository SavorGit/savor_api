<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class HeartLogModel extends Model
{
	protected $tableName='heart_log';
	public function truncateTable(){
	    $sql ="TRUNCATE TABLE `savor_heart_log`";
	    return $this->execute($sql);
	}
	// TRUNCATE TABLE
	public function getOnlineHotel($where,$fields = '*'){
	    $result = $this->field($fields)->where($where)->group('hotel_id')->select();
	    /* echo $this->getLastSql();
	    echo "<br>"; */
	    return $result;
	}

	public function getInfo($fileds,$where,$order){
	    $data = $this->field($fileds)->where($where)->order($order)->find();
	    return $data;

	}
	public function getHotelHeartBox($where,$fields = '*',$group=''){
		$result = $this->field($fields)->where($where)->group($group)->select();
		return $result;
	}

	public function getLastHeartVersion($field,$where ){
		$sql = " SELECT $field FROM savor_device_version sd JOIN (SELECT MAX(last_heart_time) ltime,MAX(apk_version) ak FROM
 savor_heart_log WHERE $where) sa ON sa.ak  =  sd.version_code";
		$result = $this->query($sql);
		return $result;

	}
}
