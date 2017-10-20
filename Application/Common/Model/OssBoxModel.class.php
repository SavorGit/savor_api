<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model;
use Think\Model;

class OssBoxModel extends Model
{
	protected $connection = 'DB_OSS';

	protected $tablePrefix = "oss_";

	protected $tableName='box_log';


	public function __consruct($name){
		parent::__construct();
		$this->tableName = $name;
	}

	public function saveData($data, $where) {
		$bool = $this->where($where)->save($data);
		return $bool;
	}

	public function addData($data) {
		$result = $this->add($data);
		return $result;
	}


	public function getRepairInfo($field, $where, $group, $order , $start,$size) {
		//上拉
		$where .= " and sru.flag = 0 and (sbo.flag=0 or sbo.flag is null)";

		$sql = "select ".$field." FROM savor_repair_box_user
		sru JOIN savor_sysuser sys ON sys.id = sru.userid
		left JOIN savor_box sbo ON sbo.mac = sru.mac JOIN
		savor_hotel sht ON sht.id = sru.hotel_id where ".$where." group by
		".$group." order by ".$order." limit ". $start.','.$size;

		$result = $this->query($sql);
		return $result;
	}

	public function getRepairUserInfo($fields, $map){
		$map['flag'] = 0;
		$data = $this->alias('sru')
					 ->field($fields)
					 ->join('savor_sysuser sys ON sys.id =
					 sru.userid')
					 ->where($map)
					 ->select();
		return $data;
	}


	public function getLastTime($mac) {
		/*$sql = " SELECT GREATEST(
 			( SELECT IFNULL((SELECT MAX(create_time) FROM
 		 	oss_box_log where oss_key like '%$mac%'),'1970-01-01
 		 	00:00:00')),
 		 	( SELECT IFNULL((SELECT MAX(create_time) FROM
 		 	oss_box_log_bak where oss_key like '%$mac%'),'1970-01-01
 		 	00:00:00')),
 		 	( SELECT IFNULL((SELECT MAX(create_time) FROM
 		 	oss_box_log_death where oss_key like '%$mac%'),'1970-01-01
 		 	00:00:00'))
		) lastma";*/

		$sql = " SELECT GREATEST(
 			( SELECT IFNULL((SELECT MAX(create_time) FROM
 		 	oss_box_log where LOCATE('$mac', `oss_key`)>0),'1970-01-01
 		 	00:00:00')),
 		 	( SELECT IFNULL((SELECT MAX(create_time) FROM
 		 	oss_box_log_bak where LOCATE('$mac', `oss_key`)>0),'1970-01-01
 		 	00:00:00')),
 		 	( SELECT IFNULL((SELECT MAX(create_time) FROM
 		 	oss_box_log_death where LOCATE('$mac', `oss_key`)>0),'1970-01-01
 		 	00:00:00'))
		) lastma";
		$result = $this->query($sql);
		return $result;
	}


}//End Class