<?php
namespace Smallsale19\Controller;
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

}