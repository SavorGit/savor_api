<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class HotelErrorReportModel extends Model
{
    protected $tableName='hotel_error_report';
    
    public function addInfo($data){
        $ret = $this->add($data);
        return $ret;
    }
    public function getInfo($fields,$where){
        $data = $this->field($fields)->where($where)->find();
        return $data;
    }
    public function getList($fields,$where,$order,$limit){
        $data = $this->field($fields)->where($where)->order($order)->limit($limit)->select();
        return $data;
    }
}