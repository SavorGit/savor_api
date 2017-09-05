<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model;
use Think\Model;

class RepairBoxUserModel extends Model
{
	protected $tableName='repair_box_user';


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


}//End Class