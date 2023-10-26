<?php
namespace Common\Model\Crm;
use Common\Model\BaseModel;

class TaskRecordModel extends BaseModel{
	protected $tableName='crm_task_record';

    public function getTaskRecords($fileds,$where,$orderby='',$limit='',$group=''){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_crm_task task on a.task_id=task.id','left')
            ->join('savor_hotel hotel on a.hotel_id=hotel.id','left')
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->group($group)
            ->select();
        return $res;
    }

    public function getCustomTasks($fileds,$where,$orderby='',$limit='',$group=''){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_crm_task task on a.task_id=task.id','left')
            ->join('savor_ops_staff staff on task.ops_staff_id=staff.id','left')
            ->join('savor_sysuser su on staff.sysuser_id=su.id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->group($group)
            ->select();
        return $res;
    }
}