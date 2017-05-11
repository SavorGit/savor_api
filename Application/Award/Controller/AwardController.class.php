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
                $this->valid_fields=array('mac'=>'1001','prizeid'=>'1001','deviceid'=>'1001','time'=>'1001');
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
        $mac = $this->params['mac'];        //机顶盒mac
        $prizeid = $this->params['prizeid'];    //奖品id
        $deviceid = $this->params['deviceid'];  //中奖手机设备
        $time = $this->params['time'];          //中奖时间  时间戳 毫秒
        $time = floor ($time/1000);
        
        $time = date('Y-m-d H:i:s',$time);
        //echo $time;exit;
        $m_box = new \Common\Model\BoxModel();
        $boxinfo = $m_box->getBoxInfoByMac($mac); 
        if(empty($boxinfo)){
            $this->to_back('15003');
        }
        $m_box_award = new \Common\Model\BoxAwardModel();
        $date_time   = date('Y-m-d',strtotime($time)) ;
        $awardInfo = $m_box_award->getAwardInfoByBoxid($boxinfo['id'],$date_time);
        if(empty($awardInfo)){
            $this->to_back('15001');
        }
        $prize_current= json_decode($awardInfo['prize_current'],true);
        if(empty($prize_current)){
            $this->to_back('15005');
        }
        if($prizeid){
            foreach($prize_current as $v){
                if($v['prize_id'] == $prizeid){
                    $award_prize_info = $v;
                    break;
                }
            }
            if($award_prize_info['prize_num']<=0){
                $this->to_back('15004');
            }
        }
        $device_arr = C('CLIENT_NAME_ARR');
        $data = array();
        $data['mac']    = $mac;
        $data['prizeid']  = intval($prizeid);
        $data['deviceid'] = $deviceid;
        $data['time']     = $time;
        $http_traceinfo = $_SERVER['HTTP_TRACEINFO'];
        if(!empty($http_traceinfo)){
	        $http_traceinfo = explode(';', $http_traceinfo);
	        foreach ($http_traceinfo as $v){
	            $info = explode('=', $v);
	            $traceinfo[$info[0]] = $info[1];
	        }
	        
	    }
	    
        $data['device_type'] = $device_arr[$traceinfo['clientname']];
        $m_award_log = new \Common\Model\AwardLogModel();
        $rt = $m_award_log->addInfo($data);
        if($rt){
            if($prizeid){
                foreach($prize_current as $key=>$v){
                    if($v['prize_id'] == $prizeid){
                        $prize_current[$key]['prize_num'] = $v['prize_num'] - 1;
                    }
                }
                
                $up_prize_current = json_encode($prize_current);
                $map = array();
                $map['prize_current'] = $up_prize_current;
                $where = array();
                $where['boxid'] = $boxinfo['id'];
                $where['date_time'] = $date_time;
                
                $m_box_award->where($where)->save($map);
                
            }
            $this->to_back('10000');
        }else {
            $this->to_back('15002');
        }
    }
}