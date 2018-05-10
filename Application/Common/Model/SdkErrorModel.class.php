<?php
/**
 * @desc SDK报损、已满统计
 */
namespace Common\Model;
use Think\Model;

class SdkErrorModel extends Model
{
	protected $tableName='sdk_error';
    public function getInfo($fields,$where){
        $info = $this->field($fields)->where($where)->find();
        return $info;
    }
    public function countNums($where){
        $nums =  $this->where($where)->count();
        return $nums;
    }
    public function addInfo($data){
        $ret = $this->add($data);
        return $ret;
    }
}