<?php
/**
 *@author hongwei
 *
 *
 */
namespace Common\Model;
use Think\Model;

class MenuListModel extends Model
{
	protected $tableName='menu_list';

	/*public function getOneById($field,$map, $order){
		return $this->field($field)->where($map)->order($order)->find();
	}*/

	public function fetchDataWhere($where, $order, $field, $type=1){
		if( $type == 1) {
			$list = $this->where($where)->order($order)->field($field)->select();
		} else {
			$list = $this->where($where)->order($order)->field($field)->find();
		}
		return $list;
	}

}