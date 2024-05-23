<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class AwardHoteldataModel extends BaseModel{
	protected $tableName='finance_award_hoteldata';

    public function getData($fileds,$where,$groupby=''){
        $res_data = $this->alias('a')
            ->field($fileds)
            ->join('savor_hotel_ext ext on a.hotel_id=ext.hotel_id','left')
            ->where($where)
            ->group($groupby)
            ->select();
        return $res_data;
    }

    public function getAwardList($fileds,$where,$orderby,$limit=''){
        $res_data = $this->alias('a')
            ->field($fileds)
            ->join('savor_hotel hotel on a.hotel_id=hotel.id','left')
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->join('savor_smallapp_user user on a.award_openid=user.openid','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->select();
        return $res_data;
    }
}