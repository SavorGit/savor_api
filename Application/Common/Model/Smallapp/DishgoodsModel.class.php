<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class DishgoodsModel extends BaseModel{
	protected $tableName='smallapp_dishgoods';

    public function getGoods($fileds,$where){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_integral_merchant merchant on a.merchant_id=merchant.id','left')
            ->where($where)
            ->select();
        return $res;
    }

    public function getGoodsInfo($fileds,$where){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_integral_merchant merchant on a.merchant_id=merchant.id','left')
            ->join('savor_hotel hotel on hotel.id=merchant.hotel_id','left')
            ->join('savor_area_info area on area.id=hotel.area_id','left')
            ->where($where)
            ->select();
        return $res;
    }
}