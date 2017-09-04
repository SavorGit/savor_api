<?php
/**
 * @desc 系统用户
 */
namespace Common\Model;
use Think\Model;

class SysUserModel extends Model
{
	protected $tableName='sysuser';
	
	public function getUserInfo($where ,$fields = "*",$type=1){
	    if($type==1){
	        $result = $this->field($fields)->where($where)->find();
	    }else {
	        $result = $this->field($fields)->where($where)->select();
	    }
	    return $result;
	}
}