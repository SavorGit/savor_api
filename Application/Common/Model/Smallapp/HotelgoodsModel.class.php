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
        $key = C('FINANCE_HOTELSTOCK').':'.$hotel_id;
        $res_cache = $redis->get($key);
        $data = array();
        if(!empty($res_cache)) {
            $hotel_stock = json_decode($res_cache,true);
            if(!empty($hotel_stock['goods_ids'])){
                $m_hotel = new \Common\Model\HotelModel();
                $res_hotel = $m_hotel->getOneById('area_id',$hotel_id);
                $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
                $fields = 'id,name,price,advright_media_id,small_media_id,cover_imgs,line_price,type,finance_goods_id';
                $where = array('type'=>43,'status'=>1,'finance_goods_id'=>array('in',$hotel_stock['goods_ids']));
                $res_goods = $m_goods->getDataList($fields,$where,'wine_type asc',$offset,$pagesize);
                $data = $res_goods['list'];
                $m_goods_price_hotel = new \Common\Model\Smallapp\GoodsPriceHotelModel();
                foreach ($data as $k=>$v){
                    $res_price = $m_goods_price_hotel->getGoodsPrice($v['id'],$res_hotel['area_id'],$hotel_id);
                    if(!empty($res_price['price'])){
                        $data[$k]['price'] = $res_price['price'];
                        $data[$k]['line_price'] = $res_price['line_price'];
                    }
                }
            }
        }
        return $data;
    }

    public function getALLhotelStockGoodsList($hotel_ids){
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(9);
        $key = C('FINANCE_HOTELSTOCK');
        $hotel_goods_ids = array();
        foreach ($hotel_ids as $v){
            $hotel_id = $v['hotel_id'];
            $hotel_key = $key.':'.$hotel_id;
            $res_cache = $redis->get($hotel_key);
            if(!empty($res_cache)){
                $hotel_cache = json_decode($res_cache,true);
                if(!empty($hotel_cache[$hotel_id]['goods_ids'])){
                    foreach ($hotel_cache[$hotel_id]['goods_ids'] as $hgv){
                        $hotel_goods_ids[$hgv]=$hgv;
                    }
                }
            }
        }
        $all_data = array();
        if(!empty($hotel_goods_ids)){
            $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
            $fields = 'id,name,price,advright_media_id,small_media_id,cover_imgs,line_price,type,finance_goods_id';
            $where = array('type'=>43,'status'=>1,'finance_goods_id'=>array('in',array_values($hotel_goods_ids)));
            $res_goods = $m_goods->getALLDataList($fields,$where,'id desc','','finance_goods_id');
            foreach ($res_goods as $v){
                $all_data[$v['finance_goods_id']]=$v;
            }
        }
        return $all_data;
    }
}