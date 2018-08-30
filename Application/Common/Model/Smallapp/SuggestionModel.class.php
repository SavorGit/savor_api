<?php
/**
 * @desc 小程序投屏日志记录
 */
namespace Common\Model\Smallapp;
use Think\Model;

class SuggestionModel extends Model
{
	protected $tableName='smallapp_suggestion';
	
	public function addInfo($data,$type=1){
	    if($type==1){
	        $ret = $this->add($data);
	        
	    }else {
	        $ret = $this->addAll($data);
	    }
	    return $ret;
	}
}