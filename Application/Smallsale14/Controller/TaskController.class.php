<?php
namespace Smallsale14\Controller;
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
        }
        parent::_init_();
    }
    public function getHotelTastList(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid   = trim($this->params['openid']);
        $page     = $this->params['page'] ? $this->params['page'] : 1;
        $this->checkUser($openid,$hotel_id);
        
        $m_task_hotel = new \Common\Model\Integral\TaskHotelModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $fields = "task.id task_id,task.name task_name ,concat('".$oss_host."',media.`oss_addr`) img_url,task.desc";
        $where = [];
        $where['a.hotel_id'] = $hotel_id;
        $where['task.status'] = 1;
        $where['task.flag']  = 1;
        $pagesize = 20;
        $size = ($page - 1) * $pagesize;
        $order = 'task.id asc';
        $task_list = $m_task_hotel->alias('a')
                                  ->join('savor_integral_task task on a.task_id=task.id','left')
                                  ->join('savor_media media on task.media_id=media.id','left')
                                  ->field($fields)
                                  ->where($where)
                                  ->order($order)
                                  ->limit(0,$size)
                                  ->select();
        $m_task_user = new \Common\Model\Integral\TaskuserModel();
        $start_time = date('Y-m-d 00:00:00');
        $end_time   = date('Y-m-d 23:59:59');
        foreach($task_list as $key=>$v){
            $map = [];
            $map['openid'] = $openid;
            $map['add_time'] = array(array('EGT',$start_time),array('ELT',$end_time));
            $rs = $m_task_user->field('integral')->where(array('openid'))->find();
            $task_list[$key]['integral'] = intval($rs['integral']);
            $task_list[$key]['progress']     = '今日获得积分';
        }
        
        $this->to_back($task_list);
    }
    
    private function checkUser($openid,$hotel_id){
        
        $where = [];
        $where['a.openid']    = $openid;
        $where['im.hotel_id'] = $hotel_id;
        
        
        
        
        $m_staff = new \Common\Model\Integral\StaffModel();
        
        
        $nums = $m_staff->alias('a')
                ->join('savor_integral_merchant mt on a.merchant_id=mt.id','left')
                ->field('a.id')->where(array('a.openid'=>$openid,'a.status'=>1,'mt.status'=>1,'mt.type'=>3))
                ->count();
        if(empty($nums)){
            $nums = $m_staff->alias('a')
            ->join('savor_integral_merchant im on a.merchant_id= im.id','left')
            ->where($where)
            ->count();
            if(empty($nums)) $this->to_back(93014);
        }
        
        
    }
}