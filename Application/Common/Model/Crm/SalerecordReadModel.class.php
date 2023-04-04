<?php
namespace Common\Model\Crm;
use Common\Model\BaseModel;

class SalerecordReadModel extends BaseModel{
	protected $tableName='crm_salerecord_read';

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

        $res_read = $this->getInfo(array('salerecord_id'=>$salerecord_id,'user_id'=>$user_id));
        if(empty($res_read)){
            $add_data = array('salerecord_id'=>$salerecord_id,'user_id'=>$user_id,'add_time'=>date('Y-m-d H:i:s'));
            $this->add($add_data);
        }
        return true;
    }
}