<?php
namespace Common\Model\Integral;
use Common\Model\BaseModel;
class StaffModel extends BaseModel{
    protected $tableName = 'integral_merchant_staff';

    public function getMerchantStaff($fileds,$where,$orderby=''){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_integral_merchant merchant on a.merchant_id=merchant.id','left')
            ->join('savor_smallapp_user user on user.openid=a.openid','left')
            ->where($where)
            ->order($orderby)
            ->select();
        return $res;
    }

    public function getStaffsByOpenid($openid,$start,$size){
        $fields = 'staff_lev1.id,staff_lev1.openid,staff_lev1.parent_id,staff_lev1.level';
        $res = $this->alias('a')
                    ->join('savor_integral_merchant_staff staff_lev1 on a.id= staff_lev1.parent_id')
                    ->field($fields)
                    ->where(array('a.openid'=>$openid,'a.status'=>1,'staff_lev1.status'=>1))
                    ->limit($start,$size)
                    ->select();
        return $res;
    }

    public function getMerchantStaffInfo($fileds,$where){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_integral_merchant merchant on a.merchant_id=merchant.id','left')
            ->join('savor_hotel hotel on merchant.hotel_id=hotel.id','left')
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->join('savor_area_info area on area.id=hotel.area_id','left')
            ->where($where)
            ->select();
        return $res;
    }
}