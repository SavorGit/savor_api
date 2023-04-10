<?php
namespace Common\Model\Crm;
use Common\Model\BaseModel;

class SalerecordReadModel extends BaseModel{
	protected $tableName='crm_salerecord_read';

    public function getReadDataList($fields,$where,$orderby,$limit=''){
        $data = $this->alias('a')
            ->field($fields)
            ->join('savor_ops_staff staff on a.user_id=staff.id','left')
            ->join('savor_smallapp_user user on staff.openid=user.openid','left')
            ->join('savor_sysuser sysuser on staff.sysuser_id=sysuser.id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->select();
        return $data;
    }

	public function readRecord($staff,$record){
	    $dev_max_uid = 7;
	    if($staff['id']==$record['ops_staff_id'] || $staff['hotel_role_type']==3){
	        return true;
        }
	    $user_id = $staff['id'];
	    $salerecord_id = $record['id'];
        $m_salerecord_remind = new \Common\Model\Crm\SalerecordRemindModel();
        $rwhere = array('salerecord_id'=>$salerecord_id,'remind_user_id'=>$user_id,'status'=>1);
        $m_salerecord_remind->updateData($rwhere,array('read_status'=>2,'read_time'=>date('Y-m-d H:i:s')));
        return true;
    }
}