<?php
namespace Common\Model;
use Think\Model;

class DailyUserModel extends Model{
	protected $tableName = 'daily_user';



	public function saveData($data, $where) {
		$bool = $this->where($where)->save($data);
		return $bool;
	}


	/**
	 * @desc 添加数据
	 */
	public function addData($data  = array()){
		if(!empty($data) && is_array($data)){
		    $this->add($data);
		    $id = $this->getLastInsID();
		    return $id;
		}else {
		    return false;
		}
	}
	public function getOne($map = array(),$order=''){
	    if(!empty($map)){
	        $result = $this->where($map)->order($order)->find();
	        return $result;
	    }
	}

	public function getWhere($where, $field){
		$list = $this->where($where)->field($field)->select();
		return $list;
	}
}