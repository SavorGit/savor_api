<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class GoodsPriceHotelModel extends BaseModel{
	protected $tableName='smallapp_goods_price_hotel';

    public function getGoodsPrice($goods_id,$area_id,$hotel_id=0){
        $fileds = 'p.id as goods_price_id,p.price,p.line_price';
        $where = array('p.goods_id'=>$goods_id,'p.status'=>1,'a.area_id'=>$area_id);
        if($hotel_id){
            $where['a.hotel_id']= array('in',array($hotel_id,0));
        }
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_smallapp_goods_price p on a.goods_price_id=p.id','left')
            ->where($where)
            ->order('p.id desc')
            ->limit(0,1)
            ->select();
        $price_data = array();
        if(!empty($res[0]['goods_price_id'])){
            $price_data = $res[0];
        }
        return $price_data;
    }

    public function getGoodsAreaPrice($goods_id,$area_id){
        $fileds = 'p.id as goods_price_id,p.price,p.line_price';
        $where = array('p.goods_id'=>$goods_id,'p.status'=>1,'a.area_id'=>$area_id);
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_smallapp_goods_price p on a.goods_price_id=p.id','left')
            ->where($where)
            ->order('p.id desc')
            ->limit(0,1)
            ->select();
        $price_data = array();
        if(!empty($res[0]['goods_price_id'])){
            $price_data = $res[0];
        }
        return $price_data;
    }

    public function getGoodsHotelPrice($goods_id,$area_id,$hotel_id){
        $fileds = 'p.id as goods_price_id,p.price,p.line_price,a.id as price_hotel_id';
        $where = array('p.goods_id'=>$goods_id,'p.status'=>1,'a.area_id'=>$area_id,'a.hotel_id'=>$hotel_id);
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_smallapp_goods_price p on a.goods_price_id=p.id','left')
            ->where($where)
            ->order('p.id desc')
            ->limit(0,1)
            ->select();
        $price_data = array();
        if(!empty($res[0]['goods_price_id'])){
            $price_data = $res[0];
        }
        return $price_data;
    }
}