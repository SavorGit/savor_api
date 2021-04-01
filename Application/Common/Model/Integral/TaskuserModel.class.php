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
            $cache_key = C('SMALLAPP_HOTEL_RELATION');
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(2);
            $res_cache = $redis->get($cache_key.$hotel_id);
            $where = array('a.hotel_id'=>$hotel_id,'task.type'=>1,'task.status'=>1,'task.flag'=>1);
            if(!empty($res_cache)){
                $relation_hotel_id = intval($res_cache);
                $where['a.hotel_id'] = array('in',array($hotel_id,$relation_hotel_id));
            }
            $fileds = 'a.task_id,task.start_time,task.end_time,task.is_long_time,task.status,task.flag';
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

    public function getCommentTask($openid,$hotel_id){
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $key_task = C('SAPP_SALE');
        $now_date = date('Ymd');
        $key_task = $key_task."task:getcomment{$openid}{$now_date}";
        $res_get = $redis->get($key_task);
        if(!empty($res_get)){
            return true;
        }

        $cache_key = C('SMALLAPP_HOTEL_RELATION');
        $redis->select(2);
        $res_cache = $redis->get($cache_key.$hotel_id);

        $where = array('a.hotel_id'=>$hotel_id,'task.type'=>1,'task.status'=>1,'task.flag'=>1);
        $where['task.task_type'] = array('in',array(4,5));//任务类型1开机,2互动,3活动推广,4邀请食客评价,5打赏补贴
        if(!empty($res_cache)){
            $relation_hotel_id = intval($res_cache);
            $where['a.hotel_id'] = array('in',array($hotel_id,$relation_hotel_id));
        }
        $fileds = 'a.task_id,task.start_time,task.end_time,task.is_long_time,task.status,task.flag,task.task_type';
        $m_taskhotel = new \Common\Model\Integral\TaskHotelModel();
        $res_taskhotel = $m_taskhotel->getHotelTasks($fileds,$where);
        if(!empty($res_taskhotel)){
            $task_ids = array();
            foreach ($res_taskhotel as $v) {
                $task_ids[]=$v['task_id'];
            }
            $where = array('openid'=>$openid);
            $where['task_id'] = array('in',$task_ids);
            $where["DATE_FORMAT(add_time,'%Y-%m-%d')"] = date('Y-m-d');
            $res_task = $this->where($where)->select();
            if(empty($res_task)){
                $now_time = date('Y-m-d H:i:s');
                $add_task = array();
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

                    $redis->select(14);
                    $redis->set($key_task,date('Y-m-d H:i:s'),86400);
                }
            }
        }
        return true;
    }

}