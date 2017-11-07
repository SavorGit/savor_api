<?php
namespace Opclient11\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class TaskController extends BaseController{ 
    private $pagesize;
    private $task_state_arr ;
    private $task_emerge_arr;
    private $option_user_skill_arr;
    private $option_user_skill_bref_arr;
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
            case 'exeTaskList':
                $this->is_verify = 1;
                $this->valid_fields = array('user_id'=>'1001','state'=>'1000','page'=>'1000');
                break;
            case 'viewTaskList':
                $this->is_verify = 1;
                $this->valid_fields = array('user_id'=>'1001','state'=>'1000','page'=>'1000');
                break; 
            case 'taskDetail':
                $this->is_verify = 1;
                $this->valid_fields = array('task_id'=>'1001');
                break;
            case 'refuseTask':
                $this->is_verify = 1;
                $this->valid_fields = array('user_id'=>'1001','task_id'=>'10001','refuse_desc'=>'1000');
                break;
            case 'getExeUserList':
                $this->is_verify = 1;
                $this->valid_fields = array('task_id'=>'1001','exe_date'=>'1001','is_lead_install'=>'1000');
                break;
            case 'appointTask':
                $this->is_verify = 1;
                $this->valid_fields = array('task_id'=>'10001','appoint_user_id'=>'1001','exe_user_id'=>'1001','appoint_exe_time'=>'1001');    
                break;
            case 'countTaskNums';
                $this->is_verify = 1;
                $this->valid_fields = array('user_id'=>'1001','area_id'=>'1001');
                break;
                
        }
        parent::_init_();
        $this->pagesize = 15;
        $this->task_state_arr = C('TASK_STATE_ARR');
        $this->task_emerge_arr = C('TASK_EMERGE_ARR');
        $this->option_user_skill_arr = C('OPTION_USER_SKILL_ARR');
        $this->option_user_skill_bref_arr = array(
            '3'=>'检测',
            '4'=>'网络',
            '6'=>'安装',
            '7'=>'维修',
        );
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
        $data['palan_finish_time'] = date('Y-m-d H:i:s',time()+259200);
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
            /* $map['box_id'] = 1;
            $map['task_id'] =1;
            $map['fault_desc'] = 'fda';
            $map['fault_img_url'] = $repair_info;
            $m_option_task_repair->addData($map);
            $this->to_back(10000); */
            $repair_info = str_replace('\\', '', $repair_info);
            $repair_info = json_decode($repair_info,true);
            
            foreach($repair_info as $key=>$v){
                $map = array();
                $map['task_id'] = $task_id;
                $map['box_id'] = $v['box_id'];
                $map['fault_desc'] = $v['fault_desc'];
                $map['fault_img_url'] = $v['fault_img_url'];
                $m_option_task_repair->addData($map);
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
        
        $fields =  'a.id,a.task_type,a.state,replace(area.region_name,\'市\',\'\') as region_name,a.task_emerge,a.tv_nums,a.hotel_id,hotel.name hotel_name,a.create_time,a.publish_user_id,
                    a.hotel_address,user.remark as publish_user,a.appoint_time,a.appoint_user_id,appuser.remark as appoint_user,a.appoint_exe_time,
                    a.exe_user_id,exeuser.remark as exeuser,a.complete_time,a.refuse_time
                    ';
        
        $where = ' a.publish_user_id='.$user_id.' and a.flag=0';   //获取自己发布的任务  
        $state = intval($state);
        if(!empty($state)){
            $where .= ' and a.state ='.$state;
        }
        
        if($state ==0 || $state ==4){
            $order = ' a.create_time desc';
        }else if($state ==1 || $state ==2){
            $order = ' a.task_emerge asc ,a.create_time asc';   
        }
        
        
        $m_option_task = new \Common\Model\OptiontaskModel();
        $data = $m_option_task->getList($fields, $where, $order, $limit);
        
        foreach($data as $key=>$v){
            
            $data[$key]['state_id'] = $v['state'];
            $data[$key]['state'] = $this->task_state_arr[$v['state']];
            $data[$key]['task_emerge_id'] = $v['task_emerge'];
            $data[$key]['task_emerge'] = $this->task_emerge_arr[$v['task_emerge']];
            $data[$key]['task_type_id'] = $v['task_type'];
            $data[$key]['task_type'] = $this->option_user_skill_arr[$v['task_type']];
            if(!empty($v['appoint_exe_time'])){
                $data[$key]['appoint_exe_time'] = substr($v['appoint_exe_time'], 0,10);
            }
            
            $data[$key]['task_type_desc'] = $this->option_user_skill_bref_arr[$data[$key]['task_type_id']];
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
            //
        }
        $this->to_back($data);
    }
    /**
     * @desc 执行者任务列表
     */
    public function exeTaskList(){
        $user_id = $this->params['user_id'];
        $state   = $this->params['state'];   //0  全部    2：处理中  4 已完成
        $page    = $this->params['page'];
        $page    = intval($page) ? intval($page) : 1;
    
        $page_size = $this->pagesize;
        $offset = ($page-1)*$page_size;
        $limit  = "$offset,$page_size";
    
        $fields =  'a.id,a.task_type,a.state,replace(area.region_name,\'市\',\'\') as region_name,a.task_emerge,a.tv_nums,a.hotel_id,hotel.name hotel_name,a.create_time,a.publish_user_id,
                    a.hotel_address,user.remark as publish_user,a.appoint_time,a.appoint_user_id,appuser.remark as appoint_user,a.appoint_exe_time,
                    a.exe_user_id,exeuser.remark as exeuser,a.complete_time,a.refuse_time
                    ';
    
        $where = ' a.exe_user_id='.$user_id.' and a.flag=0';   //获取指派给自己的任务
        $state = intval($state);
        if(!empty($state)){
            $where .= ' and a.state ='.$state;
        }
    
        if($state ==0 || $state ==4){
            $order = ' a.create_time desc';
        }else if($state ==1 || $state ==2){
            $order = ' a.task_emerge asc ,a.create_time asc';
        }
    
    
        $m_option_task = new \Common\Model\OptiontaskModel();
        $data = $m_option_task->getList($fields, $where, $order, $limit);
    
        foreach($data as $key=>$v){
    
            $data[$key]['state_id'] = $v['state'];
            $data[$key]['state'] = $this->task_state_arr[$v['state']];
            $data[$key]['task_emerge_id'] = $v['task_emerge'];
            $data[$key]['task_emerge'] = $this->task_emerge_arr[$v['task_emerge']];
            $data[$key]['task_type_id'] = $v['task_type'];
            $data[$key]['task_type'] = $this->option_user_skill_arr[$v['task_type']];
            if(!empty($v['appoint_exe_time'])){
                $data[$key]['appoint_exe_time'] = substr($v['appoint_exe_time'], 0,10);
            }
    
            $data[$key]['task_type_desc'] = $this->option_user_skill_bref_arr[$data[$key]['task_type_id']];
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
            //
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
        
        $fields =  'a.id,a.task_type,a.state,replace(area.region_name,\'市\',\'\') as region_name,a.task_emerge,a.tv_nums,a.hotel_id,hotel.name hotel_name,a.create_time,a.publish_user_id,
                    a.hotel_address,user.remark as publish_user,a.appoint_time,a.appoint_user_id,appuser.remark as appoint_user,a.appoint_exe_time,
                    a.exe_user_id,exeuser.remark as exeuser,a.complete_time,a.refuse_time
                    ';
        
        $where = ' a.flag=0';   //获取所有发布的任务
        $state = intval($state);
        if(!empty($state)){
            $where .= ' and a.state ='.$state;
        }
        if($state ==0 || $state ==4){
            $order = ' a.create_time desc';
        }else if($state ==1 || $state ==2){
            $order = ' a.task_emerge asc ,a.create_time asc';
        }
        
        
        $m_option_task = new \Common\Model\OptiontaskModel();
        $data = $m_option_task->getList($fields, $where, $order, $limit);
        
        foreach($data as $key=>$v){
            $data[$key]['state_id'] = $v['state'];
            $data[$key]['state'] = $this->task_state_arr[$v['state']];
            $data[$key]['task_emerge_id'] = $v['task_emerge'];
            $data[$key]['task_emerge'] = $this->task_emerge_arr[$v['task_emerge']];
            $data[$key]['task_type_id'] = $v['task_type'];
            $data[$key]['task_type'] = $this->option_user_skill_arr[$v['task_type']];
            $data[$key]['task_type_desc'] = $this->option_user_skill_bref_arr[$data[$key]['task_type_id']];
            if(!empty($v['appoint_exe_time'])){
                $data[$key]['appoint_exe_time'] = substr($v['appoint_exe_time'], 0,10);
            }
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
     * @desc 查看者任务列表 
     */
    public function viewTaskList(){
        $user_id = $this->params['user_id'];
        $state   = $this->params['state'];   //0  全部    2：处理中  4 已完成
        $page    = $this->params['page'];
        $page    = intval($page) ? intval($page) : 1;
    
        $page_size = $this->pagesize;
        $offset = ($page-1)*$page_size;
        $limit  = "$offset,$page_size";
    
        $fields =  'a.id,a.task_type,a.state,replace(area.region_name,\'市\',\'\') as region_name,a.task_emerge,a.tv_nums,a.hotel_id,hotel.name hotel_name,a.create_time,a.publish_user_id,
                    a.hotel_address,user.remark as publish_user,a.appoint_time,a.appoint_user_id,appuser.remark as appoint_user,a.appoint_exe_time,
                    a.exe_user_id,exeuser.remark as exeuser,a.complete_time,a.refuse_time
                    ';
    
        $where = '1 and a.flag=0';   //获取所有任务
        $state = intval($state);
        if(!empty($state)){
            $where .= ' and a.state ='.$state;
        }
    
        if($state ==0 || $state ==4){
            $order = ' a.create_time desc';
        }else if($state ==1 || $state ==2){
            $order = ' a.task_emerge asc ,a.create_time asc';
        }
    
    
        $m_option_task = new \Common\Model\OptiontaskModel();
        $data = $m_option_task->getList($fields, $where, $order, $limit);
    
        foreach($data as $key=>$v){
    
            $data[$key]['state_id'] = $v['state'];
            $data[$key]['state'] = $this->task_state_arr[$v['state']];
            $data[$key]['task_emerge_id'] = $v['task_emerge'];
            $data[$key]['task_emerge'] = $this->task_emerge_arr[$v['task_emerge']];
            $data[$key]['task_type_id'] = $v['task_type'];
            $data[$key]['task_type'] = $this->option_user_skill_arr[$v['task_type']];
            if(!empty($v['appoint_exe_time'])){
                $data[$key]['appoint_exe_time'] = substr($v['appoint_exe_time'], 0,10);
            }
    
            $data[$key]['task_type_desc'] = $this->option_user_skill_bref_arr[$data[$key]['task_type_id']];
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
            //
        }
        $this->to_back($data);
    }
    
    /**
     * @desc 任务详情
     */
    public function taskDetail(){
            $task_id = $this->params['task_id'];  //任务id
            $m_option_task = new \Common\Model\OptiontaskModel();
            
           
            
            
            $fields = ' a.id,a.task_type,a.state,replace(area.region_name,\'市\',\'\') as region_name,a.task_emerge,a.tv_nums,a.hotel_id,hotel.name hotel_name,a.create_time,a.publish_user_id,
                        a.hotel_address,user.remark as publish_user,a.appoint_time,a.appoint_user_id,appuser.remark as appoint_user,a.appoint_exe_time,
                        a.exe_user_id,exeuser.remark as exeuser,a.complete_time,a.hotel_linkman,a.hotel_linkman_tel,a.hotel_id';
            
            
            $where = array();
            $where['a.id'] = $task_id;
            $where['a.flag'] = 0;
            $task_info = $m_option_task->getInfo($fields,$where);


            if(empty($task_info)){
                $this->to_back(30059);
            }
            foreach($task_info as $key=>$v){
                if(empty($v)){
                    unset($task_info[$key]);
                }
            }
            
            $mission_state = $task_info['state'];
            $task_type = $task_info['task_type'];
            
            $task_info['task_emerge_id'] = $task_info['task_emerge'];
            $task_info['task_emerge'] = $this->task_emerge_arr[$task_info['task_emerge']];
            $task_info['task_type_id'] = $task_info['task_type'];
            $task_info['task_type_desc'] = $this->option_user_skill_bref_arr[$task_info['task_type']];
            $task_info['task_type']   = $this->option_user_skill_arr[$task_info['task_type']];
            $task_info['region_name'] = str_replace('市', '', $task_info['region_name']);
            $task_info['state_id'] = $task_info['state'];
            $task_info['state'] = $this->task_state_arr[$task_info['state']];
            if($task_type==7){//维修
                $m_option_task_repair = new \Common\Model\OptionTaskRepairModel();
                $fields = 'box.name as box_name,a.box_id,a.fault_desc,a.fault_img_url';
                $where = array();
                $where['a.task_id'] = $task_id;
                $repair_list = $m_option_task_repair->getList($fields,$where);
                if(!empty($repair_list)){
                    $task_info['repair_list'] = $repair_list;
                }
            }
            //获取任务状
            if($mission_state == 4) {
                $m_option_task_repair = new \Common\Model\OptionTaskRepairModel();
                if($task_type == 7) {
                    $fielda = ' suser.remark username,sbox.NAME box_name,
                    srepair.state,srepair.remark,srepair.repair_img,srepair.repair_time';
                    $map['srepair.task_id'] = $task_id;
                    //1为机顶盒
                    $type = 1;
                   $rplist = $m_option_task_repair->getMissionRepairInfo
                   ($fielda, $map, $type);
                    foreach($rplist as $rk=>$rv) {
                        $rplist[$rk]['repair_img'] = json_decode
                        ($rv['repair_img']);
                    }
                } else if($task_type == 3 || $task_type == 6){
                    $fielda = ' suser.remark username,
                    srepair.state,srepair.remark,srepair.repair_img';
                    $map['srepair.task_id'] = $task_id;
                    //1为机顶盒
                    $type = 2;
                    $rplist = $m_option_task_repair->getMissionRepairInfo
                    ($fielda, $map, $type);
                    foreach($rplist as $rk=>$rv) {
                        $rplist[$rk]['repair_img'] = json_decode
                        ($rv['repair_img']);
                    }
                }else if($task_type == 4){
                    $fielda = ' suser.remark username,
                    srepair.state,srepair.remark,srepair.repair_img';
                    $map['srepair.task_id'] = $task_id;
                    //1为机顶盒
                    $type = 2;
                    $rplist = $m_option_task_repair->getMissionRepairInfo
                    ($fielda, $map, $type);
                    foreach($rplist as $rk=>$rv) {
                        $rplist[$rk]['repair_img'] = json_decode
                        ($rv['repair_img']);
                    }
                }
                $task_info['execute'] = $rplist;
            }
            $this->to_back($task_info);
    }
    /**
     * @desc 拒绝任务
     */
    public function refuseTask(){
        $user_id     = $this->params['user_id'];    //拒绝人id
        $task_id     = $this->params['task_id'];    //任务id
        $refuse_desc = $this->params['refuse_desc'];  //拒绝描述
        $m_option_task = new \Common\Model\OptiontaskModel();
        $fields = 'a.state,a.appoint_user_id';
        $where = array();
        $where['a.id'] = $task_id;
        $where['a.flag'] = 0;
        $task_info = $m_option_task->getInfo($fields,$where);
        if(empty($task_info)){ 
            $this->to_back(30059);
        }
        if($task_info['state']!=1){
            $this->to_back(30060);
        }
        
        $map = $data = array();
        $map['id'] = $task_id;
        $data['appoint_user_id'] = $user_id;
        $data['refuse_time']  = date('Y-m-d H:i:s');
        $data['refuse_desc']  = $refuse_desc;
        $data['state']        =5;
        $ret = $m_option_task->updateInfo($map,$data);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(30061);
        }
    }
    /**
     * @desc 获取任务执行人员列表
     */
    public function getExeUserList(){
        $task_id = $this->params['task_id']; //任务id
        $exe_date = $this->params['exe_date']; //执行日期
        $is_lead_install = $this->params['is_lead_install']; //是否带队安装
        //获取当前城市的执行者列表
        $m_option_task = new \Common\Model\OptiontaskModel();
        $fields = 'a.task_area';
        $where['a.id'] = $task_id;
        $task_info = $m_option_task->getInfo($fields, $where);
        //print_r($task_info);exit;
        $area_id = $task_info['task_area'];
        $m_option_task_role = new \Common\Model\OpuserRoleModel();
        
        $fields = 'a.user_id,user.remark as username';
        $where =" 1 and a.role_id = 3 and (FIND_IN_SET($area_id,a.`manage_city`) or a.manage_city=9999) and a.state=1";
        if($is_lead_install){
            $where .= " and a.is_lead_install =".$is_lead_install;
        }
        
        $order = '';
        $user_list = $m_option_task_role->getList($fields,$where,$order);
        //print_r($user_list);exit;
        $fields = 'a.task_type,hotel.name hotel_name';
        foreach($user_list as $key=>$v){
            //获取当前用户指定日期指派的任务
            
            $where = array();
            $where['a.exe_user_id'] = $v['user_id'];
            $where['a.appoint_exe_time'] = $exe_date.' 00:00:00';
      
            $task_info = $m_option_task->getdatas($fields, $where);
            if(!empty($task_info)){
                //$task_info['task_type_desc'] = $this->option_user_skill_arr[$task_info['task_type']];
                foreach($task_info as $kk=>$vv){
                    $task_info[$kk]['task_type_desc'] = $this->option_user_skill_arr[$vv['task_type']];
                }
                $user_list[$key]['task_info'] = $task_info;
            }
            
        }
        $this->to_back($user_list);
    }
    /**
     * @desc 指派任务
     */
    public function appointTask(){
        $task_id = $this->params['task_id'];          //任务id
        $appoin_user_id = $this->params['appoint_user_id'];  //指派人id
        $exe_user_id = $this->params['exe_user_id'];  //执行人userid
        $appoint_exe_time = $this->params['appoint_exe_time'];  //指派执行时间
        $m_option_task = new \Common\Model\OptiontaskModel();
        
        $fields = ' a.id,a.state';
        $where['a.id'] = $task_id;
        $task_info = $m_option_task->getInfo($fields, $where);
        if(empty($task_info)){
            $this->to_back(30059);
        }
        if($task_info['state'] !=1){
            $this->to_back(30060);
        }
        $map = $data = array();
        $map['id'] = $task_id;
        $data['appoint_user_id'] = $appoin_user_id;
        $data['appoint_time']  = date('Y-m-d H:i:s');
        $data['appoint_exe_time']  = $appoint_exe_time;
        $data['exe_user_id']  = $exe_user_id;
        $data['state']        =2;
        $ret = $m_option_task->updateInfo($map,$data);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(30061);
        }
    }
    /**
     * @desc 获取当前用户得任务数量
     */
    public function countTaskNums(){
        $user_id = $this->params['user_id'];
        $area_id = $this->params['area_id'];
        
        $m_opuser_role = new \Common\Model\OpuserRoleModel();
        $role_info = $m_opuser_role->getInfoByUserid('role_id,manage_city', $user_id);
        if(empty($role_info)){
            $this->to_back(30057);
        }
        $task_nums_role_arr = array('2','3');
        if(!in_array($role_info['role_id'], $task_nums_role_arr)){
            $this->to_back(30058);
        }
        $manage_city = explode(',', $role_info['manage_city']);
        if(!in_array($area_id, $manage_city)){
            $this->to_back(30060);
        }
        $where = array();
        if($role_info['role_id'] ==2){//指派者

            $where['task_area'] = $area_id;
            $where['state']     = 1;
            $where['flag']      = 0;
                
        }
        if($role_info['role_id']==3){//执行者
            $where['exe_user_id'] = $user_id;
            $where['state']       = 2;
            $where['flag']        = 0;
        }
        $m_option_task = new \Common\Model\OptiontaskModel();
        $nums = $m_option_task->countTaskNums($where);
        $data = array();
        $data['nums'] = $nums;
        $this->to_back($data);
    }
}