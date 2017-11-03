<?php
namespace Opclient11\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class TaskController extends BaseController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'pubTask':
                $this->is_verify = 1;
                $this->valid_fields = array('task_type'=>'1001','hotel_id'=>'1001','contractor'=>'1000',
                                            'mobile'=>'1000','addr'=>'1000','task_emerge'=>'1001',
                                            'tv_nums'=>'1000','repair_info'=>'1000',
                );
                break;
           
        }
        parent::_init_();
       
    }
    /**
     * @desc 发布任务
     */
    public function pubTask(){
        $task_type  = $this->params['task_type'];
        $hotel_id   = $this->params['hotel_id'];    //酒楼id
        $contractor = $this->params['contractor'];//联系人
        $mobile     = $this->params['mobile'];    //联系人电话
        $addr       = $this->params['addr'];    //酒楼地址
        $tv_nums     = $this->params['tv_nums'];  //版位数量
        
        $m_hotel = new \Common\Model\HotelModel();
        
        $hotel_info = $m_hotel->getOneById('id,area_id',$hotel_id);
        if(empty($hotel_info)){
            $this->error('16200');
        }
        $area_id = $hotel_info['area_id'];    //城市
        $publish_user_id = $this->params['publish_user_id'];  //发布者用户id
        $task_emerge     = $this->params['task_emerge'];  //任务紧急程度 
        
        $data = array();
        $data['task_area']       = $area_id;
        $data['publish_user_id'] = $publish_user_id;
        $data['task_emerge']     = $task_emerge;
        $data['task_type']       = $task_type;
        $data['hotel_id']        = $hotel_id;
        $data['hotel_address']   = $addr;
        $data['hotel_linkman']   = $contractor;
        $data['hotel_linkman_tel']= $mobile;
        $data['tv_nums']         = $tv_nums ;
     
        $m_option_task = new \Common\Model\OptiontaskModel();
        $m_option_task_repair = new \Common\Model\OptionTaskRepairModel();
        $task_id = $m_option_task->addData($data, $type=1); 
        
        if($task_type==7){//如果是维修
            $repair_info = $this->params['repair_info'];
            $repair_info = json_decode($repair_info,true);
            foreach($repair_info as $key=>$v){
                $map = array();
                $map['task_id'] = $task_id;
                $map['box_id'] = $v['box_id'];
                $map['fault_desc'] = $v['desc'];
                $map['fault_img_url'] = $v['fault_img_url'];
                $m_option_task_repair->addInfo($map);
            }
        }
        $this->to_back(10000);
    }
    /**
     * @desc 
     */
    public function aa(){
        
    }
}