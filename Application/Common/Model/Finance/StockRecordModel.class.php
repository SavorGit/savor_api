<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class StockRecordModel extends BaseModel{
	protected $tableName='finance_stock_record';

    public function getStockRecordList($fileds,$where,$order,$limit,$group=''){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_finance_stock stock on a.stock_id=stock.id','left')
            ->join('savor_finance_goods goods on a.goods_id=goods.id','left')
            ->join('savor_finance_unit unit on a.unit_id=unit.id','left')
            ->join('savor_finance_category cate on goods.category_id=cate.id','left')
            ->join('savor_finance_specification spec on goods.specification_id=spec.id','left')
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->group($group)
            ->select();
        return $res;
    }

    public function getHotelStaffRecordList($fields,$where,$order,$limit,$group=''){
        $res = $this->alias('a')
            ->field($fields)
            ->join('savor_finance_sale sale on a.id=sale.stock_record_id','left')
            ->join('savor_finance_stock stock on a.stock_id=stock.id','left')
            ->join('savor_hotel hotel on stock.hotel_id=hotel.id','left')
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->group($group)
            ->select();
        return $res;
    }

    public function getHotelStaffStaticData($hotel_id,$openid){
        $fileds = 'count(DISTINCT goods.brand_id) as brand_num,count(DISTINCT goods.series_id) as series_num,count(a.id) as sell_num,a.op_openid';
        $data_goods_ids =C('DATA_GOODS_IDS');
        $where = array('a.type'=>7,'a.wo_status'=>array('in',array('1','2','4')),'a.goods_id'=>array('not in',$data_goods_ids));
        if($hotel_id){
            $where['stock.hotel_id'] = $hotel_id;
        }
        if(!empty($openid)){
            $where['a.op_openid'] = $openid;
        }
        $res_data = $this->alias('a')
            ->field($fileds)
            ->join('savor_finance_stock stock on a.stock_id=stock.id','left')
            ->join('savor_hotel hotel on stock.hotel_id=hotel.id','left')
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->join('savor_finance_goods goods on a.goods_id=goods.id','left')
            ->join('savor_finance_brand brand on goods.brand_id=brand.id','left')
            ->join('savor_finance_series series on goods.series_id=series.id','left')
            ->where($where)
            ->select();
        return $res_data;
    }

    public function getStaticData($area_id,$maintainer_id,$hotel_id,$start_time,$end_time,$group='',$wo_status='',$goods_id='',$ptype=''){
        $fileds = 'count(DISTINCT goods.brand_id) as brand_num,count(DISTINCT goods.series_id) as series_num,count(a.id) as sell_num,a.op_openid';
        $where = array('a.type'=>7,'a.wo_reason_type'=>1);
        if($wo_status){
            $where['a.wo_status'] = $wo_status;
        }else{
            $where['a.wo_status'] = array('in','1,2,4');
        }
        if(!empty($goods_id)){
            $where['a.goods_id'] = $goods_id;
        }else {
            $data_goods_ids = C('DATA_GOODS_IDS');
            $where['a.goods_id'] = array('not in',$data_goods_ids);
        }
        if(!empty($ptype) && $ptype<99){
            if($ptype==10){
                $where['sale.ptype'] = 0;
            }else{
                $where['sale.ptype'] = $ptype;
            }
        }
        if($area_id){
            $where['hotel.area_id'] = $area_id;
        }
        if($maintainer_id){
            $where['ext.maintainer_id'] = $maintainer_id;
        }
        if($hotel_id){
            $where['stock.hotel_id'] = $hotel_id;
        }
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $res_data = $this->alias('a')
            ->field($fileds)
            ->join('savor_finance_sale sale on a.id=sale.stock_record_id','left')
            ->join('savor_finance_stock stock on a.stock_id=stock.id','left')
            ->join('savor_hotel hotel on stock.hotel_id=hotel.id','left')
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->join('savor_finance_goods goods on a.goods_id=goods.id','left')
            ->join('savor_finance_brand brand on goods.brand_id=brand.id','left')
            ->join('savor_finance_series series on goods.series_id=series.id','left')
            ->where($where)
            ->group($group)
            ->select();
        return $res_data;
    }

    public function getStockIdcodeList($hotel_id,$goods_id,$limit){
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(9);
        $key = C('FINANCE_HOTELSTOCK').':'.$hotel_id;
        $res_cache = $redis->get($key);
        $res_stock = array();
        if(!empty($res_cache)){
            $hotel_stock = json_decode($res_cache,true);
            if(!empty($hotel_stock['goods_ids']) && in_array($goods_id,$hotel_stock['goods_ids'])){
                $where = array('stock.hotel_id'=>$hotel_id,'stock.type'=>20,'stock.io_type'=>22,'a.goods_id'=>$goods_id,'a.dstatus'=>1);
                $where['a.type']=7;
                $where['a.wo_status']= array('in',array(1,2,4));
                $res_worecord = $this->getStockRecordList('a.idcode',$where,'a.id desc','','');
                $use_idcode = array();
                if(!empty($res_worecord)){
                    foreach ($res_worecord as $v){
                        $use_idcode[]=$v['idcode'];
                    }
                }
                $where['a.type']=6;
                unset($where['a.wo_status']);
                $where['a.status']= array('in',array(1,2));
                $res_rlrecord = $this->getStockRecordList('a.idcode',$where,'a.id desc','','');
                if(!empty($res_rlrecord)){
                    foreach ($res_rlrecord as $v){
                        $use_idcode[]=$v['idcode'];
                    }
                }

                $where['a.type'] = 2;
                unset($where['a.status']);
                if(!empty($use_idcode)){
                    $where['a.idcode'] = array('not in',$use_idcode);
                }
                $fileds = 'a.idcode';
                $res_stock = $this->getStockRecordList($fileds,$where,'a.id desc',$limit,'');
            }
        }
        return $res_stock;
    }
}