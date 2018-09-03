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
	
}