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
}