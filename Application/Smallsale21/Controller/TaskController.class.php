<?php
namespace Smallsale21\Controller;
use \Common\Controller\CommonController as CommonController;

class TaskController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotelTastList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001,'page'=>1001);
                break;
            case 'getShareprofit':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001,'task_id'=>1001);
                break;
            case 'setShareprofit':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001,'task_id'=>1001,
                    'level1'=>1001,'level2'=>1001,'level3'=>1002);
                break;
            case 'getCashTaskList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001);
                break;
            case 'receiveCashTask':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'task_id'=>1001,'openid'=>1001);
                break;
            case 'getInProgressCashTask':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001);
                break;
            case 'getCashTask':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'task_id'=>1001,'openid'=>1001);
                break;


        }
        parent::_init_();
    }

    public function getHotelTastList(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid   = trim($this->params['openid']);
        $page     = $this->params['page'] ? $this->params['page'] : 1;
        $pagesize = 20;

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1,'merchant.type'=>3);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $field_staff = 'a.openid,a.level,merchant.type';
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
            $m_staff = new \Common\Model\Integral\StaffModel();
            $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
            if(empty($res_staff)){
                $this->to_back(93014);
            }
        }
        
        $m_task_hotel = new \Common\Model\Integral\TaskHotelModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $fields = "task.id task_id,task.name task_name ,concat('".$oss_host."',media.`oss_addr`) img_url,task.desc,task.is_shareprofit";
        $where = array('a.hotel_id'=>$hotel_id,'task.status'=>1,'task.flag'=>1);
        $order = 'task.id asc';
        $size = ($page-1) * $pagesize;
        $task_list = $m_task_hotel->getHotelTaskList($fields,$where,$order,0,$size);
        $m_task_user = new \Common\Model\Integral\TaskuserModel();
        $start_time = date('Y-m-d 00:00:00');
        $end_time   = date('Y-m-d 23:59:59');
        foreach($task_list as $key=>$v){
            $map = array('openid'=>$openid,'task_id'=>$v['task_id']);
            $map['add_time'] = array(array('EGT',$start_time),array('ELT',$end_time));
            $rs = $m_task_user->field('integral')->where($map)->find();
            $task_list[$key]['integral'] = intval($rs['integral']);
            $task_list[$key]['progress'] = '今日获得积分';
        }
        $this->to_back($task_list);
    }

    public function getShareprofit(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];
        $task_id = intval($this->params['task_id']);

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $res_staff = $res_staff[0];

        $level1 = $level2 = $level3 = $last_num = 0;
        if($res_staff['level']==1){
            $where = array('task_id'=>$task_id);
            $where['hotel_id'] = array('in',array(0,$hotel_id));
            $m_task_shareprofit = new \Common\Model\Integral\TaskShareprofitModel();
            $res_shareprofit = $m_task_shareprofit->getTaskShareprofit('level1,level2,level3',$where,'id desc',0,1);

            $level1 = intval($res_shareprofit[0]['level1']);
            $level2 = intval($res_shareprofit[0]['level2']);
            $level3 = intval($res_shareprofit[0]['level3']);

            $where = array('task_id'=>$task_id,'hotel_id'=>$hotel_id,'openid'=>$openid);
            $start_time = date('Y-m-01 00:00:00');
            $end_time   = date('Y-m-31 23:59:59');
            $where['add_time'] = array(array('EGT',$start_time),array('ELT',$end_time));
            $res_num = $m_task_shareprofit->getTaskShareprofit('count(id) as num',$where,'id desc',0,1);
            $num = intval($res_num[0]['num']);
            $all_num = 3;
            $last_num = $all_num-$num>0?$all_num-$num:0;
        }
        $res = array('level1'=>$level1,'level2'=>$level2,'level3'=>$level3,'num'=>$last_num);
        $this->to_back($res);
    }

    public function setShareprofit(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];
        $task_id = intval($this->params['task_id']);
        $level1 = intval($this->params['level1']);
        $level2 = intval($this->params['level2']);
        $level3 = !empty($this->params['level3'])?intval($this->params['level3']):0;

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        if($res_staff[0]['level']!=1){
           $this->to_back(93027);
        }
        if($level1+$level2+$level3!=100){
            $this->to_back(93028);
        }

        $where = array('task_id'=>$task_id,'hotel_id'=>$hotel_id,'openid'=>$openid);
        $start_time = date('Y-m-01 00:00:00');
        $end_time   = date('Y-m-31 23:59:59');
        $where['add_time'] = array(array('EGT',$start_time),array('ELT',$end_time));
        $m_task_shareprofit = new \Common\Model\Integral\TaskShareprofitModel();
        $res_num = $m_task_shareprofit->getTaskShareprofit('count(id) as num',$where,'id desc',0,1);
        $num = intval($res_num[0]['num']);
        if($num>=3){
            $this->to_back(93026);
        }
        $add_data = array('level1'=>$level1,'level2'=>$level2,'level3'=>$level3,
            'task_id'=>$task_id,'hotel_id'=>$hotel_id,'openid'=>$openid);
        $m_task_shareprofit->add($add_data);

        $all_num = 3;
        $last_num = $all_num - $num -1>0?$all_num - $num -1:0;
        $res = array('level1'=>$level1,'level2'=>$level2,'level3'=>$level3,'num'=>$last_num);
        $this->to_back($res);
    }

    public function getCashTaskList(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_usertask = new \Common\Model\Smallapp\UserTaskModel();
        $where = array('openid'=>$openid,'status'=>array('in',array(1,2)));
        $res_usertask = $m_usertask->getDataList('*',$where,'id desc',0,1);
        $is_has_task = 0;
        if($res_usertask['total']>0){
            $is_has_task = 1;
        }
        $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
        $fields = 'a.id as task_hid,a.task_id,task.name,task.media_id,task.end_time';
        $where = array('task.type'=>2,'task.task_type'=>21,'task.status'=>1,'task.flag'=>1);
        $where['task.end_time'] = array('egt',date('Y-m-d H:i:s'));
        $res_task = $m_hoteltask->getHotelTaskList($fields,$where,'task.money desc',0,100);
        $datalist = array();
        if(!empty($res_task)){
            $m_media = new \Common\Model\MediaModel();
            foreach ($res_task as $v){
                $send_num = 0;
                $where = array('task_id'=>$v['task_id'],'status'=>array('in',array(4,5)));
                $fields = 'count(id) as num';
                $res_task_num = $m_usertask->getDataList($fields,$where,'id desc');
                if(!empty($res_task_num)){
                    $send_num = intval($res_task_num['num']);
                }
                $status = 1;
                if($is_has_task){
                    $status = 2;
                }
                $img_url = '';
                if(!empty($v['media_id'])){
                    $res_media = $m_media->getMediaInfoById($v['media_id']);
                    $img_url = $res_media['oss_addr'];
                }
                $info = array('task_id'=>$v['task_hid'],'name'=>$v['name'],'img_url'=>$img_url,
                    'send_num'=>$send_num,'status'=>$status,'end_time'=>$v['end_time']);
                $datalist[]=$info;
            }
        }
        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function getInProgressCashTask(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $m_usertask = new \Common\Model\Smallapp\UserTaskModel();
        $where = array('openid'=>$openid,'status'=>array('in',array(1,2)));
        $res_usertask = $m_usertask->getDataList('*',$where,'id desc',0,1);
        $task_id = $percent = 0;
        $status = 0;//0无领取任务 1进行中 2已完成
        $name = $img_url = $end_time = '';

        if($res_usertask['total']>0){
            $task_id = $res_usertask['list'][0]['task_hotel_id'];
            $status = $res_usertask['list'][0]['status'];
            if($status==1){
                $now_time = date('Y-m-d H:i:s');
                $m_task = new \Common\Model\Integral\TaskModel();
                $res_otask = $m_task->getInfo(array('id'=>$res_usertask['list'][0]['task_id']));
                if($res_otask['end_time']<$now_time){
                    $m_usertask->updateData(array('id'=>$res_usertask['list'][0]['id']),array('status'=>3));
                    $task_id = 0;
                    $status = 0;
                    $percent = 0;
                }else{
                    $percent = sprintf("%.4f",$res_usertask['list'][0]['get_money']/$res_usertask['list'][0]['money']);
                    $percent = $percent * 100;
                }
            }else{
                $percent = 100;
            }
            if($task_id){
                $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
                $where = array('a.id'=>$task_id);
                $fileds = 'a.meal_num,a.interact_num,a.comment_num,a.finish_num,task.name,task.media_id,task.end_time';
                $res_task = $m_hoteltask->getHotelTasks($fileds,$where);
                $name = $res_task[0]['name'];
                $end_time = $res_task[0]['end_time'];
                if(!empty($res_task[0]['media_id'])){
                    $m_media = new \Common\Model\MediaModel();
                    $res_media = $m_media->getMediaInfoById($res_task[0]['media_id']);
                    $img_url = $res_media['oss_addr'];
                }
            }
        }
        $data = array('task_id'=>$task_id,'status'=>$status,'percent'=>$percent,
            'name'=>$name,'img_url'=>$img_url,'end_time'=>$end_time);
        $this->to_back($data);
    }

    public function getCashTask(){
        $hotel_id = intval($this->params['hotel_id']);
        $task_id = intval($this->params['task_id']);
        $openid = $this->params['openid'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,a.hotel_id,a.room_ids,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $staff_info = $res_staff[0];
        $is_bind_room = 0;
        if($staff_info['hotel_id']==$hotel_id && !empty($staff_info['room_ids'])){
            $is_bind_room = 1;
        }

        $m_usertask = new \Common\Model\Smallapp\UserTaskModel();
        $where = array('openid'=>$openid,'task_hotel_id'=>$task_id);
        $res_usertask = $m_usertask->getInfo($where);
        if(empty($res_usertask)){
            $this->to_back(93060);
        }
        if($res_usertask['status']==3){
            $this->to_back(93061);
        }
        if(in_array($res_usertask['status'],array(4,5))){
            $this->to_back(93062);
        }
        $task_id = $percent = $send_num = 0;
        $status = 0;//0无领取任务 1进行中 2已完成
        $name = $img_url = $end_time = '';
        $money = $get_money = 0;
        $content = array();
        if(in_array($res_usertask['status'],array(1,2))){
            $task_id = $res_usertask['task_hotel_id'];
            $status = $res_usertask['status'];
            if($status==1){
                $now_time = date('Y-m-d H:i:s');
                $m_task = new \Common\Model\Integral\TaskModel();
                $res_otask = $m_task->getInfo(array('id'=>$res_usertask['task_id']));
                if($res_otask['end_time']<$now_time){
                    $m_usertask->updateData(array('id'=>$res_usertask['id']),array('status'=>3));
                    $task_id = 0;
                    $status = 0;
                    $percent = 0;
                }else{
                    $percent = sprintf("%.2f",$res_usertask['get_money']/$res_usertask['money']);
                    $percent = $percent*100;
                    $money = $res_usertask['money'];
                    $get_money = $res_usertask['get_money'];
                }
            }else{
                $percent = 100;
            }
            if($task_id){
                $where = array('task_id'=>$res_usertask['task_id'],'status'=>array('in',array(4,5)));
                $fields = 'count(id) as num';
                $res_task_num = $m_usertask->getDataList($fields,$where,'id desc');
                if(!empty($res_task_num)){
                    $send_num = intval($res_task_num['num']);
                }

                $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
                $where = array('a.id'=>$task_id);
                $fileds = 'a.meal_num,a.interact_num,a.comment_num,a.finish_num,task.name,task.media_id,task.end_time';
                $res_task = $m_hoteltask->getHotelTasks($fileds,$where);
                $end_time = $res_task[0]['end_time'];
                if(!empty($res_task[0]['media_id'])){
                    $m_media = new \Common\Model\MediaModel();
                    $res_media = $m_media->getMediaInfoById($res_task[0]['media_id']);
                    $img_url = $res_media['oss_addr'];
                }
                $content = $m_hoteltask->getTaskinfo($res_task[0],$res_usertask);
            }
        }
        $data = array('task_id'=>$task_id,'status'=>$status,'percent'=>$percent,'money'=>$money,
            'get_money'=>$get_money,'name'=>$name,'img_url'=>$img_url,'end_time'=>$end_time,
            'send_num'=>$send_num,'is_bind_room'=>$is_bind_room,'content'=>$content
        );
        $this->to_back($data);
    }

    public function receiveCashTask(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];
        $task_id = $this->params['task_id'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $m_usertask = new \Common\Model\Smallapp\UserTaskModel();
        $where = array('openid'=>$openid,'status'=>array('in',array(1,2)));
        $res_usertask = $m_usertask->getDataList('*',$where,'id desc',0,1);
        if($res_usertask['total']>0){
           $this->to_back(93063);
        }
        $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
        $where = array('a.id'=>$task_id,'task.status'=>1,'task.flag'=>1);
        $fileds = 'a.meal_num,a.interact_num,a.comment_num,a.finish_num,task.id as task_id,task.money,task.name,task.media_id,task.end_time';
        $res_task = $m_hoteltask->getHotelTasks($fileds,$where);
        if(empty($res_task)){
            $this->to_back(93060);
        }
        $now_time = date('Y-m-d H:i:s');
        if($res_task[0]['end_time']<$now_time){
            $this->to_back(93061);
        }
        $money = $res_task[0]['money'];
        $get_money = $money * 0.7;
        $data = array('openid'=>$openid,'task_id'=>$res_task[0]['task_id'],'task_hotel_id'=>$task_id,'money'=>$money,
            'get_money'=>$get_money,'status'=>1,'type'=>1
        );
        $user_task_id = $m_usertask->add($data);
        $this->to_back(array('user_task_id'=>$user_task_id));
    }

}