<?php
namespace Common\Model\Crm;
use Common\Model\BaseModel;

class SalerecordTaskModel extends BaseModel{
	protected $tableName='crm_salerecord_task';

    public function getSalerecordTask($fileds,$where,$orderby='',$limit='',$group=''){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_crm_task_record tr on a.task_record_id=tr.id','left')
            ->join('savor_crm_task task on tr.task_id=task.id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->group($group)
            ->select();
        return $res;
    }
}