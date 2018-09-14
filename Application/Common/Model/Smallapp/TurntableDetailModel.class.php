<?php
/**
 * @desc 小程序投屏日志记录
 */
namespace Common\Model\Smallapp;
use Think\Model;

class TurntableDetailModel extends Model
{
	protected $tableName='smallapp_turntable_detail';
	
	public function addInfo($data,$type=1){
	    if($type==1){
	        $ret = $this->add($data);
	        
	    }else {
	        $ret = $this->addAll($data);
	    }
	    return $ret;
	}
	public function updateInfo($where,$data){
	    $ret = $this->where($where)->save($data);
	    return $ret;
	}
	public function countWhere($where){
	    $nums = $this->where($where)->count();
	    return $nums;
	}
	public function getOne($fields,$where,$order){
	    $data = $this->field($fields)->where($where)->order($order)->find();
	    return $data;
	}
}