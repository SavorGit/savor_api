<?php
namespace Common\Model\Integral;
use Common\Model\BaseModel;
class MerchantModel extends BaseModel{
    protected $tableName = 'integral_merchant';

    public function getMerchantList($fields,$where,$orderby,$start=0,$size=0){
        if($start >= 0 && $size){
            $list = $this->alias('m')
                ->field($fields)
                ->join('savor_hotel hotel on m.hotel_id=hotel.id','left')
                ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
                ->join('savor_area_info area on area.id=hotel.area_id','left')
                ->join('savor_hotel_food_style food on ext.food_style_id=food.id','left')
                ->where($where)
                ->order($orderby)
                ->limit($start,$size)
                ->select();
            $count = $this->alias('m')
                ->field($fields)
                ->join('savor_hotel hotel on m.hotel_id=hotel.id','left')
                ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
                ->join('savor_area_info area on area.id=hotel.area_id','left')
                ->join('savor_hotel_food_style food on ext.food_style_id=food.id','left')
                ->where($where)
                ->count();
            $data = array('list'=>$list,'total'=>$count);
        }else{
            $data = $this->field($fields)->where($where)->order($orderby)->select();
        }
        return $data;
    }

    public function getMerchantInfo($fields,$where){
        $data = $this->alias('m')
            ->field($fields)
            ->join('savor_hotel hotel on m.hotel_id=hotel.id','left')
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->join('savor_area_info area on area.id=hotel.area_id','left')
            ->where($where)
            ->select();
        return $data;
    }


}