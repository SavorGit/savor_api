<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class OrdersettlementModel extends BaseModel{
	protected $tableName='smallapp_ordersettlement';

    public function getOrdersettlement($fields,$where){
        $data = $this->alias('a')
            ->field($fields)
            ->join('savor_smallapp_distribution_user as duser on a.distribution_user_id=duser.id','left')
            ->join('savor_smallapp_user as user on a.openid=user.openid','left')
            ->where($where)
            ->select();
        return $data;
    }
}