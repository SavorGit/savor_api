<?php
/**
 *@author zhang.yingtao
 *@desc 餐厅端客户
 *
 */
namespace Common\Model;
use Think\Model;

class DinnerActionLogModel extends Model
{
	protected $tableName='dinner_action_log';
	public function addData($data){
		$ret = $this->add($data);
		return $ret;
	}

	public function getLatestCusInfo($field, $where, $order, $limit) {
		$res = $this->alias('sa')
			        ->join('left join `savor_dinner_customer` sct on sa.action_id = sct.id ')
					->field($field)
					->where($where)
					->order($order)
					->limit($limit)
					->select();
		return $res;
	}





	public function addList($dataList){
	    $ret = $this->addAll($dataList);
	    return $ret;
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

}
