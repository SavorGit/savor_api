<?php
/**
 *@author zhang.yingtao
 *@desc 餐厅端客户
 *
 */
namespace Common\Model;
use Think\Model;

class DinnerCustomerLabelModel extends Model
{
	protected $tableName='dinner_label';

	public function getLabelNameByCid($field, $where){
		$res = $this->alias('scl')
					->field($field)
			        ->join('left JOIN savor_dinner_label sdl ON scl.label_id = sdl.id')
					->where($where)
					->select();
		return $res;
	}

	public function addData($data){
		$ret = $this->add($data);
		return $ret;
	}
	public function saveData($data, $where){
		$ret = $this->where($where)->save($data);
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
