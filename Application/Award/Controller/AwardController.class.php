<?php
namespace Award\Controller;

use \Common\Controller\CommonController as CommonController;
class AwardController extends CommonController{
 	/**
     * 构造函数
     */
    function _init_() {

        
        switch(ACTION_NAME) {
            case 'getAwardInfo':
                $this->is_verify = 1;
                $this->valid_fields=array('mac'=>'1001','date'=>'1001');
                break;
            case 'recordAwardLog':
                $this->is_verify = 1;
                $this->valid_fields=array('boxid'=>'1001','prizeid'=>'1001','deviceid'=>1001,'time'=>'1001');
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取某个机顶盒的奖项设置
     */
    public function getAwardInfo(){
        $mac = $this->params['mac'];
        $date  = $this->params['date'];
    
        $m_box = new \Common\Model\BoxModel();
        $boxinfo = $m_box->getBoxInfoByMac($mac);
        if(empty($boxinfo)){
            $this->to_back('15003');
        }
        $boxid = $boxinfo['id'];
        $awardInfo = array();
        $m_box_award = new \Common\Model\BoxAwardModel();
        $awardInfo = $m_box_award->getAwardInfoByBoxid($boxid,$date);
        if(empty($awardInfo)){
            $this->to_back('15001');
        }else {
            $awardInfo['prize']= json_decode($awardInfo['prize'],true);
            $m_sys_config = new \Common\Model\SysConfigModel();
            $award_time = $m_sys_config->getInfo("'system_award_start_time','system_award_end_time'");
            
            $award_start_time = $award_time[1]['config_value'];
            $award_end_time   = $award_time[0]['config_value'];
            $awardInfo['start_time'] = $award_start_time;
            $awardInfo['end_time'] = $award_end_time;
            $this->to_back($awardInfo);
        }
        
    }
    /**
     * @desc 机顶盒中奖日志上报
     */
    public function recordAwardLog(){
        $this->to_back(10000);
        $boxid = $this->params['boxid'];        //机顶盒id
        $prizeid = $this->params['prizeid'];    //奖品id
        $deviceid = $this->params['deviceid'];  //中奖手机设备
        $time = $this->params['time'];          //中奖时间
        $data = array();
        $data['boxid']    = intval($boxid);
        $data['prizeid']  = intval($prizeid);
        $data['deviceid'] = $deviceid;
        $data['time']     = $time;
        $m_box_award = new \Common\Model\BoxAwardModel();
        $rt = $m_box_award->addInfo($data);
        if($rt){
            $this->to_back('10000');
        }else {
            $this->to_back('15002');
        }
    }
}