<?php
namespace Common\Model\Crm;
use Common\Model\BaseModel;

class UserModel extends BaseModel{
	protected $tableName='crm_user';

	public function getUserList($fields,$where,$orderby,$limit=''){
        $data = $this->alias('a')
            ->field($fields)
            ->join('savor_hotel hotel on a.hotel_id=hotel.id','left')
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->select();
        return $data;
    }
}