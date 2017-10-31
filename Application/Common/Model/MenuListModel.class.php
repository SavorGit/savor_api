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
	//获取酒楼最新的一期节目单信息
	public function getMenuInfoByHotelid($hotel_id){
	    $now_date = date('Y-m-d H:i:s');
	    $sql =" select b.menu_name from savor_menu_hotel as a
	            left join savor_menu_list as b  on a.menu_id=b.id
	            where a.hotel_id=$hotel_id and a.pub_time <='".$now_date."' order by b.id desc";
	    $data = $this->query($sql);
	    return $data[0];
	}

}