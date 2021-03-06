<?php
namespace Opclient20\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
class BoxMemController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'boxMemoryInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('box_id'=>1001);
                break;
                
        }
        parent::_init_();
    }
    public function boxMemoryInfo(){
        $box_id = $this->params['box_id'];
        /* $box_mac = $this->params['box_mac'];
        $box_mem_sta = empty($this->params['box_mem_state'])?1:$this->params['box_mem_state'];
        $box_memo = C('MEMORY_CONDITION');
        if(!array_key_exists($box_mem_sta, $box_memo)) {
            $this->to_back(30083);
        }
        if ( empty($box_mac) ) {
            $this->to_back(30081);
        } */
        //根据box_id或者mac获取酒楼相关信息
        $boxModel = new \Common\Model\BoxModel();
        
        $where = array();
        $where['id']   = $box_id;
        $where['flag'] = 0;
        $box_info = $boxModel->getOnerow($where);
        if (empty($box_info)) {
            $this->to_back(30082);
        }
        $where = array();
        $where['box_id'] = $box_id;
        $m_sdk_error = new \Common\Model\SdkErrorModel();
        $info = $m_sdk_error->getInfo('last_report_date',$where);
        //$nums = $m_sdk_error->countNums($where);
        if(empty($info)){
            $data = array();
            $data['box_id'] = $box_id;
            $data['erro_count'] =1;
            $data['last_report_date'] = date('Y-m-d H:i:s');
            $ret = $m_sdk_error->addInfo($data);
        }else {
            
            $last_report_date = strtotime($info['last_report_date']);//上次上报时间
            $sdk_error_report_time  = C('SDK_ERROR_REPORT_TIME');
            
            $last_ten_minutes = strtotime("-".$sdk_error_report_time." minutes"); //10分钟之前
            
            if($last_report_date<=$last_ten_minutes){//如果数据库上报时间是10分钟之前上报的 再次更新上报
                $where = array();
                $where['box_id'] = $box_id;
                $data = array();
                $sql ="update `savor_sdk_error` set `erro_count`=`erro_count`+1,last_report_date='".date('Y-m-d H:i:s')."' where box_id=".$box_id.' limit 1';
                $ret = $m_sdk_error->execute($sql);
            }else {
                $ret = false;
            }  
        }
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(30084);
        }   
    }
    public function boxMemoryInfo_20180509(){
        $this->to_back(10000);
        $box_id = $this->params['box_id'];
        $box_mac = $this->params['box_mac'];
        $box_mem_sta = empty($this->params['box_mem_state'])?1:$this->params['box_mem_state'];
        $box_memo = C('MEMORY_CONDITION');
        if(!array_key_exists($box_mem_sta, $box_memo)) {
            $this->to_back(30083);
        }
        if ( empty($box_mac) ) {
            $this->to_back(30081);
        }
        //根据box_id或者mac获取酒楼相关信息
        $boxModel = new \Common\Model\BoxModel();
        if(empty($box_id)) {
            $wherea = "1=1 and a.mac= '".$box_mac."'";
        } else {
            $wherea = "1=1 and a.id= '".$box_id."'";
        }
        if($box_mem_sta == 1) {
            $rp_type = 2;
        } else {
            $rp_type = 14;
        }
        //判断是否有
        $m_option_task_repair = new \Common\Model\OptionTaskRepairModel();
        $r_field = 'stas.id';
        $r_where = $wherea.' and srp.repair_type='.$rp_type.'
         and stas.flag=0 and stas.state
         in (1,2,3)';
         $m_rp = $m_option_task_repair->getOneMemInfo($r_field, $r_where);
        if($m_rp) {
            $this->to_back(30085);
        }


        $fields = 'd.id hotel_id,d.contractor,
        d.addr,d.tel,d.area_id,a.id box_id,d.name hotel_name';
        $box_info = $boxModel->getBoxInfo($fields, $wherea);
        if (empty($box_info)) {
            $this->to_back(30082);
        }
        $box_info = $box_info[0];

        $data = array();
        $data['task_area']       = empty($box_info['area_id'])?1:$box_info['area_id'];
        //获取发布者为系统的账号
        $m_opuser_role = new \Common\Model\OpuserRoleModel();
        $fields = 'a.user_id';
        $mop['a.state']   = 1;
        $mop['a.role_id']   = 1;
        $mop['user.remark']   = '系统';
        $mop['a.manage_city']   = 1;
        $user_info = $m_opuser_role->getList($fields,$mop,'' );

        $data['publish_user_id'] = $user_info[0]['user_id'];
        $data['palan_finish_time'] = date('Y-m-d H:i:s',time());
        $data['task_emerge']     = 2;
        $data['task_type']       = 4;
        $data['hotel_name']        = $box_info['hotel_name'];
        $data['hotel_id']        = $box_info['hotel_id'];
        $data['hotel_address']   = $box_info['addr'];
        $data['hotel_linkman']   = $box_info['contractor'];
        $data['hotel_linkman_tel']= $box_info['tel'];
        $data['tv_nums']         = 1 ;
        $m_option_task = new \Common\Model\OptiontaskModel();

        $task_id = $m_option_task->addData($data, $type=1);
        if($task_id) {
            $map = array();
            $map['task_id'] = $task_id;
            $map['state'] = 0;
            $map['repair_type'] = $rp_type;
            $map['box_id'] = $box_info['box_id'];
            $map['fault_desc'] = $box_memo[$box_mem_sta];
            $m_option_task_repair->addData($map);
        } else {
            $this->to_back(30084);
        }
        $this->to_back(10000);
    }


}