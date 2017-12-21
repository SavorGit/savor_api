<?php
/**
 *@author zhang.yingtao
 *@desc 餐厅端客户
 *
 */
namespace Common\Model;
use Think\Model;

class DinnerLabelModel extends Model
{
	protected $tableName='dinner_label';
	public function addData($data){
		$ret = $this->add($data);
		return $ret;
	}
	public function getOne($fields,$where){
		$data = $this->field($fields)->where($where)->find();
		return $data;
	}
	public function countNums($where){
		$nums = $this->where($where)->count();
		return $nums;
	}
	public function getData($field, $where, $order, $limit) {
		$res = $this->field($field)
			->where($where)
			->order($order)
			->limit($limit)
			->select();
		return $res;
	}

}
