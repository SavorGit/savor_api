<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class UserTaskRecordModel extends BaseModel{
	protected $tableName='smallapp_usertask_record';

    public function getRecordListdate($fields,$where,$orderby,$count_fields='',$group='',$start=0,$size=0){
        if($start >= 0 && $size){
            $list = $this->field($fields)->where($where)->order($orderby)->group($group)->limit($start,$size)->select();
            $res_count = $this->field($count_fields)->where($where)->select();
            $count = intval($res_count[0]['tp_count']);
            $data = array('list'=>$list,'total'=>$count);
        }else{
            $data = $this->field($fields)->where($where)->order($orderby)->group($group)->select();
        }
        return $data;
    }
}