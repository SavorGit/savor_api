<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class GoodsModel extends BaseModel{
	protected $tableName='finance_goods';

    public function getGoodsInfo($fileds,$where){
        $res = $this->alias('goods')
            ->field($fileds)
            ->join('savor_finance_category cate on goods.category_id=cate.id','left')
            ->join('savor_finance_specification spec on goods.specification_id=spec.id','left')
            ->where($where)
            ->select();
        return $res;
    }
}