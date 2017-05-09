<?php

namespace Common\Model;
use Think\Model;

class BoxAwardModel extends Model
{
	protected $tableName='box_award';
	public function getAwardInfoByBoxid($boxid,$date){
	    return $this->field('box_id,room_id,hotel_id,prize,date_time')->where(array('box_id'=>$boxid,'date_time'=>$date,'flag'=>1))->find();
	}
	public function getAwardInfoByHotelid($hotelid,$date){
	    return $this->field('id')->where(array('hotel_id'=>$hotelid,'date_time'=>$date,'flag'=>1))->select();
	}
}
