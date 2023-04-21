<?php
namespace Common\Model\Crm;
use Common\Model\BaseModel;

class StockcheckRecordModel extends BaseModel{
	protected $tableName='crm_stockcheck_record';

    public function getCheckRecordList($fields,$where,$orderby,$limit='',$group=''){
        $data = $this->alias('record')
            ->field($fields)
            ->join('savor_finance_goods goods on record.goods_id=goods.id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }
}