<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */

namespace Common\Model;
use Think\Model;

class MenuItemModel extends Model
{

	public function getWhere($where, $order, $field){

		$list = $this->where($where)->order($order)->field($field)->select();
		return $list;
	}





}//End Class
