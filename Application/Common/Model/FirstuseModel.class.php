<?php
namespace Common\Model;
use Think\Model;

class FirstuseModel extends Model{
	protected $tableName = 'mb_first';
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
	public function getOne($map = array()){
	    if(!empty($map)){
	        $result = $this->where($map)->find();
	        return $result;
	    }
	}
}