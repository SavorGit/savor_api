<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class HotelStockModel extends BaseModel{
	protected $tableName='finance_hotel_stock';

    public function getHotelDatas($fileds,$where,$group='',$limit='',$orderby=''){
        $res_data = $this->alias('a')
            ->field($fileds)
            ->join('savor_hotel hotel on a.hotel_id=hotel.id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->group($group)
            ->select();
        return $res_data;
    }
}