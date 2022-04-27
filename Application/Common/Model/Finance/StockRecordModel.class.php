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
}