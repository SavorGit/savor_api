<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class HotelgoodsModel extends BaseModel{
	protected $tableName='smallapp_hotelgoods';

    public function getList($fields,$where,$order,$limit){
        $data = $this->alias('h')
            ->join('savor_smallapp_goods g on h.goods_id=g.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;
    }
}