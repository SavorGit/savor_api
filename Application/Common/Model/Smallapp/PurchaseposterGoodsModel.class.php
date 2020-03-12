<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class PurchaseposterGoodsModel extends BaseModel{
	protected $tableName='smallapp_purchaseposter_goods';

	public function getPosterGoods($fileds,$where){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_smallapp_dishgoods dg on a.goods_id=dg.id','left')
            ->where($where)
            ->select();
        return $res;
    }
}