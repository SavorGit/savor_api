<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class CustomerLogModel extends BaseModel{
	protected $tableName='customer_log';

    public function getCustomerLogs($fileds,$where,$orderby='',$limit='',$group=''){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_customer customer on a.customer_id=customer.id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->group($group)
            ->select();
        return $res;
    }
}