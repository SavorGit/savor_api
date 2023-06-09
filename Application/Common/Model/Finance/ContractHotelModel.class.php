<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class ContractHotelModel extends BaseModel{
	protected $tableName='finance_contract_hotel';

	public function getContractData($fields,$where,$orderby){
        $data = $this->alias('a')
            ->join('savor_finance_contract contract on a.contract_id=contract.id','left')
            ->field($fields)
            ->where($where)
            ->order($orderby)
            ->select();
        return $data;
    }
}