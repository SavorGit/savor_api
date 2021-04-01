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
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001);
                break;
            case 'taskclaim':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001);
                break;
            case 'taskfinish':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001,'page'=>1);
                break;


        }
        parent::_init_();
    }

    public function taskprocess(){
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

        $m_usertaskrecord = new \Common\Model\Smallapp\UserTaskRecordModel();
        $where = array('openid'=>$openid,'type'=>1);
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
                $ut_fields = 'sum(meal_num) as meal_num,sum(comment_num) as comment_num,sum(interact_num) as interact_num';
                $res_list = $m_usertaskrecord->getDataList($ut_fields,$where,'id desc');
                $content = $m_hoteltask->getTaskinfo($res_list[0]);
                $info = array('date_str'=>date("j月d日",strtotime($date)),'content'=>$content);
                $datalist[]=$info;
            }
        }
        $data = array('total'=>$total,'datalist'=>$datalist);
        $this->to_back($data);
    }

    public function taskclaim(){
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

        $m_usertaskrecord = new \Common\Model\Smallapp\UserTaskRecordModel();
        $where = array('openid'=>$openid,'type'=>2);
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
                $info = array('date_str'=>date("j月d日",strtotime($date)),'list'=>$date_clist);
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
                $datalist[]=array('id'=>$v['id'],'money'=>$v['money'],'date_str'=>date("Y年j月d日",strtotime($v['withdraw_time'])));
            }
        }
        $data = array('total'=>$total,'datalist'=>$datalist);
        $this->to_back($data);
    }



}