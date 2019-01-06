<?php
/**
 * model基类
 * 
 */
namespace Common\Model;
use Think\Model;

class BaseModel extends Model{

    public function getDataList($fields,$where,$orderby,$start=0,$size=0){
        if($start >= 0 && $size){
            $list = $this->field($fields)->where($where)->order($orderby)->limit($start,$size)->select();
            $count = $this->countNum($where);
            $data = array('list'=>$list,'total'=>$count);
        }else{
            $data = $this->field($fields)->where($where)->order($orderby)->select();
        }
        return $data;
    }

    public function countNum($where){
        $nums = $this->where($where)->count();
        return $nums;
    }

    public function getInfo($condition){
        $result = $this->where($condition)->find();
        return $result;
    }

    public function addData($data){
        $result = $this->add($data);
        return $result;
    }

    public function updateData($condition,$data){
        $result = $this->where($condition)->save($data);
        return $result;
    }

    public function delData($condition){
        $result = $this->where($condition)->delete();
        return  $result;
    }
}

