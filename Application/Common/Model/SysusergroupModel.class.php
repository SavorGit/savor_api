<?php
namespace Common\Model;
use Think\Model;
class SysusergroupModel extends Model{
    protected $tableName='sysusergroup';

    public function getOpeprv($where,$field){


        $list = $this->alias('sgr')
                     ->where($where)
                     ->field($field)
                     ->join('savor_sysuser su on su.groupId=sgr.id')
                     ->select();
        return $list;
    }

    public function fetchDataWhere($where, $order, $field, $type=1){
        if( $type == 1) {
            $list = $this->where($where)->order($order)->field($field)->find();
        } else {
            $list = $this->where($where)->order($order)->field($field)->select();
        }
        return $list;
    }
}