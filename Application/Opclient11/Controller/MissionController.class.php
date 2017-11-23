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
        $where['exe_user_id'] = $save['user_id'];
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
            $this->to_back(array('state'=>4));
        } else {
            $this->to_back(30106);
        }
    }


    /**
     * @desc 网络改造
     */
    public function disposeModify($save, $task_info) {
        /* 
        $save['create_time'] = $now_date; */
        /* $field = 'repair_img,id';
        $where = array();
        $where['task_id'] = $save['task_id'];
         */
        $m_repair_task = new \Common\Model\OptionTaskRepairModel();
        $ret = $m_repair_task->addData($save);
        if($ret){
            $now_date = date("Y-m-d H:i:s");
            $dat['state'] = 4;
            $map['id'] = $save['task_id'];
            
            $dat['complete_time'] = $now_date;
            $m_option_task = new \Common\Model\OptiontaskModel();

            $m_option_task->saveData($dat, $map);
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
            $repair_info = $m_option_task_repair->getOneRecord('id',array('task_id'=>$save['task_id']));
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
            }  
        }else {
            $this->to_back(30059);
            
        }
    }

    /**
     * @desc 执行者提交任务
     */
    public function reportMission(){

        //error_log(($this->params['repair_img']),3,LOG_PATH.'baiyutao.log');
        $save['task_id'] = $this->params['task_id'];  //任务id
        $save['task_type']  = $this->params['task_type'];//任务类型
        $save['user_id'] = $this->params['user_id'];//执行人id
        $repair_img = empty($this->params['repair_img'])?'':$this->params['repair_img'];
        $save['repair_img'] = str_replace('\\', '', $repair_img);

    //   $save['repair_img'] = str_replace( C('TASK_REPAIR_IMG'),'', $this->params['repair_img']);
        $save['repair_img'] = str_replace( C('TASK_REPAIR_IMG'),'', $save['repair_img']);
        //判断是否有对任务执行权限,判断角色
        $task_info = $this->disposeTips($save);

        unset($save['user_id']);
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