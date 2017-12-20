<?php
/**
 *@author zhang.yingtao
 *@desc 餐厅端客户
 *
 */
namespace Common\Model;
use Think\Model;

class DinnerCustomerModel extends Model
{
	protected $tableName='dinner_customer';
	public function addList($dataList){
	    $ret = $this->addAll($dataList);
	    return $ret;
	}
	public function countNums($where){
	    $nums = $this->where($where)->count();
	    return $nums;
	}

	public function addData($data){
		$ret = $this->add($data);
		return $this->getLastInsID();
	}
	public function getData($field, $where, $order, $limit) {
		$res = $this->field($field)
					->where($where)
					->order($order)
					->limit($limit)
					->select();
		return $res;
	}

	public function saveData($data, $where) {
		$res = $this->where($where)
			        ->save($data);
		return $res;
	}
	public function getOneRow($field, $id) {
		$res = $this->field($field)
					->find($id);
		return $res;
	}


	public function getList($fields,$where,$order,$limit){
	    $data = $this->field($fields)->where($where)->order($order)->limit($limit)->select();
	    return $data;
	}
	public function getOne($fields,$where){
	    $data = $this->field($fields)->where($where)->find();
	    return $data;
	}
}
