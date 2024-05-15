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
            ->join('savor_approval approval on a.approval_id=approval.id','left')
            ->join('savor_approval_step step on a.step_id=step.id','left')
            ->join('savor_ops_staff staff on a.ops_staff_id=staff.id','left')
            ->join('savor_sysuser sysuser on staff.sysuser_id=sysuser.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->select();
        return $data;
    }

    public function handleProcessStatus($stock_id,$type,$hotel_id=0){
        $m_approval = new \Common\Model\Crm\ApprovalModel();
        $res_approval = $m_approval->getInfo(array('stock_id'=>$stock_id));
        $approval_id = 0;
        if(empty($res_approval)){
            return $approval_id;
        }
        $approval_id = $res_approval['id'];
        $m_approval_process = new \Common\Model\Crm\ApprovalProcessesModel();
        switch ($type){
            case 2://2出库,4领取,5验收
                $m_approval->updateData(array('id'=>$approval_id),array('status'=>5));
                $pwhere = array('approval_id'=>$approval_id,'step_order'=>2);
                $res_process = $m_approval_process->getInfo($pwhere);
                if(!empty($res_process)){
                    $m_approval_process->updateData(array('id'=>$res_process['id']),array('handle_status'=>3));
                }
                $res_next = $m_approval_process->getInfo(array('approval_id'=>$approval_id,'step_order'=>3));
                if(!empty($res_next)){
                    $m_approval_process->updateData(array('id'=>$res_next['id']),array('is_receive'=>1,'handle_status'=>1));
                }
                break;
            case 4:
                $m_approval->updateData(array('id'=>$approval_id),array('status'=>7));
                break;
            case 5:
                $m_approval->updateData(array('id'=>$approval_id),array('status'=>9));
                $res_process = $m_approval_process->getDataList('id',array('approval_id'=>$approval_id,'step_order'=>array('in','3,4')),'id asc');
                foreach ($res_process as $v){
                    $m_approval_process->updateData(array('id'=>$v['id']),array('handle_status'=>3));
                }
                break;
            case 12:
                $res_approval = $m_approval->getDataList('*',array('hotel_id'=>$hotel_id,'status'=>11),'id asc');
                if(!empty($res_approval)){
                    foreach ($res_approval as $v){
                        $approval_id = $v['id'];
                        $m_approval->updateData(array('id'=>$approval_id),array('status'=>12,'real_recycle_time'=>date('Y-m-d H:i:s')));

                        $pwhere = array('approval_id'=>$approval_id,'step_order'=>2);
                        $res_process = $m_approval_process->getInfo($pwhere);
                        if(!empty($res_process)){
                            $m_approval_process->updateData(array('id'=>$res_process['id']),array('handle_status'=>3));
                        }
                    }

                }
                break;
        }
        return $approval_id;
    }
}