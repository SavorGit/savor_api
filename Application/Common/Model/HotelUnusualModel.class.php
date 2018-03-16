<?php
/**
 *@author hongwei
 * 
 * 
 */
namespace Common\Model;

use Think\Model;

class HotelUnusualModel extends Model
{
	protected $tableName='hotel_unusual_report';

	public function getList($fileds,$where,$order,$start, $size){
		$data = $this->field($fileds)->where($where)->order($order)->limit($start,$size)->select();
		return $data;
	}
	public function getErrHotelList($fileds,$where,$order,$start, $size){
	    $data = $this->alias('a')
	                 ->join('savor_hotel hotel on a.hotel_id=hotel.id','left')
	                 ->field($fileds)
	                 ->where($where)
	                 ->order($order)
	                 ->limit($start,$size)
	                 ->select();
	    return $data;
	}

	public function getOneInfo($fileds,$where,$order){
		$data = $this->field($fileds)->where($where)->order($order)->find();
		return $data;
	}

	public function saveData($data, $where) {
		$ret = $this->where($where)->save($data);
		return $ret;
	}

	public function addData($data) {
		$ret = $this->add($data);
		return $ret;
	}
}//End Class