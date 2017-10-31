<?php
/**
 *@author hongwei
 *
 *
 */
namespace Common\Model;

use Think\Model;

class OptiontaskModel extends Model
{
	protected $tableName='option_task';
	public function addData($data, $type) {
		if($type == 1) {
			$result = $this->add($data);
		} else {
			$result = $this->addAll($data);
		}
		return $result;
	}
}