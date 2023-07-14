<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class SalePaymentRecordModel extends BaseModel{
	protected $tableName='finance_sale_payment_record';

    public function getSalePaymentRecordList($fileds,$where,$group=''){
        $res_data = $this->alias('a')
            ->field($fileds)
            ->join('savor_finance_sale sale on a.sale_id=sale.id','left')
            ->join('savor_finance_stock_record record on sale.stock_record_id=record.id','left')
            ->where($where)
            ->group($group)
            ->select();
        return $res_data;
    }
}