<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class PriceTemplateHotelModel extends BaseModel{
	protected $tableName='finance_price_template_hotel';

    public function getHotelGoodsPrice($hotel_id,$goods_id,$is_cache=0){
        $settlement_price = 0;
        if($is_cache==1){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(9);
            $cache_key = C('FINANCE_HOTELGOODS_PRICE');
            $res_cache = $redis->get($cache_key.":$hotel_id");
            if(!empty($res_cache)){
                $hotel_price = json_decode($res_cache,true);
                if(isset($hotel_price[$goods_id])){
                    $settlement_price = $hotel_price[$goods_id];
                }
            }
        }else{
            if($hotel_id==7){
                $m_hotel = new \Common\Model\HotelModel();
                $res_hotel = $m_hotel->getOneById('area_id',$hotel_id);
                $where = array('a.hotel_id'=>array('in',"$hotel_id,0"),'a.goods_id'=>$goods_id,'t.status'=>1,'a.area_id'=>$res_hotel['area_id']);
            }else{
                $where = array('a.hotel_id'=>array('in',"$hotel_id,0"),'a.goods_id'=>$goods_id,'t.status'=>1);
            }
            $result = $this->alias('a')
                ->join('savor_finance_price_template t on a.template_id=t.id','left')
                ->field('a.template_id')
                ->where($where)
                ->order('t.id desc')
                ->limit(0,1)
                ->find();
            if(!empty($result)){
                $template_id = $result['template_id'];
                $m_pricegoods = new \Common\Model\Finance\PriceTemplateGoodsModel();
                $field = 'settlement_price';
                $res_pgoods = $m_pricegoods->getALLDataList($field,array('template_id'=>$template_id,'goods_id'=>$goods_id),'id desc','0,1','');
                if(!empty($res_pgoods[0]['settlement_price'])){
                    $settlement_price = $res_pgoods[0]['settlement_price'];
                }
            }
        }
        return $settlement_price;
    }
}