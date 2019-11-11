<?php
namespace Common\Model\Integral;
use Common\Model\BaseModel;

class TaskHotelModel extends BaseModel{
	protected $tableName='integral_task_hotel';

	public function getHotelTasks($fileds,$where){
        $data = $this->alias('a')
            ->field($fileds)
            ->join('savor_integral_task task on a.task_id=task.id','left')
            ->where($where)
            ->select();
        return $data;
    }
}