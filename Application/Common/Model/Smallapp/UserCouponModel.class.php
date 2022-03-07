<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class UserCouponModel extends BaseModel{
	protected $tableName='smallapp_usercoupon';

    public function getUsercouponDatas($fields,$where,$order,$limit=''){
        $data = $this->alias('a')
            ->join('savor_smallapp_coupon coupon on a.coupon_id=coupon.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;
    }
}