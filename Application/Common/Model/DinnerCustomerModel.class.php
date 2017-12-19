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
}
