<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class ActivityModel extends BaseModel{
	protected $tableName='smallapp_activity';

    public function getActivity($fields,$where,$orderby,$start=0,$size=0){
        if($start >= 0 && $size){
            $data = $this->field($fields)->where($where)->order($orderby)->limit($start,$size)->select();
        }else{
            $data = $this->field($fields)->where($where)->order($orderby)->select();
        }
        return $data;
    }
}