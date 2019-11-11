<?php
namespace Box\Controller;
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
                $this->valid_fields = array('box_id'=>1001,'type'=>1001);
            break;
        }
        parent::_init_();
       
    }
    public function boxMemoryInfo(){
        $box_id = $this->params['box_id'];
        $type   = $this->params['type'];  //1内存卡损坏；2内存卡已满
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
            if($type==1){
                $data['erro_count'] =1;
            }else {
                $data['full_count'] =1;
            }
            
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
                if($type==1){
                    $sql ="update `savor_sdk_error` set `erro_count`=`erro_count`+1,last_report_date='".date('Y-m-d H:i:s')."' where box_id=".$box_id.' limit 1';
                }else {
                    $sql ="update `savor_sdk_error` set `full_count`=`full_count`+1,last_report_date='".date('Y-m-d H:i:s')."' where box_id=".$box_id.' limit 1';
                }
                
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
}