<?php
/**
 * @desc 小程序极简版wifi链接错误
 */
namespace Common\Model\Smallapp;
use Think\Model;

class WifiErrModel extends Model
{
	protected $tableName='smallapp_wifi_err';
	public function addInfo($data,$type=1){
	    if($type==1){
	        $ret = $this->add($data);
	         
	    }else {
	        $ret = $this->addAll($data);
	    }
	    return $ret;
	}
}