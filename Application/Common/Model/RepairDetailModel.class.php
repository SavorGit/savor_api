<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model;
use Think\Model;

class RepairDetailModel extends Model
{
	protected $tableName='repair_detail';


	public function saveData($data, $where) {
		$bool = $this->where($where)->save($data);
		return $bool;
	}

	public function addData($data, $type) {
		if($type == 1) {
			$result = $this->add($data);
		} else {
			$result = $this->addAll($data);
		}
		return $result;
	}


}//End Class