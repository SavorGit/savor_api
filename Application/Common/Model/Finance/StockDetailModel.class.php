<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class StockDetailModel extends BaseModel{
	protected $tableName='finance_stock_detail';

    public function getStockGoods($fileds,$where){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_finance_goods goods on a.goods_id=goods.id','left')
            ->join('savor_finance_unit unit on a.unit_id=unit.id','left')
            ->join('savor_finance_category cate on goods.category_id=cate.id','left')
            ->join('savor_finance_specification spec on goods.specification_id=spec.id','left')
            ->where($where)
            ->select();
        return $res;
    }

    public function getStockGoodsByHotelId($fileds,$where,$group=''){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_finance_stock stock on a.stock_id=stock.id','left')
            ->join('savor_finance_goods goods on a.goods_id=goods.id','left')
            ->join('savor_finance_unit unit on a.unit_id=unit.id','left')
            ->join('savor_finance_category cate on goods.category_id=cate.id','left')
            ->join('savor_finance_specification spec on goods.specification_id=spec.id','left')
            ->where($where)
            ->group($group)
            ->select();
        return $res;
    }

    public function getStockDetailInfo($fileds,$where){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_finance_stock stock on a.stock_id=stock.id','left')
            ->where($where)
            ->find();
        return $res;
    }
}