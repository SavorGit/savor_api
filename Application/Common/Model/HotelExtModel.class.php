<?php
/**
 *酒店model
 *@author  hongwei <[<email address>]>
 *
 */
namespace Common\Model;
use Think\Model;

class HotelExtModel extends Model{
	protected $tableName='hotel_ext';


	public function saveData($data, $where) {
		$bool = $this->where($where)->save($data);
		return $bool;
	}

	public function addData($data) {
		$result = $this->add($data);
		return $result;
	}

	public function saveStRedis($data, $id){
		$redis  =  \Common\Lib\SavorRedis::getInstance();
		$redis->select(15);
		$cache_key = C('DB_PREFIX').$this->tableName.'_'.$id;
		$redis->set($cache_key, json_encode($data));
	}


	public function getData($field, $where){
		$list = $this->field($field)->where($where)->select();
		return $list;
	}

	public function getOnerow($where){
		$list = $this->where($where)->find();
		return $list;
	}

	public function isHaveMac($field,$where){
	    $sql ="select $field from savor_hotel_ext as he 
	           left join savor_hotel as h on he.hotel_id = h.id where ".$where;
	    $result = $this->query($sql);
	    return $result;
	}
}
