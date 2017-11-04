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
	public function getList($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	                 ->join('savor_sysuser as user on user.id=a.user_id','left')
	                 ->field($fields)
	                 ->where($where)
	                 ->order($order)
	                 ->limit()
	                 ->select();
	    return $data;
	}
}