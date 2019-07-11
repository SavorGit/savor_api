<?php
/**
 * @desc 小程序投屏日志记录
 */
namespace Common\Model\Smallapp;
use Think\Model;

class ForscreenRecordModel extends Model
{
	protected $tableName='smallapp_forscreen_record';

    public function getWhere($fields,$where,$order,$limit,$group){
        $data = $this->field($fields)->where($where)->order($order)->group($group)->limit($limit)->select();
        return $data;
    }

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

	public function getInfo($where){
        $res = $this->where($where)->find();
        return $res;
    }

}