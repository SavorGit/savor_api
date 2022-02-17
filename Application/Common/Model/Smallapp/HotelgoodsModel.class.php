<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class HotelgoodsModel extends BaseModel{
	protected $tableName='smallapp_hotelgoods';

    public function getList($fields,$where,$order,$limit,$group=''){
        $data = $this->alias('h')
            ->join('savor_smallapp_goods g on h.goods_id=g.id','left')
            ->field($fields)
            ->where($where)
            ->group($group)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;
    }

    public function getGoodsList($fields,$where,$order,$limit,$group=''){
        $data = $this->alias('h')
            ->join('savor_hotel hotel on h.hotel_id=hotel.id','left')
            ->join('savor_smallapp_dishgoods g on h.goods_id=g.id','left')
            ->field($fields)
            ->where($where)
            ->group($group)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;
    }
}