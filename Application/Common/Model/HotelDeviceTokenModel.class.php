<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class HotelDeviceTokenModel extends Model
{
    protected $tableName='hotel_device_token';



    public function getOnerow($where){
        $list = $this->where($where)->find();
        return $list;
    }


    public function saveData($save, $where) {
        $data = $this->where($where)->save($save);
        return $data;
    }


    public function addData($data) {
        $data = $this->add($data);
        return $data;
    }

}