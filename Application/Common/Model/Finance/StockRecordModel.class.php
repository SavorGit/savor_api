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

    public function getStaticData($area_id,$maintainer_id,$hotel_id,$start_time,$end_time,$group=''){
        $fileds = 'count(DISTINCT goods.brand_id) as brand_num,count(DISTINCT goods.series_id) as series_num,count(a.id) as sell_num,a.op_openid';
        $where = array();
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
}