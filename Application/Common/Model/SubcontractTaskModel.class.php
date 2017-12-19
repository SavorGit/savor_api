<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model;
use Think\Model;

class SubcontractTaskModel extends Model
{
	protected $tableName='subcontract';

	public function getList($field, $where, $order){
		$data = $this->field($field)
			->where($where)
			->order($order)
			->select();
		return $data;

	}

	public function saveData($data, $where) {
		$bool = $this->where($where)->save($data);
		return $bool;
	}

	public function addData($data) {
		$result = $this->add($data);
		return $result;
	}

	
}//End Class