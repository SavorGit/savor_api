<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class PurchaseposterModel extends BaseModel{
	protected $tableName='smallapp_purchaseposter';

    public function getPosterList($fileds,$where,$orderby,$start,$size){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_integral_merchant m on a.merchant_id=m.id','left')
            ->join('savor_hotel hotel on m.hotel_id=hotel.id','left')
            ->where($where)
            ->order($orderby)
            ->limit($start,$size)
            ->select();
        return $res;
    }

}