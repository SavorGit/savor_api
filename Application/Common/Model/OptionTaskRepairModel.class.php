<?php
/**
 *@author hongwei
 *
 *
 */
namespace Common\Model;

use Think\Model;

class OptionTaskRepairModel extends Model
{
	protected $tableName='option_task_repair';
	public function addData($data, $type) {
		$ret = $this->add($data);
	    return $ret;
	}


	public function getOneRecord($fields, $where){
		$data = $this->field($fields)->where($where)->find();
		return $data;
	}

	public function saveData($dat, $where) {
		$ret = $this->where($where)->save($dat);
		return $ret;
	}
	public function getRepairBoxInfo($field, $where){
		$data = $this->field($field)
					 ->where($where)
			         ->select();
		return $data;

	}
	public function getList($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	                 ->join('savor_box as box on box.id=a.box_id','left')
	                 ->field($fields)
	                 ->where($where)
	                 ->order($order)
	                 ->limit($limit)
	                 ->select();
	    return $data;
	}

	public function getTaskState($fields,$where) {
		$data = $this->alias('srepair')
			->join('savor_option_task stask  on stask.id= srepair.id')
			->field($fields)
			->where($where)
			->select();
		return $data;
	}
}