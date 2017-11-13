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
            $fields = 'a.repair_img,a.box_id,box.name box_name';
            $where['a.task_id'] = $save['task_id'];
            $repair_list = $m_option_task_repair->getList($fields,
                $where);

            if($repair_list) {
                foreach($repair_list as $rk=>$rk) {
                    $repair_list[$rk]['repair_img'] = C('TASK_REPAIR_IMG').
                        $repair_list[$rk]['repair_img'];
                }
                $data['list'] = $repair_list;

            }else{
                $data['list']  = array();
            }
        }
        if($save['task_type'] == 1 || $save['task_type'] == 8) {
            $fields = 'a.repair_img,a.box_id,box.name box_name';
            $where['a.task_id'] = $save['task_id'];
            $repair_list = $m_option_task_repair->getList($fields,
                $where);

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
        $field = 'state,tv_nums ';
        $where['flag'] = 0;
        $where['id'] = $save['task_id'];
        $where['exe_user_id'] = $save['user_id'];
        $m_option_task = new \Common\Model\OptiontaskModel();
        $user_task = $m_option_task->getTaskInfoByUserid($field, $where);
        //echo $m_option_task->getLastSql();
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
                $m_option_task_repair = new
                \Common\Model\OptionTaskRepairModel();
                $field = " repair_img ";
                $map['task_id'] = $save['task_id'];
                $res = $m_option_task_repair->getRepairBoxInfo($field, $map);



                if($save['task_type'] == 1) {
                    if($res) {
                        $this->to_back(30107);
                    }
                }
                if($save['task_type'] == 8) {
                    if($res) {
                        $img_arr = json_decode($res[0]['repair_img'], true);
                        if(count($img_arr) == $this->net_img_len) {
                            $this->to_back(30108);
                        }
                    }
                }
                if($save['task_type'] == 2) {
                    /*if($res) {
                        $img_arr = json_decode($res[0]['repair_img'], true);
                        if(count($img_arr) == $user_task['tv_nums']) {
                            $this->to_back(30109);
                        }
                    }*/
                }
                if($save['task_type'] == 4) {
                    $box_id = $this->params['box_id'];
                    $task_id = $this->params['task_id'];
                    $repair_img = $this->params['repair_img'];
                    $state =  empty($this->params['state'])
                        ?0:$this->params['state'];

                    if(empty($box_id) || empty($state)) {
                        $this->to_back(30101);
                    }
                    //判断上传照片个数
                    $img_arr = explode(',', $repair_img);
                    if(count($img_arr)>3) {
                        $this->to_back(30103);
                    }
                    //判断是否提交过维修记录
                    $m_option_task_repair = new
                    \Common\Model\OptionTaskRepairModel();
                    $fields = 'box_id';
                    $where = array();
                    $where['a.box_id'] = $box_id;
                    $where['a.task_id'] = $task_id;
                    $where['a.state'] = array('neq',0);
                    $repair_list = $m_option_task_repair->getList($fields,
                        $where);
                    if($repair_list){
                        $this->to_back(30104);
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
                $this->to_back(10000);
            } else {
                $dat = array();
                //更新task表
                $dat['state'] = 4;
                $map['id'] = $this->params['task_id'];
                $dat['complete_time'] = $now_date;
                $m_option_task = new \Common\Model\OptiontaskModel();
                $m_option_task->saveData($dat, $map);

            }
            $this->to_back(10000);
        } else {
            $this->to_back(10000);
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
            $this->to_back(10000);
        } else {
            $this->to_back(30106);
        }
    }


    /**
     * @desc 网络改造
     */
    public function disposeModify($save, $task_info) {
        $now_date = date("Y-m-d H:i:s");
        $save['create_time'] = $now_date;
        $field = 'repair_img,id';
        $where = array();
        $where['task_id'] = $save['task_id'];
        $m_repair_task = new \Common\Model\OptionTaskRepairModel();
        $ta_info = $m_repair_task->getOneRecord($field, $where);
        $task_update = false;
        $len = count(json_decode($save['repair_img'], true));
        if($ta_info) {
            $rid['id'] = $ta_info['id'];
            $info['repair_img'] = $save['repair_img'];
            $info['update_time'] = $now_date;
            $tmp_info = $m_repair_task->saveData($info, $rid);
            if($tmp_info){
                if($len == $this->net_img_len){
                    $task_update = true;
                }else{
                    $this->to_back(10000);
                }
            }else {
                $this->to_back(30106);
            }

        } else {
            $ta_info = $m_repair_task->addData($save);
            if($ta_info) {
                if($len == $this->net_img_len){
                    $task_update = true;
                }else{
                    $this->to_back(10000);
                }
            } else {
                $ta_info = $m_repair_task->addData($save);
                if($ta_info){
                    if($len == $this->net_img_len){
                        $task_update = true;
                    }else{
                        $this->to_back(10000);
                    }
                }else {
                    $this->to_back(30106);
                }

            }
        }

        if($task_update){

            $dat['state'] = 4;
            $map['id'] = $save['task_id'];
            $dat['update_time'] = $now_date;
            $m_option_task = new \Common\Model\OptiontaskModel();

            $m_option_task->saveData($dat, $map);
            $this->to_back(10000);
        }
    }

    /**
     * @desc 安装验收
     */
    public function disposeInstall($save, $task_info) {
        $now_date = date("Y-m-d H:i:s");
        $save['create_time'] = $now_date;
        $field = 'id,box_id';
        $where = array();
        $where['task_id'] = $save['task_id'];
        $m_repair_task = new \Common\Model\OptionTaskRepairModel();
        $ta_info = $m_repair_task->getRepairBoxInfo($field, $where);
        $repair_box_num = count($ta_info);
        $rp_img = json_decode($save['repair_img'], true);
        $len = count($rp_img);
        //$tv_nums = $task_info['tv_nums'];

        if($ta_info) {
            $bool = true;
            $m_repair_task->startTrans();
            foreach($ta_info as $tk=>$tv) {
                foreach($rp_img as $rk=>$rv) {
                    if($rv['box_id'] == $tv['box_id']) {
                        $rid['id'] = $tv['id'];
                        $info['repair_img'] = $rv['img'];
                        $info['update_time'] = $now_date;
                        $tmp_info = $m_repair_task->saveData($info, $rid);
                        if($tmp_info) {

                        } else {
                            $bool = false;
                            break;
                        }

                    }
                }
                if($bool == false) {
                    break;
                }
            }
            if($bool){
                if($len == $repair_box_num) {
                    $task_update = true;
                }else{
                    $m_repair_task->commit();
                    $this->to_back(10000);
                }
            }else {
                $m_repair_task->rollback();
                $this->to_back(30106);
            }

        }

        if($task_update){
            $dat['state'] = 4;
            $map['id'] = $save['task_id'];
            $dat['complete_time'] = $now_date;
            $m_option_task = new \Common\Model\OptiontaskModel();
            $m_option_task->saveData($dat, $map);
            $m_repair_task->commit();
            $this->to_back(10000);
        }

    }

    /**
     * @desc 执行者提交任务
     */
    public function reportMission(){

        error_log(($this->params['repair_img']),3,LOG_PATH.'baiyutao.log');
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
                echo 'kckdker';
                break;
        }
        /*'3'=>'信息检测',
        '4'=>'网络改造',
        '6'=>'安装验收',
        '7'=>'维修',*/
        die;
    }
}