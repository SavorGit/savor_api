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
}