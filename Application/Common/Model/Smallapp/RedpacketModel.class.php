<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class RedpacketModel extends BaseModel{
	protected $tableName='smallapp_redpacket';
	public function getList($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	         ->join('savor_smallapp_user user on a.user_id=user.id','left')
	         ->field($fields)
	         ->where($where)
	         ->order($order)
	         ->limit($limit)
	         ->select();
	    return $data;
	}
	public function getOrderAndUserInfo($fields,$where){
	    $data = $this->alias('a')
            	     ->join('savor_smallapp_user user on a.user_id=user.id','left')
            	     ->field($fields)
            	     ->where($where)
            	     ->find();
	    return $data;
	}
}