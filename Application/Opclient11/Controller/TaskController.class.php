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
    private $production_mode;
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
                $this->valid_fields = array('user_id'=>'1001','city_id'=>'1001','state'=>'1000','page'=>'1000');
                break;
            case 'appointTaskList':
                $this->is_verify = 1;
                $this->valid_fields = array('user_id'=>'1001','city_id'=>'1001','state'=>'1000','page'=>'1000');
                break;
            case 'exeTaskList':
                $this->is_verify = 1;
                $this->valid_fields = array('user_id'=>'1001','city_id'=>'1001','state'=>'1000','page'=>'1000');
                break;
            case 'viewTaskList':
                $this->is_verify = 1;
                $this->valid_fields = array('user_id'=>'1001','city_id'=>'1001','state'=>'1000','page'=>'1000');
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
                $this->valid_fields = array('task_id'=>'10001','appoint_user_id'=>'1001','exe_user_id'=>'1001','appoint_exe_time'=>'1001','is_lead_install'=>'1000');    
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
            '1'=>'检测',
            '8'=>'网络',
            '2'=>'安装',
            '4'=>'维修',
        );
        $this->production_mode = C('UMENG_PRODUCTION_MODE');
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
        $desc = $this->params['desc']; //备注
        $m_opuser_role = new \Common\Model\OpuserRoleModel();
        $role_info = $m_opuser_role->getInfoByUserid('role_id', $publish_user_id);
        if(empty($role_info)){//未设置发布者账号
            $this->to_back(30057);
        }
        if($role_info['role_id'] !=1){ //不是对应的发布者角色
            $this->to_back(30058);
        }
        
        $task_type_arr =  C('OPTION_USER_SKILL_ARR');
        if(!key_exists($task_type, $task_type_arr)){
            $this->to_back('30065');
        }
        
        $m_hotel = new \Common\Model\HotelModel();
        
        $hotel_info = $m_hotel->getOneById('id,area_id',$hotel_id);
        if(empty($hotel_info)){
            $this->to_back('16200');
        }
        //判断该用户10秒之前是否发布过任务
        
        $m_option_task = new \Common\Model\OptiontaskModel();
        $p_info = $m_option_task->getTaskInfoByUserid('create_time',array('publish_user_id'),'id desc');
        if(!empty($p_info) && !empty($p_info['create_time'])){
           $diff_time = time() - strtotime($p_info['create_time']) ;
           if($diff_time<=10){
               $this->to_back(30076);
           }
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
        $data['desc']            = $desc;
       /*  if($task_type==4 || $task_type ==2){
            $mobile = trim($mobile);
            if(empty($mobile)){
                $this->to_back('30074');
            }else  if(!check_mobile($mobile)){
                $this->to_back('30075');
            }
        } */
        $m_option_task_repair = new \Common\Model\OptionTaskRepairModel();
        $task_id = $m_option_task->addData($data, $type=1); 
        
        if($task_type==4 || $task_type ==2){//如果是维修 或者是安装验收
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
                $fault_img_url_arr = array();
                $map = array();
                $map['task_id'] = $task_id;
                $map['box_id'] = $v['box_id'];
                $map['fault_desc'] = $v['fault_desc'];
                if(!empty($v['fault_img_url'])){
                    $fault_img_url_arr = parse_url($v['fault_img_url']);
                }
                $map['fault_img_url'] = $fault_img_url_arr['path'] ? $fault_img_url_arr['path']: '';
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
        $city_id = $this->params['city_id'];
        $page    = intval($page) ? intval($page) : 1; 
        
        $page_size = $this->pagesize;
        $offset = ($page-1)*$page_size;
        $limit  = "$offset,$page_size";
        
        $fields =  'a.id,a.task_type,a.state,replace(area.region_name,\'市\',\'\') as region_name,a.task_emerge,a.tv_nums,a.hotel_id,hotel.name hotel_name,a.create_time,a.publish_user_id,
                    a.hotel_address,user.remark as publish_user,a.appoint_time,a.appoint_user_id,appuser.remark as appoint_user,a.appoint_exe_time,
                    a.exe_user_id,a.complete_time,a.refuse_time
                    ';
        $where = ' 1';
        if($city_id !=9999){
            $where .= " and a.task_area =$city_id";
        }
        $where .= ' and  a.publish_user_id='.$user_id.' and a.flag=0';   //获取自己发布的任务  
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
        $data = $m_option_task->getMultList($fields, $where, $order, $limit);
        
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
            if(empty($v['refuse_time'])){
                unset($data[$key]['refuse_time']);
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
        $city_id = $this->params['city_id'];
        $page    = intval($page) ? intval($page) : 1;
    
        $page_size = $this->pagesize;
        $offset = ($page-1)*$page_size;
        $limit  = "$offset,$page_size";
    
        $fields =  'a.id,a.task_type,a.state,replace(area.region_name,\'市\',\'\') as region_name,a.task_emerge,a.tv_nums,a.hotel_id,hotel.name hotel_name,a.create_time,a.publish_user_id,
                    a.hotel_address,user.remark as publish_user,a.appoint_time,a.appoint_user_id,appuser.remark as appoint_user,a.appoint_exe_time,
                    a.exe_user_id,a.complete_time,a.refuse_time
                    ';
        
        $where = ' 1';
        if($city_id !=9999){
            $where .= " and a.task_area =$city_id";
        }
        $where .= " and  FIND_IN_SET($user_id,a.exe_user_id) and a.flag=0"; //获取指派给自己的任务
        //$where .= ' and a.exe_user_id='.$user_id.' and a.flag=0';   //获取指派给自己的任务
        $state = intval($state);
        if(!empty($state)){
            $where .= ' and a.state ='.$state;
        }
    
        if($state ==0 ){
            $order = ' a.create_time desc';
        }else if($state ==4){
            $order = ' a.complete_time desc';
        }else if($state ==1 || $state ==2){
            $order = ' a.task_emerge asc ,a.create_time asc';
        }
    
    
        $m_option_task = new \Common\Model\OptiontaskModel();
        $data = $m_option_task->getMultList($fields, $where, $order, $limit);
    
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
            if(empty($v['refuse_time'])){
                unset($data[$key]['refuse_time']);
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
        $city_id = $this->params['city_id'];
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
                    a.exe_user_id,a.complete_time,a.refuse_time
                    ';
        $where = ' 1';
        if($city_id !=9999){
            $where .= " and a.task_area =$city_id";
        }
        $where .= "  and a.flag=0";   //获取所有发布的任务
        $state = intval($state);
        if(!empty($state)){
            $where .= ' and a.state ='.$state;
        }
        if($state ==0 ){
            $order = ' a.create_time desc';
        }else if($state ==4){
            $order = ' a.complete_time desc';   
        }else if($state ==1 || $state ==2){
            $order = ' a.task_emerge asc ,a.create_time asc';
        }
        
        
        $m_option_task = new \Common\Model\OptiontaskModel();
        $data = $m_option_task->getMultList($fields, $where, $order, $limit);
        
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
            if(empty($v['refuse_time'])){
                unset($data[$key]['refuse_time']);
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
        $city_id = $this->params['city_id'];
        $page    = intval($page) ? intval($page) : 1;
    
        $page_size = $this->pagesize;
        $offset = ($page-1)*$page_size;
        $limit  = "$offset,$page_size";
    
        $fields =  'a.id,a.task_type,a.state,replace(area.region_name,\'市\',\'\') as region_name,a.task_emerge,a.tv_nums,a.hotel_id,hotel.name hotel_name,a.create_time,a.publish_user_id,
                    a.hotel_address,user.remark as publish_user,a.appoint_time,a.appoint_user_id,appuser.remark as appoint_user,a.appoint_exe_time,
                    a.exe_user_id,a.complete_time,a.refuse_time
                    ';
        $where = ' 1';
        if($city_id !=9999){
            $where .= " and a.task_area=$city_id ";
        }
        
        $where .= " and a.flag=0";   //获取所有任务
        $state = intval($state);
        if(!empty($state)){
            $where .= ' and a.state ='.$state;
        }
    
        if($state ==0){
            $order = ' a.create_time desc';
        }else if ($state==4){
            $order = ' a.complete_time desc';
        }
        else if($state ==1 || $state ==2){
            $order = ' a.task_emerge asc ,a.create_time asc';
        }
    
    
        $m_option_task = new \Common\Model\OptiontaskModel();
        $data = $m_option_task->getMultList($fields, $where, $order, $limit);
    
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
            if(empty($v['refuse_time'])){
                unset($data[$key]['refuse_time']);
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
                        a.is_lead_install,a.hotel_address,a.refuse_time,a.refuse_desc,user.remark as publish_user,a.appoint_time,a.appoint_user_id,appuser.remark as appoint_user,a.appoint_exe_time,
                        a.exe_user_id,a.complete_time,a.hotel_linkman,a.hotel_linkman_tel,a.hotel_id,a.desc';
            
            
            $where = array();
            $where['a.id'] = $task_id;
            $where['a.flag'] = 0;
            //$task_info = $m_option_task->getInfo($fields,$where);
            $task_info = $m_option_task->getMultInfo($fields,$where);

            if(empty($task_info)){
                $this->to_back(30059);
            }
            foreach($task_info as $key=>$v){
                if(empty($v)){
                    unset($task_info[$key]);
                }
            }
            $task_repair_img = C('TASK_REPAIR_IMG');
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
            if($task_info['appoint_exe_time']){
                $task_info['appoint_exe_time'] = substr($task_info['appoint_exe_time'], 0,10);
            }            
            if($task_type==4 ){//维修  安装与验收
                $hotel_standalone_config = C('HOTEL_STANDALONE_CONFIG');
                
                $m_option_task_repair = new \Common\Model\OptionTaskRepairModel();
                $fields = 'a.user_id,a.id as repair_id,box.name as box_name,a.box_id,a.fault_desc,a.repair_type,a.fault_img_url';
                $where = array();
                $where['a.task_id'] = $task_id;
                $repair_list = $m_option_task_repair->getList($fields,$where);
                if(!empty($repair_list)){
                    foreach($repair_list as $key=>$v){
                        if(!empty($v['fault_img_url'])){
                            $repair_list[$key]['fault_img_url'] = $task_repair_img.$v['fault_img_url'];
                        }
                        if(!empty($v['repair_type'])){
                            $repair_type_arr = explode(',', $v['repair_type']);
                            $repair_type_str  = '';
                            $space = '';
                            
                            foreach($repair_type_arr as $rvs){
                                $repair_type_str .= $space .$hotel_standalone_config[$rvs];
                                $space = ',';      
                                
                            }
                            $repair_list[$key]['fault_desc'] .= "(".$repair_type_str.")";
 
                        }
                        unset($repair_list[$key]['repair_type']);
                        $fieldd = ' suser.remark username,sbox.NAME box_name,
                    srepair.state,srepair.remark,srepair.repair_img,srepair.update_time repair_time';
                        
                        $result = $m_option_task_repair->getMultMissionRepairInfo($fieldd,array('srepair.id'=>$v['repair_id']),1);
                        $result = $result[0];
                        $repair_list[$key]['username'] = $result['username'] ? $result['username'] :$task_info['exeuser'];
                        $repair_list[$key]['state'] = $result['state'] ? $result['state'] :'';
                        $repair_list[$key]['remark'] = $result['remark'] ? $result['remark'] :'';
                        
                        if(!empty($result['repair_img'])){
                            $tmp_img  = json_decode($result['repair_img']);
                            $repair_img_arr = array();
                            foreach($tmp_img as $tk=>$tv) {
                                $repair_img_arr[$tk]['type'] =0;
                                if(!empty($tv)){
                                    $repair_img_arr[$tk]['img'] = $task_repair_img.$tv;
                                }else {
                                    $repair_img_arr[$tk]['img'] = '';
                                }
                            
                            }
                            $repair_list[$key]['repair_time'] = $result['repair_time'];
                            $repair_list[$key]['repair_img'] = $repair_img_arr;
                        }else {
                            $repair_list[$key]['repair_img'] = array();
                        }
                        
                        
                        //$rplist = $m_option_task_repair->getMissionRepairInfo($fielda, $map, $type);
                        
                    }
                    $task_info['repair_list'] = $repair_list;
                }
            }
            //获取执行者完成任务详情
            $rplist = array();
            if($mission_state == 4) {
                $m_option_task_repair = new \Common\Model\OptionTaskRepairModel();
                
                if($task_type == 2) {
                    $fielda = ' suser.remark username,srepair.repair_img,srepair.update_time repair_time,srepair.real_tv_nums';
                    $map['srepair.task_id'] = $task_id;
                    //1为机顶盒
                    $type = 1;
                    $rplist = $m_option_task_repair->getMultMissionRepairInfo($fielda, $map, $type);
                   
                    $result = $rplist[0];
                    $task_info['real_tv_nums'] = $result['real_tv_nums'];
                    $repair_img_arr = json_decode($result['repair_img'],true);
                    if(empty($repair_img_arr)){
                        $repair_img_arr = array();
                    }
                    $rets = array();
                    
                    foreach($repair_img_arr as $rk=>$rv){
                        $ttp = array();
                        $ttp[0]['type'] = 0;
                        if(!empty($rv['img'])){
                            $ttp[0]['img'] = $task_repair_img .$rv['img'];
                            
                        }else {
                            $ttp[0]['img'] = '';
                        }
                        
                        $rets[$rk]['repair_img'] = $ttp;
                        //unset($repair_img_arr[$rk]['img']);
                        $rets[$rk]['usernanme'] = $result['username'] ? $result['username'] : $task_info['exeuser'];
                        $rets[$rk]['repair_time']= $result['repair_time'];
                        
                    }
                    
                   $rplist = $rets;
                    
                    
                }else if($task_type == 1){
                    //echo 'welrjlwer';
                    $fielda = ' suser.remark username,sbox.NAME box_name,
                    srepair.state,srepair.remark,srepair.repair_img,srepair.update_time repair_time';
                    $map['srepair.task_id'] = $task_id;
                    //1为机顶盒
                    $type = 1;
                    $rplist = $m_option_task_repair->getMultMissionRepairInfo($fielda, $map, $type);
                    $result = $rplist[0];
                    $repair_img =json_decode($result['repair_img'],true);
                    
                    $repair_img_arr = array();
                    foreach($repair_img as $k=>$v){
                        $ttp = array();
                        $ttp[0]['type'] = 0;
                        if(!empty($v)){
                            $ttp[0]['img'] = $task_repair_img .$v;
                            
                        }else {
                            $ttp[0]['img'] = '';
                        }
                        $repair_img_arr[$k]['repair_img'] = $ttp;
                        unset($repair_img_arr[$k]['img']);
                        $repair_img_arr[$k]['username'] = $result['username']?$result['username']:$task_info['exeuser'];
                        $repair_img_arr[$k]['repair_time'] = $result['repair_time'];
                    }
                    
                    $rplist = $repair_img_arr;
                }else if($task_type == 8){
                    $fielda = ' suser.remark username,sbox.NAME box_name,
                    srepair.state,srepair.remark,srepair.repair_img,srepair.update_time repair_time';
                    $map['srepair.task_id'] = $task_id;
                    //1为机顶盒
                    $type = 1;
                    $rplist = $m_option_task_repair->getMultMissionRepairInfo($fielda, $map, $type);
                    $result = $rplist[0];
                    $repair_img =json_decode($result['repair_img'],true);
                    
                    $repair_img_arr = array();
                    
                    foreach($repair_img as $key=>$v){
                        if(!empty($v['img'])){
                            $repair_img[$key]['img'] = $task_repair_img.$v['img'];
                        }else {
                            $repair_img[$key]['img'] = '';
                        }
                        
                    }
                    $repair_img_arr[0]['username']    = $result['username']?$result['username']:$task_info['exeuser'];
                    $repair_img_arr[0]['repair_time'] = $result['repair_time'];
                    $repair_img_arr[0]['repair_img']  = $repair_img;
                    $rplist = $repair_img_arr;

                }
                if($task_type!=4){
                    $task_info['execute'] = $rplist;
                }
                
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
        $fields = 'a.state,a.appoint_user_id,a.publish_user_id,a.task_type,hotel.name hotel_name';
        $where = array();
        $where['a.id'] = $task_id;
        $where['a.flag'] = 0;
        $task_info = $m_option_task->getInfo($fields,$where);
        if(empty($task_info)){ 
            $this->to_back(30059);
        }
        if($task_info['state']!=1){
            $this->to_back(30062);
        }
        
        $map = $data = array();
        $map['id'] = $task_id;
        $data['appoint_user_id'] = $user_id;
        $data['refuse_time']  = date('Y-m-d H:i:s');
        $data['refuse_desc']  = $refuse_desc;
        $data['state']        =5;
        $ret = $m_option_task->updateInfo($map,$data);
        if($ret){
            $m_hotel_device_token = new \Common\Model\HotelDeviceTokenModel();
            $user_token = $m_hotel_device_token->getOnerow(array('user_id'=>$task_info['publish_user_id']));
            if(!empty($user_token) && !empty($user_token['device_token'])){//推送
                $display_type = 'notification';//通知
                $device_type  = $user_token['device_type'];
                $type = 'listcast' ;  //多列播
                $option_name  = 'optionclient';//运维端app
                $after_array = C('AFTER_APP');
                $after_open = $after_array[3];
            
                $device_tokens = $user_token['device_token'];
            
                $task_type_arr = C('OPTION_USER_SKILL_ARR');
                $m_sys_user= new \Common\Model\SysUserModel();
                $sys_user = $m_sys_user->getUserInfo(array('id'=>$user_id),'remark',1);
                /* $ticker = date('m-d',time())."日".date('H',time()).'点'.date('i',time()).'分,'.
                    $task_info['hotel_name'].'的'.$task_type_arr[$task_info['task_type']].'的任务已经被 '.
                    $sys_user['remark'].' 拒绝'; */
                $ticker = $task_info['hotel_name'].'的'.$task_type_arr[$task_info['task_type']].'的任务已经被 '.
                    $sys_user['remark'].' 拒绝';
                $title = '小热点运维端通知';
                $text  = $ticker;
                $production_mode = $this->production_mode;
                $alert['title'] = $ticker;
                $alert['subtitle'] = $title;
                $alert['body'] = $text;
                $custom = array();
                $extra =  array('type'=>2,'params'=>json_encode(array('task_id'=>"{$task_id}")));
                $this->pushData($display_type,$device_type,$type, $option_name, $after_open,
                    $device_tokens, $ticker, $title, $text,$production_mode,$custom,
                    $extra,$ticker);
            }
            
            $this->to_back(10000);
        }else {
            $this->to_back(30063);
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
        $fields = 'a.task_area,a.task_type';
        $where['a.id'] = $task_id;
        $task_info = $m_option_task->getInfo($fields, $where);
        if(empty($task_info)){
            $this->to_back(30059);
        }
        //print_r($task_info);exit;
        $area_id = $task_info['task_area'];
        $task_type = $task_info['task_type'];
        $m_option_task_role = new \Common\Model\OpuserRoleModel();
        
        $fields = 'a.user_id,user.remark as username';
        $where =" 1 and a.role_id = 3 and (FIND_IN_SET($area_id,a.`manage_city`) or a.manage_city=9999) and FIND_IN_SET($task_type,a.`skill_info`)  and a.state=1";
        if($is_lead_install){
            $where .= " and a.is_lead_install =".$is_lead_install;
        }
        
        $order = '';
        $user_list = array();
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
        $is_lead_install = $this->params['is_lead_install'] ? $this->params['is_lead_install']:0;  //是否带队安装
        $m_option_task = new \Common\Model\OptiontaskModel();
        
        $fields = ' a.id,a.state,a.publish_user_id,a.task_type,hotel.name hotel_name';
        $where['a.id'] = $task_id;
        $task_info = $m_option_task->getInfo($fields, $where);
        if(empty($task_info)){
            $this->to_back(30059);
        }
        if($task_info['state'] !=1){
            $this->to_back(30062);
        }
        $map = $data = array();
        $map['id'] = $task_id;
        $data['appoint_user_id'] = $appoin_user_id;
        $data['appoint_time']  = date('Y-m-d H:i:s');
        $data['appoint_exe_time']  = $appoint_exe_time;
        $data['exe_user_id']  = $exe_user_id;
        $data['state']        =2;
        $data['is_lead_install'] = $is_lead_install;
        $ret = $m_option_task->updateInfo($map,$data);
        if($ret){
            $m_hotel_device_token = new \Common\Model\HotelDeviceTokenModel();
            $user_token = $m_hotel_device_token->getOnerow(array('user_id'=>$task_info['publish_user_id']));
            if(!empty($user_token) && !empty($user_token['device_token'])){//推送
                $display_type = 'notification';//通知
                $device_type  = $user_token['device_type'];
                $type = 'listcast' ;  //多列播
                $option_name  = 'optionclient';//运维端app
                $after_array = C('AFTER_APP');
                $after_open = $after_array[3];
                
                $device_tokens = $user_token['device_token'];
                
                $task_type_arr = C('OPTION_USER_SKILL_ARR');
                $m_sys_user= new \Common\Model\SysUserModel();
                $sys_user = $m_sys_user->getUserInfo(array('id'=>$appoin_user_id),'remark',1);
                /* $ticker = date('m-d',time())."日".date('H',time()).'点'.date('i',time()).'分,'.
                          $task_info['hotel_name'].'的'.$task_type_arr[$task_info['task_type']].'的任务已经被 '.
                          $sys_user['remark'].' 指派'; */
                $ticker = $task_info['hotel_name'].'的'.$task_type_arr[$task_info['task_type']].'的任务已经被 '.
                          $sys_user['remark'].' 指派';
                $title = '小热点运维端通知';
                $text  = $ticker;
                $production_mode = $this->production_mode;
                $alert['title'] = $ticker;
                $alert['subtitle'] = $title;
                $alert['body'] = $text;
                $custom = array();
                $extra =  array('type'=>2,'params'=>json_encode(array('task_id'=>"{$task_id}")));
                $this->pushData($display_type,$device_type,$type, $option_name, $after_open,
                                $device_tokens, $ticker, $title, $text,$production_mode,$custom, 
                                $extra,$ticker);
            }
            $this->to_back(10000);
        }else {
            $this->to_back(30064);
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
        if(!in_array(9999, $manage_city)){
            if(!in_array($area_id, $manage_city)){
                $this->to_back(30060);
            } 
        }
        
        $where = array();
        
        if($area_id!=9999){
            $where['task_area'] = $area_id;
        }
        if($role_info['role_id'] ==2){//指派者
            $where['state']     = 1;
            $where['flag']      = 0;
                
        }
        if($role_info['role_id']==3){//执行者
            $where['_string']="FIND_IN_SET($user_id,exe_user_id)";
            //$where['exe_user_id'] = array('find_in_set',) $user_id;
            $where['state']       = 2;
            $where['flag']        = 0;
        }
        $m_option_task = new \Common\Model\OptiontaskModel();
        $nums = $m_option_task->countTaskNums($where);
        $data = array();
        $data['nums'] = $nums;
        $this->to_back($data);
    }
    public function testPush(){
        exit();
        $task_id = 1;
        $display_type = 'notification';//通知
        $device_type  = 4;
        $type = 'listcast' ;  //多列播
        $option_name  = 'optionclient';//运维端app
        $after_array = C('AFTER_APP');
        $after_open = $after_array[3];
        
        $device_tokens = 'c7fba38fa88d6d8d027e0f01e5069e3bbf1dd018c475b16358a5f0b6a655d50e';
        $task_info['hotel_name'] = '永峰测试';
        $task_type_arr = C('OPTION_USER_SKILL_ARR'); 
        $m_sys_user= new \Common\Model\SysUserModel();
        $sys_user = $m_sys_user->getUserInfo(array('id'=>145),'remark',1);
        /* $ticker = date('m-d',time())."日".date('H',time()).'点'.date('i',time()).'分,'.
            $task_info['hotel_name'].'的'.$task_type_arr[4].'任务已经被 '.
            $sys_user['remark'].' 指派'; */
        $ticker = $task_info['hotel_name'].'的'.$task_type_arr[4].'任务已经被 '.$sys_user['remark'].' 指派';
        
        $title = '小热点运维端通知';
        $text  = $ticker;
        $production_mode = $this->production_mode;
        $alert['title'] = $ticker;
        $alert['subtitle'] = $title;
        $alert['body'] = $text;
        //$alert = $alert;
        $custom = array();
        $extra = $ext_arr = array('type'=>2,'params'=>json_encode(array('task_id'=>"{$task_id}")));
        $this->pushData($display_type,$device_type,$type, $option_name, $after_open,
            $device_tokens, $ticker, $title, $text,$production_mode,$custom,
            $extra,$ticker);
    }
    public function testAdPush(){
        exit();
        $task_id = 1;
        $display_type = 'notification';//通知
        $device_type  = 3;
        $type = 'listcast' ;  //多列播
        $option_name  = 'optionclient';//运维端app
        $after_array = C('AFTER_APP');
        $after_open = $after_array[3];
        
        $device_tokens = 'Ak6nFuL7K3nu4AVVAHMLUEL9Yc5IOvcO2JhxQysyGXmn';
        $task_info['hotel_name'] = '永峰测试';
        $task_type_arr = C('OPTION_USER_SKILL_ARR');
        $m_sys_user= new \Common\Model\SysUserModel();
        $sys_user = $m_sys_user->getUserInfo(array('id'=>145),'remark',1);
        $ticker = $task_info['hotel_name'].'的'.$task_type_arr[4].'任务已经被 '.
            $sys_user['remark'].' 指派'; 
        //$ticker = 'fdd';
        $title = '小热点运维端通知';
        $text  = $ticker;
        $production_mode = $this->production_mode;
        $alert['title'] = $ticker;
        $alert['subtitle'] = $title;
        $alert['body'] = $text;
        //$alert = $alert;
        $custom = array();
        $extra = $ext_arr = array('type'=>2,'params'=>json_encode(array('task_id'=>"{$task_id}")));
        $this->pushData($display_type,$device_type,$type, $option_name, $after_open,
            $device_tokens, $ticker, $title, $text,$production_mode,$custom,
            $extra,$ticker);
    }
}