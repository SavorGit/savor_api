<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class StoresaleSortHotelModel extends BaseModel {
	protected $tableName='storesale_sort_hotel';

    public function getSortDatas($fields,$where,$order,$limit,$group){
        $data = $this->alias('a')
            ->join('savor_storesale_sort salesort on a.storesale_sort_id=salesort.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }

}