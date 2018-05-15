<?php
namespace Opclient11\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class MissionController extends BaseController{
    private $pagesize;
    private $task_state_arr ;
    private $task_emerge_arr;
    private $option_user_skill_arr;
    private $net_img_len = 2;
    private $production_mode;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getexecutorInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('task_id'=>'1001',
                    'user_id'=>'1001','task_type'=>'1001');
                break;
            case 'reportMission':
                $this->is_verify = 1;
                $this->valid_fields = array('task_id'=>'1001',
                    'user_id'=>'1001','task_type'=>'1001','repair_img'=>'1001');
                break;
                
        }
        parent::_init_();
        $this->production_mode = C('UMENG_PRODUCTION_MODE');
    }

    public function getexecutorInfo() {
        $save['task_id'] = $this->params['task_id'];  //任务id
        $save['task_type']  = $this->params['task_type'];//任务类型
        $save['user_id'] = $this->params['user_id'];//执行人id
        //判断是否有对任务执行权限,判断角色
        $type = 2;
        $task_info = $this->disposeTips($save, $type);
        //获取相关数据
        $m_option_task_repair = new
        \Common\Model\OptionTaskRepairModel();
        $where = array();
        $data['list'] = array();
        //安装验收
        if($save['task_type'] == 2) {
            $fields = 'a.repair_img';
            $where['a.task_id'] = $save['task_id'];
            $repair_list = $m_option_task_repair->getList($fields,$where);
            
            $repair_list = json_decode($repair_list[0]['repair_img'],true);
            $repair_img_arr =array();
            foreach($repair_list as $key=> $v){
                $repair_img_arr[$key]['repair_img'] = C('TASK_REPAIR_IMG').$v['img'];
            }
           
            $data['list'] = $repair_img_arr;
            
        }
        if($save['task_type'] == 1 || $save['task_type'] == 8) {
            $fields = 'a.repair_img,a.box_id,box.name box_name';
            $where['a.task_id'] = $save['task_id'];
            $repair_list = $m_option_task_repair->getList($fields,
                $where);
            $img_arr = array();
            if($repair_list) {
                $img_arr = json_decode($repair_list[0]['repair_img'], true);
                foreach($img_arr as $im=>$iv) {
                    $img_arr[$im]['repair_img'] = C('TASK_REPAIR_IMG').
$img_arr[$im]['repair_img'];
                }
                $data['list'] = $img_arr;

            }else{

            }

        } else if($save['task_type'] == 4){
            $fields = 'box_id,box.name box_name';
            $where['task_id'] = $save['task_id'];
            $where['a.state'] = 0;
            $repair_list = $m_option_task_repair->getList($fields,
                $where);

            if($repair_list) {
                $data['list'] = $repair_list;
            }
        }
        $this->to_back($data);
    }


    /**
     * @desc 处理账号信息
     */
    public function disposeTips($save, $type) {
        
        $m_opuser_role = new \Common\Model\OpuserRoleModel();
        $role_info = $m_opuser_role->getInfoByUserid('role_id',$save['user_id']);
        if($role_info['role_id'] !=3){
            $this->to_back(30058);
        }
        $field = 'state,tv_nums,task_type ';
        $where['flag'] = 0;
        $where['id'] = $save['task_id'];
        $where['_string'] = "FIND_IN_SET(".$save['user_id'].",exe_user_id)";
        //$where['exe_user_id'] = $save['user_id'];
        $m_option_task = new \Common\Model\OptiontaskModel();
        $user_task = $m_option_task->getTaskInfoByUserid($field, $where);
        if($user_task['task_type']!=$save['task_type']){
            $this->to_back('30113');
        }
        if(empty($user_task)) {
            $this->to_back(30100);
        }else{
            if($type == 2) {
                return $user_task;
            }
            if ( $user_task['state'] !=2 ) {
                
                if($user_task['state']==4){
                    $m_option_task_repair = new \Common\Model\OptionTaskRepairModel();
                    if($user_task['task_type']==4){
                        
                        $fields = 'suser.remark username';
                        $map = array();
                        $map['srepair.task_id'] = $save['task_id'];
                        $map['srepair.box_id'] = $this->params['box_id'];
                        $r_info = $m_option_task_repair->getMultMissionRepairInfo($fields,$map,1);
                        
                        $comp_username = $r_info[0]['username'];
                        
                        $this->to_back(30070,1,$comp_username);
                    }else {
                        $fields = 'suser.remark username';
                        $map = array();
                        $map['srepair.task_id'] = $save['task_id'];
                        
                        $r_info = $m_option_task_repair->getMultMissionRepairInfo($fields,$map,2);
                        $comp_username = $r_info[0]['username'];
                        
                        $this->to_back(30069,1,$comp_username);
                    }  
                }
                //该任务状态不对
                $this->to_back(30102);
            } else {
                $m_option_task_repair = new \Common\Model\OptionTaskRepairModel();
                
                if($save['task_type'] == 4) {
                    $box_id = $this->params['box_id'];
                    $task_id = $this->params['task_id'];
                    //$repair_img = $this->params['repair_img'];
                    $repair_img = $save['repair_img'];
                    $state =  empty($this->params['state']) ? 0 : $this->params['state'];

                    if(empty($box_id) || empty($state)) {
                        $this->to_back(30101);
                    }
                    //判断上传照片个数
                    $img_arr = explode(',', $repair_img);
                    if(count($img_arr)>3) {
                        $this->to_back(30103);
                    } 
                    
                    $fields = 'srepair.user_id,suser.remark username';
                    $map = array();
                    $map['srepair.task_id'] = $task_id;
                    $map['srepair.box_id'] = $box_id;
                    $r_info = $m_option_task_repair->getMultMissionRepairInfo($fields,$map,1);
                    if(!empty($r_info[0]['user_id'])){
                        $comp_username = $r_info[0]['username'];
                        
                        $this->to_back(30070,1,$comp_username);
                    }
                    
                    
                }
                return $user_task;
            }
        }
    }

    /**
     * @desc 处理维修信息
     */
    public function disposeRepair($save, $task_info) {
        $now_date = date("Y-m-d H:i:s");
        $save['remark'] = $this->params['remark'] ? $this->params['remark'] : '';
        $save['state'] = $this->params['state'];
        $save['update_time'] = $now_date;
        $where = array();
        $where['task_id'] = $this->params['task_id'];
        $where['box_id'] = $this->params['box_id'];
        $m_repair_task = new \Common\Model\OptionTaskRepairModel();
        
        $info_arr = $m_repair_task->getOneRecord('user_id',$where);
        if(!empty($info_arr['user_id'])){
            $this->to_back('30068');
        }
        $bool = $m_repair_task->saveData($save, $where);
        if($bool) {
            $where = array();
            $fields = 'id';
            $where['state'] = 0;
            $where['task_id'] = $this->params['task_id'];
            $repair_list = $m_repair_task->getRepairBoxInfo($fields,$where);
            //$repair_list = $m_repair_task->getList($fields,$where);
            if($repair_list) {
                $this->to_back(array('state'=>$save['state']));
            } else {
                $dat = array();
                //更新task表
                $dat['state'] = 4;
                $map['id'] = $this->params['task_id'];
                $dat['complete_time'] = $now_date;
                $m_option_task = new \Common\Model\OptiontaskModel();
                $m_option_task->saveData($dat, $map);
                
                
                
                $task_id = $this->params['task_id'];
                $fields = 'a.task_area,a.state,a.appoint_user_id,a.publish_user_id,a.task_type,
                           hotel.name hotel_name,a.hotel_linkman_tel,exeuser.remark,role.mobile,
                           user.remark as pub_user_name,puser.mobile pubmobile';
                $where = array();
                $where['a.id'] = $task_id;
                $where['a.flag'] = 0;
                $task_info = $m_option_task->getInfo($fields,$where);
                //增加发送酒楼联系人短信
                if(!empty($task_info['hotel_linkman_tel']) && check_mobile($task_info['hotel_linkman_tel'])){
                    $info = array();
                    $info['tel'] = $task_info['hotel_linkman_tel'];
                    if(empty($task_info['mobile'])){//如果酒楼维护人的手机号为空取该区域经理的联系方式
                        $maps = array();
                        $m_opuser_role = new \Common\Model\OpuserRoleModel();
                        $sql ="select a.mobile,b.remark from savor_opuser_role a 
                               left join savor_sysuser b on a.user_id = b.id
                               where find_in_set(".$task_info['task_area'].",a.manage_city) 
                               and a.is_leader=1 and a.state=1 and b.status=1 limit 1";
                        $leader_info = $m_opuser_role->query($sql);
                        if(!empty($leader_info) && !empty($leader_info[0]['mobile'])){
                            $param = $leader_info[0]['remark'] . $leader_info[0]['mobile'];
                            $this->sendToUcPa($info, $param,6);
                        }
                    }else {
                        $param = $task_info['remark'].$task_info['mobile'];
                        $this->sendToUcPa($info, $param,6);
                    }     
                }else if(!empty($task_info['pubmobile']) && check_mobile($task_info['pubmobile'])){
                    $info = array();
                    $info['tel'] = $task_info['pubmobile'];
                    $param = $task_info['remark'].$task_info['mobile'];
                    $this->sendToUcPa($info, $param,6);
                }
                //发送酒楼联系人短信结束
                
                //增加推送
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
                    $sys_user = $m_sys_user->getUserInfo(array('id'=>$this->params['user_id']),'remark',1);
                    /* $ticker = date('m-d',time())."日".date('H',time()).'点'.date('i',time()).'分,'.
                        $task_info['hotel_name'].'的'.$task_type_arr[$task_info['task_type']].'的任务已经被 '.
                        $sys_user['remark'].' 完成'; */
                    
                    $ticker = $task_info['hotel_name'].'的'.$task_type_arr[$task_info['task_type']].'的任务已经被 '.
                        $sys_user['remark'].' 完成';
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
                 
                //推送结束
                
                
            }
            $this->to_back(array('state'=>4));
        } else {
            $this->to_back(30106);
        }
    }


    /**
     * @desc 处理信息检测
     */
    public function disposeCheck($save, $task_info) {
        $now_date = date("Y-m-d H:i:s");
        $save['create_time'] = $now_date;
        $m_repair_task = new \Common\Model\OptionTaskRepairModel();
        $bool = $m_repair_task->addData($save);
        if($bool){
            //更新task表
            $dat['state'] = 4;
            $map['id'] = $this->params['task_id'];
            $dat['complete_time'] = $now_date;
            $m_option_task = new \Common\Model\OptiontaskModel();
            $m_option_task->saveData($dat, $map);
 
            //增加推送
            $task_id = $this->params['task_id'];
            $fields = 'a.state,a.appoint_user_id,a.publish_user_id,a.task_type,hotel.name hotel_name';
            $where = array();
            $where['a.id'] = $task_id;
            $where['a.flag'] = 0;
            $task_info = $m_option_task->getInfo($fields,$where);
            
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
                $sys_user = $m_sys_user->getUserInfo(array('id'=>$this->params['user_id']),'remark',1);
                
                $ticker =  $task_info['hotel_name'].'的'.$task_type_arr[$task_info['task_type']].'的任务已经被 '.
                    $sys_user['remark'].' 完成';
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
            //推送结束;
            
            $this->to_back(array('state'=>4));
        } else {
            $this->to_back(30106);
        }
    }


    /**
     * @desc 网络改造
     */
    public function disposeModify($save, $task_info) {
   
        $m_repair_task = new \Common\Model\OptionTaskRepairModel();
        $ret = $m_repair_task->addData($save);
        if($ret){
            $now_date = date("Y-m-d H:i:s");
            $dat['state'] = 4;
            $map['id'] = $save['task_id'];
            
            $dat['complete_time'] = $now_date;
            $m_option_task = new \Common\Model\OptiontaskModel();

            $m_option_task->saveData($dat, $map);
            
            //增加推送
            $task_id = $save['task_id'];
            $fields = 'a.state,a.appoint_user_id,a.publish_user_id,a.task_type,hotel.name hotel_name';
            $where = array();
            $where['a.id'] = $task_id;
            $where['a.flag'] = 0;
            $task_info = $m_option_task->getInfo($fields,$where);
            
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
                $sys_user = $m_sys_user->getUserInfo(array('id'=>$this->params['user_id']),'remark',1);
                
                $ticker = $task_info['hotel_name'].'的'.$task_type_arr[$task_info['task_type']].'的任务已经被 '.
                    $sys_user['remark'].' 完成';
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
           
            //推送结束
            
            
            $this->to_back(array('state'=>4));
        }
    }

    /**
     * @desc 安装验收
     */
    public function disposeInstall($save, $task_info) {
        $now_date = date("Y-m-d H:i:s");
        $save['create_time'] = $now_date;
        $field = 'a.id,a.tv_nums,a.state';
        $where = array();
        $where['a.id'] = $save['task_id'];
        $where['a.flag'] = 0;
        $m_option_task = new \Common\Model\OptiontaskModel();
        $task_info = $m_option_task->getInfo($field, $where);
        
        if($task_info){
            
            $m_option_task_repair = new \Common\Model\OptionTaskRepairModel();
            
            $repair_img = json_decode($save['repair_img'],true);
            $count = count($repair_img);
            if($count> $task_info['tv_nums']){
                $this->to_back('30066');
                
            }
            $data = array();
            $data['task_id'] = $save['task_id'];
            $data['repair_img'] = $save['repair_img'];
            $data['real_tv_nums'] = $this->params['real_tv_nums'];
            $data['user_id'] = $save['user_id'];
            $ret = $m_option_task_repair->add($data);
            if(!$ret){
                $this->to_back(30106);
            }
            $dat['state'] = 4;
            $map['id'] = $save['task_id'];
            $dat['complete_time'] = $now_date;
            
            $m_option_task = new \Common\Model\OptiontaskModel();
            $m_option_task->saveData($dat, $map);
            
            //增加推送
            $task_id = $save['task_id'];
            $fields = 'a.state,a.appoint_user_id,a.publish_user_id,a.task_type,hotel.name hotel_name';
            $where = array();
            $where['a.id'] = $task_id;
            $where['a.flag'] = 0;
            $task_info = $m_option_task->getInfo($fields,$where);
            
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
                $sys_user = $m_sys_user->getUserInfo(array('id'=>$this->params['user_id']),'remark',1);
                
                $ticker =  $task_info['hotel_name'].'的'.$task_type_arr[$task_info['task_type']].'的任务已经被 '.
                    $sys_user['remark'].' 完成';
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
             
            //推送结束
            
            
            
            $this->to_back(array('state'=>4));
            
            /* $repair_info = $m_option_task_repair->getOneRecord('id',array('task_id'=>$save['task_id']));
            if(empty($repair_info)){
                $data = array();
                $data['task_id'] = $save['task_id'];
                $data['repair_img'] = $save['repair_img'];
                $ret = $m_option_task_repair->add($data);
                
            }else {
                $where = array();
                $where['task_id'] = $save['task_id'];
                $data['repair_img'] = $save['repair_img'];
                $data['update_time'] = date('Y-m-d H:i;s');
                $ret = $m_option_task_repair->saveData($data, $where);
            }
            if(!$ret){
                $this->to_back(30106);
            }
            if($count == $task_info['tv_nums']){
                $dat['state'] = 4;
                $map['id'] = $save['task_id'];
                $dat['complete_time'] = $now_date;
                $m_option_task = new \Common\Model\OptiontaskModel();
                $m_option_task->saveData($dat, $map);
                
                $this->to_back(array('state'=>4));
            }else {
                
               $this->to_back(array('state'=>2));
            }  */ 
        }else {
            $this->to_back(30059);
            
        }
    }

    /**
     * @desc 执行者提交任务
     */
    public function reportMission(){

        $save['task_id'] = $this->params['task_id'];  //任务id
        $save['task_type']  = $this->params['task_type'];//任务类型
        $save['user_id'] = $this->params['user_id'];//执行人id
        $repair_img = empty($this->params['repair_img'])?'':$this->params['repair_img'];
        $save['repair_img'] = str_replace('\\', '', $repair_img);

        $save['repair_img'] = str_replace( C('TASK_REPAIR_IMG'),'', $save['repair_img']);
        //判断是否有对任务执行权限,判断角色
        $task_info = $this->disposeTips($save);

        //unset($save['user_id']);
        $task_type = $save['task_type'];
        unset($save['task_type']);
        switch($task_type){
            case '1':
                $this->disposeCheck($save, $task_info);
                break;
            case '8':
                $this->disposeModify($save, $task_info);
                break;
            case '2':
                $this->disposeInstall($save, $task_info);
                break;
            case '4':
                $this->disposeRepair($save, $task_info);
                break;
            default:
                $this->to_back('30065');
                break;
        }
        /*'3'=>'信息检测',
        '4'=>'网络改造',
        '6'=>'安装验收',
        '7'=>'维修',*/
        die;
    }
}