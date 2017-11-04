<?php
/**
 *@author hongwei
 *
 *
 */
namespace Common\Model;

use Think\Model;

class OptiontaskModel extends Model
{
	protected $tableName='option_task';

	public function getTaskInfoByUserid($fields, $where){
		$data = $this->field($fields)->where($where)->find();
		return $data;
	}

	public function saveData($dat, $where) {
		$ret = $this->where($where)->save($dat);
		return $ret;
	}


	public function addData($data, $type) {
		if($type == 1) {
			$result = $this->add($data);
			return $this->getLastInsID();
		} else {
			$result = $this->addAll($data);
		    return $result;
		}
		
	}
	public function getList($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	                 ->join(' savor_area_info area on a.task_area = area.id','left')
	                 ->join('savor_hotel hotel on a.hotel_id = hotel.id','left')
	                 ->join(' savor_sysuser user on a.publish_user_id=user.id','left')
	                 ->join('savor_sysuser appuser on a.appoint_user_id = appuser.id','left')
	                 ->join('savor_sysuser exeuser on a.exe_user_id = exeuser.id','left')  
	                 ->field($fields)
	                 ->where($where)
	                 ->order($order)
	                 ->limit($limit)
	                 ->select();
	    return $data;
	}
	public function getInfo($fields,$where){
	    $data = $this->alias('a')
	                 ->join('savor_hotel hotel on a.hotel_id = hotel.id','left')
	                 ->join('savor_area_info area on a.task_area=area.id','left')
	                 ->field($fields)->where($where)->find();
	    return $data;
	}
	public function updateInfo($where,$data){
	    $ret = $this->where($where)->save($data);
	    return $ret;
	}
	public function getdatas($fields,$where){
	    $data = $this->alias('a')
	    ->join('savor_hotel hotel on a.hotel_id = hotel.id','left')
	    ->field($fields)->where($where)->select();
	    return $data;
	}
}