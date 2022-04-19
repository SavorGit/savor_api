<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class QrcodeContentModel extends BaseModel{
	protected $tableName='finance_qrcode_content';

    public function getQrcodeList($fields,$where,$orderby,$start=0,$size=0){
        $data = $this->field($fields)->where($where)->order($orderby)->limit($start,$size)->select();
        return $data;
    }
}