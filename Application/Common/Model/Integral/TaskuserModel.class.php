<?php
namespace Common\Model\Integral;
use Common\Model\BaseModel;
class TaskuserModel extends BaseModel{
    protected $tableName = 'integral_task_user';

    public function getTask($openid,$hotel_id){
        $where = array('openid'=>$openid);
        $where["DATE_FORMAT(add_time,'%Y-%m-%d')"] = date('Y-m-d');
        $res_task = $this->where($where)->select();
        if(empty($res_task)){
            $fileds = 'a.task_id,task.start_time,task.end_time,task.is_long_time,task.status,task.flag';
            $where = array('a.hotel_id'=>$hotel_id,'task.status'=>1,'task.flag'=>1);
            $m_taskhotel = new \Common\Model\Integral\TaskHotelModel();
            $res_taskhotel = $m_taskhotel->getHotelTasks($fileds,$where);
            $add_task = array();
            if(!empty($res_taskhotel)){
                $now_time = date('Y-m-d H:i:s');
                foreach ($res_taskhotel as $v){
                    if($v['is_long_time']){
                        $add_task[] = array('openid'=>$openid,'task_id'=>$v['task_id']);
                    }else{
                        if($now_time>=$v['start_time'] && $now_time<=$v['end_time']){
                            $add_task[] = array('openid'=>$openid,'task_id'=>$v['task_id']);
                        }
                    }
                }
                if(!empty($add_task)){
                    $this->addAll($add_task);
                }
            }
        }
        return true;
    }

}