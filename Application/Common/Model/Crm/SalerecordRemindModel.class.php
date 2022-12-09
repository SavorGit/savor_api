<?php
namespace Common\Model\Crm;
use Common\Model\BaseModel;

class SalerecordRemindModel extends BaseModel{
	protected $tableName='crm_salerecord_remind';

    public function getList($fields,$where,$orderby,$limit='',$group=''){
        $data = $this->alias('a')
            ->field($fields)
            ->join('savor_crm_salerecord record on a.salerecord_id=record.id','left')
            ->join('savor_ops_staff staff on a.remind_user_id=staff.id','left')
            ->join('savor_smallapp_user user on staff.openid=user.openid','left')
            ->join('savor_sysuser sysuser on staff.sysuser_id=sysuser.id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }

    public function getRemindRecordList($fields,$where,$orderby,$limit='',$group=''){
        $data = $this->alias('a')
            ->field($fields)
            ->join('savor_crm_salerecord record on a.salerecord_id=record.id','left')
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