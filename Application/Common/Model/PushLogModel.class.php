<?php
/**
 * @desc   推送日志
 * @author zhang.yingtao 
 * @since  2018-02-01
 */
namespace Common\Model;
use Think\Model;

class PushLogModel extends Model{
	protected $tableName='push_log';
	
	public function addInfo($data,$type=1){
	    if($type==1){
	        return $this->add($data);
	    }else {
	        return $this->addAll($data);
	    }
	}
}