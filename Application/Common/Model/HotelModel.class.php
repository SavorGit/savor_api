<?php
/**
 *@author hongwei
 *
 *
 */
namespace Common\Model;
use Think\Model;

class HotelModel extends Model
{
    protected $tableName='hotel';
    public function getOneById($field,$id){
        return $this->field($field)->where("id='".$id."'")->find();
    }
}