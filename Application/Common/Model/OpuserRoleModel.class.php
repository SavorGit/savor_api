<?php
namespace Common\Model;
use Think\Model;

class OpuserRoleModel extends Model{
	protected $tableName = 'opuser_role';
	public function getInfoByUserid($fields,$userid){
	    $where['user_id'] = $userid;
	    $where['state']   = 1;
 	    $data = $this->field($fields)->where($where)->find();
	    return $data;
	}
}