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
}