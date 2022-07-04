<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class HotelgoodsModel extends BaseModel{
	protected $tableName='smallapp_hotelgoods';

    public function getList($fields,$where,$order,$limit,$group=''){
        $data = $this->alias('h')
            ->join('savor_smallapp_goods g on h.goods_id=g.id','left')
            ->field($fields)
            ->where($where)
            ->group($group)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;
    }

    public function getGoodsList($fields,$where,$order,$limit,$group=''){
        $data = $this->alias('h')
            ->join('savor_hotel hotel on h.hotel_id=hotel.id','left')
            ->join('savor_smallapp_dishgoods g on h.goods_id=g.id','left')
            ->field($fields)
            ->where($where)
            ->group($group)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;
    }

    public function getStockGoodsList($hotel_id,$offset,$pagesize){
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(9);
        $key = C('FINANCE_HOTELSTOCK');
        $res_cache = $redis->get($key);
        if(!empty($res_cache)){
            $hotel_stock = json_decode($res_cache,true);
            if(isset($hotel_stock[$hotel_id])){
                $fields = 'g.id,g.name,g.price,g.advright_media_id,g.cover_imgs,g.line_price,g.type,g.finance_goods_id';
                $where = array('h.hotel_id'=>$hotel_id,'g.type'=>43,'g.status'=>1);
                $order = 'g.wine_type asc';
                $res_data = $this->getGoodsList($fields,$where,$order,'','');
                $all_data = array();
                foreach ($res_data as $v){
                    if(in_array($v['finance_goods_id'],$hotel_stock[$hotel_id]['goods_ids'])){
                        $all_data[]=$v;
                    }
                }
                $data = array_slice($all_data,$offset,$pagesize);
            }else{
                $data = array();
            }
        }else{
            $data = array();
        }
        return $data;
    }
}