<?php
namespace Common\Model\Crm;
use Common\Model\BaseModel;

class SignhotelModel extends BaseModel{
	protected $tableName='crm_signhotel';

    public function getSignData($fields,$where,$orderby,$limit='',$group=''){
        $data = $this->alias('a')
            ->field($fields)
            ->join('savor_hotel hotel on a.hotel_id=hotel.id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }
}