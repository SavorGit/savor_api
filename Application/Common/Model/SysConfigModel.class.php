<?php
namespace Common\Model;
use Think\Model;

class SysConfigModel extends Model{
	protected $tableName = 'sys_config';
	public function getInfo($where){
	    if($where){
	        $where =" config_key in(".$where.")";
	    }
	    $result = $this->where($where)->select();
	    return $result;
	}
	
}