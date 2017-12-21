<?php
/**
 *@author zhang.yingtao
 *@desc   餐厅端预订包间
 *@since  20171220
 */
namespace Common\Model;
use Think\Model;

class DinnerOrderModel extends Model
{
	protected $tableName='dinner_order';
	/**
	 * @desc 添加订单
	 */
	public function addInfo($data){
	    $ret = $this->add($data);
	    return $ret;
	}
	public function countNums($where){
	    $nums = $this->where($where)->count();
	    return $nums;
	}
	public function getList($fields,$where,$order,$limit){
	    $data = $this->field($fields)->where($where)->order($order)->limit($limit)->select();
	    return $data;
	}
	public function updateInfo($where,$data){
	    $ret =$this->where($where)->save($data);
	    return $ret;
	}
	public function getOne($fields,$where){
	    $data = $this->field($fields)->where($where)->find();
	    return $data;
	}
}
