<?php
/**
 *@author zhang.yingtao
 *@desc   餐厅端手动添加包间
 *@since  20171220
 */
namespace Common\Model;
use Think\Model;

class DinnerRoomModel extends Model
{
	protected $tableName='dinner_room';
	/**
	 * @desc 添加包间
	 */
	public function addInfo($data){
	    $ret = $this->add($data);
	    return $ret;
	}
	public function countNums($where){
	    $nums = $this->where($where)->count();
	    return $nums;
	}
}
