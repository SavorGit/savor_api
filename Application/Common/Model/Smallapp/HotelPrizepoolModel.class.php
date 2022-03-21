<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class HotelPrizepoolModel extends BaseModel{
	protected $tableName='smallapp_hotel_prizepool';

    public function getHotelprizeList($fields,$where,$order){
        $data = $this->alias('a')
            ->join('savor_hotel h on a.hotel_id=h.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->select();
        return $data;
    }
}