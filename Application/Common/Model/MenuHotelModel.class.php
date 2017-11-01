<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model;
use Think\Model;

class MenuHotelModel extends Model
{
	protected $tableName='menu_hotel';
	public function getWhere($where, $order, $field){

		$list = $this->where($where)->order($order)->field($field)->select();

		return $list;
	}


	/**
	 * getadsPeriod 获取酒楼广告期号拿最新的一条
	 * @access public
	 * @param $hotelid
	 * @return array
	 */
	public function getadsPeriod($hotelid){
		$sql = "select
        menu_hotel.id AS menuHotelId,
        menu_hotel.menu_id AS menuId,
        CONCAT(DATE_FORMAT(menu_hotel.update_time,'%m%d%H%i'),
		  DATE_FORMAT(list.update_time,'%m%d%H%i')) AS period,
        menu_hotel.pub_time AS pubTime,list.menu_name
        FROM savor_menu_hotel menu_hotel
        LEFT JOIN savor_menu_list list on menu_hotel.menu_id=list.id
        where menu_hotel.hotel_id = $hotelid
        ORDER BY menu_hotel.update_time desc,menu_hotel.id desc limit 1";
		$result = $this->query($sql);
		return $result;
	}

	public function fetchDataWhere($where, $order, $field, $type=1){
		if( $type == 1) {
			$list = $this->where($where)->order($order)->field($field)->find();
		} else {
			$list = $this->where($where)->order($order)->field($field)->select();
		}
		return $list;
	}


	




}//End Class
