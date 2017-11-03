<?php
namespace Opclient11\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class TaskController extends BaseController{ 
    private $pagesize;
    private $task_state_arr ;
    private $task_emerge_arr;
    private $option_user_skill_arr;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'pubTask':
                $this->is_verify = 1;
                $this->valid_fields = array('publish_user_id'=>'1001','task_type'=>'1001','hotel_id'=>'1001','contractor'=>'1000',
                                            'mobile'=>'1000','addr'=>'1000','task_emerge'=>'1001',
                                            'tv_nums'=>'1000','repair_info'=>'1000',
                );
                break;
            case 'pubTaskList':
                $this->is_verify = 1;
                $this->valid_fields = array('user_id'=>'1001','state'=>'1000','page'=>'1000');
                break;
            case 'appointTaskList':
                $this->is_verify = 1;
                $this->valid_fields = array('user_id'=>'1001','state'=>'1000','page'=>'1000');
                break;
            case 'taskDetail':
                $this->is_verify = 1;
                $this->valid_fields = array('task_id'=>'1001');
                break;
                
        }
        parent::_init_();
        $this->pagesize = 15;
        $this->task_state_arr = C('TASK_STATE_ARR');
        $this->task_emerge_arr = C('TASK_EMERGE_ARR');
        $this->option_user_skill_arr = C('OPTION_USER_SKILL_ARR');
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
        $publish_user_id = $this->params['publish_user_id'];  //发布者用户id
        $m_opuser_role = new \Common\Model\OpuserRoleModel();
        $role_info = $m_opuser_role->getInfoByUserid('role_id', $publish_user_id);
        if(empty($role_info)){//未设置发布者账号
            $this->to_back(30057);
        }
        if($role_info['role_id'] !=1){ //不是对应的发布者角色
            $this->to_back(30058);
        }
        
        $m_hotel = new \Common\Model\HotelModel();
        
        $hotel_info = $m_hotel->getOneById('id,area_id',$hotel_id);
        if(empty($hotel_info)){
            $this->to_back('16200');
        }
        $area_id = $hotel_info['area_id'];    //城市
        
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
     * @desc 发布任务列表
     */
    public function pubTaskList(){
        $user_id = $this->params['user_id'];
        $state   = $this->params['state'];   //0  全部  1：贷指派  2：处理中  4 已完成
        $page    = $this->params['page'];
        $page    = intval($page) ? intval($page) : 1; 
        
        $page_size = $this->pagesize;
        $offset = ($page-1)*$page_size;
        $limit  = "$offset,$page_size";
        
        $fields =  'a.id,a.task_type,a.state,area.region_name,a.task_emerge,a.tv_nums,hotel.name hotel_name,a.create_time,a.publish_user_id,
                    user.remark as publish_user,a.appoint_time,a.appoint_user_id,appuser.remark as appoint_user,a.appoint_exe_time,
                    a.exe_user_id,exeuser.remark as exeuser,a.complete_time
                    ';
        
        $where = ' a.publish_user_id='.$user_id.' and a.flag=0';   //获取自己发布的任务  
        $state = intval($state);
        if($state ==0 || $state ==4){
            $order = ' a.create_time desc';
        }else if($state ==1 || $state ==2){
            $order = ' a.task_emerge asc ,a.create_time asc';   
        }
        
        
        $m_option_task = new \Common\Model\OptiontaskModel();
        $data = $m_option_task->getList($fields, $where, $order, $limit);
        
        foreach($data as $key=>$v){
            
            $data[$key]['state'] = $this->task_state_arr[$v['state']];
            $data[$key]['task_emerge'] = $this->task_state_arr[$v['task_emerge']];
            $data[$key]['task_type'] = $this->option_user_skill_arr[$v['task_type']];
            if(empty($v['appoint_user_id'])){
                unset($data[$key]['appoint_user_id']);
                unset($data[$key]['appoint_user']);
            }

            if(empty($v['exe_user_id'])){
                unset($data[$key]['exe_user_id']);
                unset($data[$key]['exeuser']);
            }
            if(empty($v['appoint_time'])){
                unset($data[$key]['appoint_time']);
            }
            if(empty($v['appoint_exe_time'])){
                unset($data[$key]['appoint_exe_time']);
            }
            if(empty($v['complete_time'])){
                unset($data[$key]['complete_time']);
            }
        }
        $this->to_back($data);
    }
    /**
     * @desc 指派任务列表
     */
    public function appointTaskList(){
        $user_id = $this->params['user_id'];
        $state   = $this->params['state'];   //0  全部  1：贷指派  2：处理中  4 已完成
        $page    = $this->params['page'];
        $page    = intval($page) ? intval($page) : 1;
        
        $m_opuser_role = new \Common\Model\OpuserRoleModel();
        $role_info = $m_opuser_role->getInfoByUserid('role_id',$user_id);
        
        if($role_info['role_id'] !=2){
            $this->to_back(30058);
        }
        
        $page_size = $this->pagesize;
        $offset = ($page-1)*$page_size;
        $limit  = "$offset,$page_size";
        
        $fields =  'a.id,a.task_type,a.state,area.region_name,a.task_emerge,a.tv_nums,hotel.name hotel_name,a.create_time,a.publish_user_id,
                    user.remark as publish_user,a.appoint_time,a.appoint_user_id,appuser.remark as appoint_user,a.appoint_exe_time,
                    a.exe_user_id,exeuser.remark as exeuser,a.complete_time
                    ';
        
        $where = ' a.flag=0';   //获取所有发布的任务
        $state = intval($state);
        if($state ==0 || $state ==4){
            $order = ' a.create_time desc';
        }else if($state ==1 || $state ==2){
            $order = ' a.task_emerge asc ,a.create_time asc';
        }
        
        
        $m_option_task = new \Common\Model\OptiontaskModel();
        $data = $m_option_task->getList($fields, $where, $order, $limit);
        
        foreach($data as $key=>$v){
        
            $data[$key]['state'] = $this->task_state_arr[$v['state']];
            $data[$key]['task_emerge'] = $this->task_state_arr[$v['task_emerge']];
            $data[$key]['task_type'] = $this->option_user_skill_arr[$v['task_type']];
            if(empty($v['appoint_user_id'])){
                unset($data[$key]['appoint_user_id']);
                unset($data[$key]['appoint_user']);
            }
        
            if(empty($v['exe_user_id'])){
                unset($data[$key]['exe_user_id']);
                unset($data[$key]['exeuser']);
            }
            if(empty($v['appoint_time'])){
                unset($data[$key]['appoint_time']);
            }
            if(empty($v['appoint_exe_time'])){
                unset($data[$key]['appoint_exe_time']);
            }
            if(empty($v['complete_time'])){
                unset($data[$key]['complete_time']);
            }
        }
        $this->to_back($data);
    }
    
    /**
     * @desc 任务详情
     */
    public function taskDetail(){
            $task_id = $this->params['task_id'];  //任务id
            $m_option_task = new \Common\Model\OptiontaskModel();
            $fields = 'a.create_time,hotel.name hotel_name,a.hotel_linkman,a.hotel_linkman_tel,a.hotel_address,
                       a.task_emerge,a.task_type,a.tv_nums';
            
            $where = array();
            $where['a.id'] = $task_id;
            $where['a.flag'] = 0;
            $task_info = $m_option_task->getInfo($fields,$where);
  
            if(empty($task_info)){
                $this->to_back(30059);
            }
            if($task_info['task_type']==7){//维修
                $m_option_task_repair = new \Common\Model\OptionTaskRepairModel();
                $fields = 'box.name as box_name,a.box_id,a.fault_desc,a.fault_img_url';
                $where = array();
                $where['a.task_id'] = $task_id;
                $repair_list = $m_option_task_repair->getList($fields,$where);
                if(!empty($repair_list)){
                    $task_info['repair_list'] = $repair_list;
                }
            }
            
            $task_info['task_emerge'] = $this->task_emerge_arr[$task_info['task_emerge']];
            $task_info['task_type']   = $this->option_user_skill_arr[$task_info['task_type']];
            
            
            $this->to_back($task_info);
    }
}