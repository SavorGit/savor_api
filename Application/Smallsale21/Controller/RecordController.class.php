<?php
namespace Smallsale21\Controller;
use \Common\Controller\CommonController as CommonController;

class RecordController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'taskprocess':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'task_id'=>1001,'openid'=>1001);
                break;
            case 'taskclaim':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'task_id'=>1001,'openid'=>1001);
                break;
            case 'taskfinish':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001,'page'=>1);
                break;
            case 'rollexchangelist':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;


        }
        parent::_init_();
    }

    public function taskprocess(){
        $hotel_id = intval($this->params['hotel_id']);
        $task_id = intval($this->params['task_id']);
        $openid = $this->params['openid'];
        $page = $this->params['page']?$this->params['page']:1;
        $pagesize = 10;

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $m_usertaskrecord = new \Common\Model\Smallapp\UserTaskRecordModel();
        $where = array('openid'=>$openid,'usertask_id'=>$task_id,'type'=>1);
        $all_nums = $page * $pagesize;
        $count_fields = 'COUNT(DISTINCT(DATE(add_time))) AS tp_count';
        $fields = 'DATE(add_time) add_date';
        $group = 'DATE(add_time)';
        $res_usertask = $m_usertaskrecord->getRecordListdate($fields,$where,'id desc',$count_fields,$group,0,$all_nums);
        $total = $res_usertask['total'];
        $datalist = array();
        if($res_usertask['total']>0){
            $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
            foreach ($res_usertask['list'] as $k=>$v){
                $date = $v['add_date'];
                $where['DATE(add_time)'] = $date;
                $ut_fields = 'sum(meal_num) as meal_num,sum(comment_num) as comment_num,sum(interact_num) as interact_num,sum(lottery_num) as lottery_num';
                $res_list = $m_usertaskrecord->getDataList($ut_fields,$where,'id desc');
                $content = $m_hoteltask->getTaskinfo($res_list[0]);
                $info = array('date_str'=>date("n月d日",strtotime($date)),'content'=>$content);
                $datalist[]=$info;
            }
        }
        $data = array('total'=>$total,'datalist'=>$datalist);
        $this->to_back($data);
    }

    public function taskclaim(){
        $hotel_id = intval($this->params['hotel_id']);
        $task_id = intval($this->params['task_id']);
        $openid = $this->params['openid'];
        $page = $this->params['page']?$this->params['page']:1;
        $pagesize = 10;

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $m_usertaskrecord = new \Common\Model\Smallapp\UserTaskRecordModel();
        $where = array('openid'=>$openid,'usertask_id'=>$task_id,'type'=>2);
        $all_nums = $page * $pagesize;
        $count_fields = 'COUNT(DISTINCT(DATE(add_time))) AS tp_count';
        $fields = 'DATE(add_time) add_date';
        $group = 'DATE(add_time)';
        $res_usertask = $m_usertaskrecord->getRecordListdate($fields,$where,'id desc',$count_fields,$group,0,$all_nums);
        $total = $res_usertask['total'];
        $datalist = array();
        if($res_usertask['total']>0){
            $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
            foreach ($res_usertask['list'] as $k=>$v){
                $date = $v['add_date'];
                $where['DATE(add_time)'] = $date;
                $res_list = $m_usertaskrecord->getDataList('*',$where,'id desc');
                $date_clist = array();
                foreach ($res_list as $cv){
                    $date_clist[]=array('box_name'=>$cv['box_name'],'content'=>$m_hoteltask->getTaskinfo($cv));
                }
                $info = array('date_str'=>date("n月d日",strtotime($date)),'list'=>$date_clist);
                $datalist[]=$info;
            }
        }
        $data = array('total'=>$total,'datalist'=>$datalist);
        $this->to_back($data);
    }

    public function taskfinish(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];
        $page = $this->params['page']?$this->params['page']:1;
        $pagesize = 10;

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $m_usertask = new \Common\Model\Smallapp\UserTaskModel();
        $where = array('openid'=>$openid,'status'=>array('in',array(4,5)));
        $all_nums = $page * $pagesize;
        $res_usertask = $m_usertask->getDataList('*',$where,'id desc',0,$all_nums);

        $total = $res_usertask['total'];
        $datalist = array();
        if($res_usertask['total']>0){
            foreach ($res_usertask['list'] as $k=>$v){
                $datalist[]=array('id'=>$v['id'],'money'=>$v['money'],'date_str'=>date("Y年n月d日",strtotime($v['withdraw_time'])));
            }
        }
        $data = array('total'=>$total,'datalist'=>$datalist);
        $this->to_back($data);
    }

    public function rollexchangelist(){
        $hotel_id = intval($this->params['hotel_id']);

        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = C('SAPP_SALE').'exchangerecord';
        $res_cache = $redis->get($cache_key);
        $cache_record = array();
        if(!empty($res_cache)){
            $res_cache = json_decode($res_cache,true);
            shuffle($res_cache);
            $cache_record = array_slice($res_cache,0,50);
        }
        $m_usertaskrecord = new \Common\Model\Smallapp\UserTaskRecordModel();
        $fileds = 'a.openid,a.money,user.avatarUrl as avatar_url,user.nickName as name';
        $where = array('a.hotel_id'=>$hotel_id,'a.type'=>3);
        $res_hotelrecord = $m_usertaskrecord->getFinishRecordlist($fileds,$where,'a.id desc',0,50);
        $exchange_list = array();
        $total = 50;
        if(count($res_hotelrecord)<50){
            $hotel_num = $total - count($res_hotelrecord);
            $where = array('a.hotel_id'=>array('neq',$hotel_id),'a.type'=>1);
            $res_other_hotelrecord = $m_usertaskrecord->getFinishRecordlist($fileds,$where,'a.id desc',0,$hotel_num);
            $other_hotel_num = count($res_other_hotelrecord);
            $last_num = $hotel_num - $other_hotel_num;
            if($last_num>0){
                $cache_record = array_slice($cache_record,0,$last_num);
                $exchange_list = array_merge($res_hotelrecord,$res_other_hotelrecord,$cache_record);
            }else{
                $exchange_list = array_merge($res_hotelrecord,$res_other_hotelrecord);
            }
        }else{
            $exchange_list = $res_hotelrecord;
        }
        $datalist = array();
        foreach ($exchange_list as $v){
            $content = "{$v['name']}领取了{$v['money']}元现金红包";
            $info = array('avatar_url'=>$v['avatar_url'],'content'=>$content);
            $datalist[]=$info;
        }
        $this->to_back(array('datalist'=>$datalist));
    }



}