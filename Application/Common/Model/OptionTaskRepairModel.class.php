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
}