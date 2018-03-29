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

	public function getOneMemInfo($fields, $where){
		$data = $this->alias('srp')
					 ->join('savor_box as a on a.id=srp.box_id','left')
					 ->join('savor_option_task as stas on stas.id=srp.task_id','left')
					 ->field($fields)
					 ->where($where)
					 ->find();
		return $data;
	}


	public function getOneRecord($fields, $where){
		$data = $this->field($fields)->where($where)->find();
		return $data;
	}

	public function saveData($dat, $where) {
		$ret = $this->where($where)->save($dat);
		return $ret;
	}
	public function getRepairBoxInfo($field, $where, $order){
		$data = $this->field($field)
					 ->where($where)
					 ->order($order)
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

	public function getMissionRepairInfo($fields, $where, $type=2) {
		$joinb = '';
		if($type == 1) {
			$joinb = 'left join savor_box sbox ON srepair.box_id = sbox.id';
		}
		$data = $this->alias('srepair')
			->join('left join savor_option_task stask  on stask.id= srepair.task_id')
			->join($joinb)
			->join('left join savor_sysuser suser ON stask.exe_user_id = suser.id')
			->field($fields)
			->where($where)
			->select();

		return $data;
	}
	public function getMultMissionRepairInfo($fields, $where, $type=2) {
		$joinb = '';
		if($type == 1) {
			$joinb = 'left join savor_box sbox ON srepair.box_id = sbox.id';
		}
		$data = $this->alias('srepair')
			->join('left join savor_option_task stask  on stask.id= srepair.task_id')
			->join($joinb)
			->join('left join savor_sysuser suser ON srepair.user_id = suser.id')
			->field($fields)
			->where($where)
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