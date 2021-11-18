<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class RedpacketReceiveModel extends BaseModel{
	protected $tableName='smallapp_redpacket_receive';
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
	public function getInfo($fields,$where){
	    $data = $this->alias('a')
            	     ->join('savor_smallapp_user user on a.user_id=user.id','left')
            	     ->field($fields)
            	     ->where($where)
            	     ->find();
	    return $data;
	}
    public function getRedpacketInfo($fields,$where){
        $data = $this->alias('a')
            ->join('savor_smallapp_redpacket r on a.redpacket_id=r.id','left')
            ->join('savor_smallapp_user u on r.user_id=u.id','left')
            ->field($fields)
            ->where($where)
            ->find();
        return $data;
    }
}