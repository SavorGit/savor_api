<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class GoodsPolicyHotelModel extends BaseModel{
	protected $tableName='finance_goods_policy_hotel';

    public function getGoodsPolicy($goods_id,$area_id,$hotel_id){
        $fileds = 'p.id as policy_id,p.integral,p.open_integral,p.media_id as open_media_id';
        $where = array('p.goods_id'=>$goods_id,'p.status'=>1,'a.area_id'=>$area_id,'a.hotel_id'=>array('in',array($hotel_id,0)));
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_finance_goods_policy p on a.policy_id=p.id','left')
            ->where($where)
            ->order('p.id desc')
            ->limit(0,1)
            ->select();
        $policy_data = array();
        if(!empty($res[0]['policy_id'])){
            $policy_data = $res[0];
        }
        return $policy_data;
    }
}