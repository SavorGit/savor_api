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

    public function getStaticData($area_id,$maintainer_id,$start_time,$end_time){
        $fileds = 'count(DISTINCT goods.brand_id) as brand_num,count(DISTINCT goods.series_id) as series_num,count(a.id) as sell_num';
        $where = array('staff.status'=>1,'merchant.status'=>1);
        if($area_id){
            $where['hotel.area_id'] = $area_id;
        }
        if($maintainer_id){
            $where['ext.maintainer_id'] = $maintainer_id;
        }
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $res_data = $this->alias('a')
            ->field($fileds)
            ->join('savor_integral_merchant_staff staff on a.op_openid=staff.openid','left')
            ->join('savor_integral_merchant merchant on staff.merchant_id=merchant.id','left')
            ->join('savor_hotel hotel on merchant.hotel_id=hotel.id','left')
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->join('savor_finance_goods goods on a.goods_id=goods.id','left')
            ->join('savor_finance_brand brand on goods.brand_id=brand.id','left')
            ->join('savor_finance_series series on goods.series_id=series.id','left')
            ->where($where)
            ->select();
        $brand_num = $series_num = $sell_num = 0;
        if(!empty($res_data)){
            $brand_num = intval($res_data[0]['brand_num']);
            $series_num = intval($res_data[0]['series_num']);
            $sell_num = intval($res_data[0]['sell_num']);
        }
        return array('brand_num'=>$brand_num,'series_num'=>$series_num,'sell_num'=>$sell_num);
    }
}