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
                $this->valid_fields=array('mac'=>'1001');
                break;
            case 'recordAwardLog':
                $this->is_verify = 1;
                $this->valid_fields=array('mac'=>'1001','prizeid'=>'1001','deviceid'=>1001,'time'=>'1001');
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取某个机顶盒的奖项设置
     */
    public function getAwardInfo(){
        $mac = $this->params['mac'];
        $date  = date('Y-m-d');
    
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
            $configs = $m_sys_config->getInfo("'system_award_time'");
            $award_time = json_decode($configs[0]['config_value'],true);
           
                    
            $awardInfo['award_time'] = $award_time;
            $this->to_back($awardInfo);
        }
        
    }
    /**
     * @desc 机顶盒中奖日志上报
     */
    public function recordAwardLog(){
        $this->to_back(10000);
        $mac = $this->params['mac'];        //机顶盒mac
        $prizeid = $this->params['prizeid'];    //奖品id
        $deviceid = $this->params['deviceid'];  //中奖手机设备
        $time = $this->params['time'];          //中奖时间
        $data = array();
        $data['mac']    = intval($mac);
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