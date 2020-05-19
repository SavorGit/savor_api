<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class OrdergiftModel extends BaseModel{
	protected $tableName='smallapp_ordergift';

    public function getOrderGiftgoodsList($fields,$where,$orderby,$start=0,$size=0){
        $data = $this->alias('og')
            ->join('savor_smallapp_dishgoods goods on og.gift_goods_id=goods.id','left')
            ->field($fields)
            ->where($where)
            ->order($orderby)
            ->limit($start,$size)
            ->select();
        return $data;
    }
}