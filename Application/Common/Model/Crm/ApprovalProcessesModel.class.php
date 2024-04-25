<?php
namespace Common\Model\Crm;
use Common\Model\BaseModel;

class ApprovalProcessesModel extends BaseModel{
	protected $tableName='approval_processes';

    public function getProcessDatas($fields,$where,$order,$limit,$group){
        $data = $this->alias('a')
            ->join('savor_approval approval on a.approval_id=approval.id','left')
            ->join('savor_approval_item item on approval.item_id=item.id','left')
            ->join('savor_hotel hotel on approval.hotel_id=hotel.id','left')
            ->join('savor_ops_staff staff on approval.ops_staff_id=staff.id','left')
            ->join('savor_smallapp_user user on staff.openid=user.openid','left')
            ->join('savor_sysuser sysuser on staff.sysuser_id=sysuser.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }

    public function getDatas($fields,$where,$order){
        $data = $this->alias('a')
            ->join('savor_approval_step step on a.step_id=step.id','left')
            ->join('savor_ops_staff staff on a.ops_staff_id=staff.id','left')
            ->join('savor_sysuser sysuser on staff.sysuser_id=sysuser.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->select();
        return $data;
    }
}