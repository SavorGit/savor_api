<?php
namespace Common\Model\Crm;
use Common\Model\BaseModel;

class SalerecordModel extends BaseModel{
	protected $tableName='crm_salerecord';

    public function getRecordList($fields,$where,$orderby,$limit='',$group=''){
        $data = $this->alias('record')
            ->field($fields)
            ->join('savor_ops_staff staff on record.ops_staff_id=staff.id','left')
            ->join('savor_smallapp_user user on staff.openid=user.openid','left')
            ->join('savor_sysuser sysuser on staff.sysuser_id=sysuser.id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }
}