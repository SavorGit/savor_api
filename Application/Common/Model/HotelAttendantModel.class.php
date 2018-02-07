<?php
/**
 * @desc wifi探针手机酒楼工作人员mac
 */
namespace Common\Model;
use Think\Model;

class HotelAttendantModel extends Model
{
	protected $tableName='hotel_attendant';
	public function getWhere($fields,$where,$order,$limit){
	    $data = $this->field($fields)->where($where)->order($order)->limit($limit)->select();
	    return $data;
	}
}