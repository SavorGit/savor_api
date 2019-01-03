<?php
namespace Heartbeat\Controller;
use Common\Lib\SavorRedis;
use Think\Controller;
//use Common\Lib\Curl;
use \Common\Controller\CommonController as CommonController;
/**
 * @desc 心跳上报
 */
class ReportController extends CommonController{
    private $countHeartlogPre ;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'index':
                $this->is_verify = 0;
                break;
            case 'syncData':
                $this->is_verify = 0;
                break;
            case 'countHeartLog':
                $this->is_verify = 0;
                break;
                
        }
        $this->countHeartlogPre = 'heartlog_';
        parent::_init_();
    }
    
    public function index(){
        
        //https://mb.rerdian.com/survival/api/2/survival
        //?hotelId=10000000&period=454&mac=111&demand=555&apk=666&war=888&logo=ppp&ip=89.3143.1
        $data = array();
        $data['clientid'] = I('get.clientid','0','intval');     //上报客户端类型 1:小平台 2:机顶盒
        $data['hotelId']  = I('get.hotelId','0','intval');
        $data['period']   = I('get.period','','trim');
        $data['mac']      = I('get.mac','','trim');
        $data['demand']   = I('get.demand','','trim');
        $data['apk']      = I('get.apk','','trim');
        $data['war']      = I('get.war','','trim');
        $data['logo']     = I('get.logo','','trim');
        $data['intranet_ip'] = I('get.ip','','trim');  //内网ip
        $data['outside_ip']  = get_client_ipaddr();    //外网ip
        $data['teamviewer_id'] = I('get.teamviewer_id'); //远程id
        
        //20180115 新增
        $data['pro_period'] = I('get.pro_period','','trim');  //当前节目号
        $data['adv_period'] = I('get.adv_period','','trim');  //当前宣传片期号
        $data['pro_download_period'] = I('get.pro_download_period','','trim'); //下载节目期号
        $data['ads_download_period'] = I('get.ads_download_period','','trim');  //下载广告期号
        
        //20180531新增
        $data['net_speed']  = I('get.net_speed','','trim');   //机顶盒下载速度
                
        if(empty($data['mac'])){
            $this->to_back(10004);
        }
        if(!in_array($data['clientid'],array(1,2))){
            $this->to_back(10005);
        }
        if(!preg_match('/[0-9A-F]{12}/', $data['mac'])){
            $this->to_back(10006);
        }
        if(!is_numeric($data['hotelId']) || $data['hotelId']<1){
            $this->to_back(10007);
        }
        $data['date'] = date('YmdHis');
        $redis = SavorRedis::getInstance();
        $redis->select(13);
        //$redis->rpush('reportData', json_encode($data));
        
        $key = $this->countHeartlogPre.$data['mac'].'_'.date('YmdHis');
        $redis->set($key, json_encode($data));
        
        $key = "heartbeat:".$data['clientid'].':'.$data['mac'];
        $redis->set($key,json_encode($data),2592000);
        
        
        
        //$bkey = 'bkheartlog_'.$data['mac'].'_'.date('YmdHis');
        //$redis->set($bkey,json_encode($data));
        $ret = array();
        $ret['deviceType'] = $data['clientid'];
        $ret['mac'] = $data['mac'];
        $ret['hotelId'] = $data['hotelId'];
        $ret['adsPeriod'] = $data['period'];
        $ret['demandPeriod'] = $data['demand'];
        $ret['logoPeriod'] = $data['logo'];
        $ret['boxApkVersion'] = $data['apk'];
        $ret['smallWebVersion'] = $data['war'];
        $ret['outerIp'] = $data['outside_ip'];
        $ret['innerIp'] = $data['intranet_ip'];
        $ret['teamviewer_id'] = $data['teamviewer_id'];
        $ret['pro_period']  = $data['pro_period'];
        $ret['adv_period'] = $data['adv_period'];
        $ret['pro_download_period'] = $data['pro_download_period'];
        $ret['ads_download_period'] = $data['ads_download_period'];
        $ret['net_speed']  = $data['net_speed'];
        
        $m_box = new \Common\Model\BoxModel();
        $info = $m_box->field('is_4g')->where(array('state'=>1,'flag'=>0,'mac'=>$data['mac']))->find();
        
        $ret['is_4g'] = intval($info['is_4g']);
        $this->to_back($ret);
    }
    /**
     * @desc 同步心跳数据到数据库
     */
    /* public function syncData(){
        $m_heart_log = new \Common\Model\HeartLogModel();
        $m_heart_log->truncateTable();
        $redis = SavorRedis::getInstance();
        $redis->select(14);
        $keys = $redis->keys('*');
        //print_r($keys);exit;
        $m_hotel = new \Common\Model\HotelModel();
        $m_box   = new \Common\Model\BoxModel();
        //$keys = array('00E04C6A2F72*1');
        //$keys = array('FCD5D900B83F*2');
        $data = $map = array();
        $dflag = $mflag =  0;
        $total = count($keys);
        
        
        $mark =0;
        foreach($keys as $v){
            
            $key_arr = explode('*', $v);
            $mac = $key_arr[0];
            $clientid = $key_arr[1];
            
            $ret = $redis->get($v);
            $ret_arr = explode(',', $ret);
            if($clientid==1){//小平台
                
                //$ret_arr = array('1','00E04C6A2F72','10.10.10.10','192.168.2.30','1','2','3','4','5','7','20170405054010');
                $hotelId = intval($ret_arr[9]);
                
                //$hotelInfo = $m_hotel->getHotelInfoById($hotelId);
                $hotelInfo = $m_hotel->getHotelInfoByMac($mac);
                if($hotelInfo){
                    $data[$dflag]['box_mac'] = $mac;
                    $data[$dflag]['hotel_id'] = $hotelId;
                    $data[$dflag]['hotel_name'] = $hotelInfo['hotel_name'];
                    $data[$dflag]['area_id'] = $hotelInfo['area_id'];
                    $data[$dflag]['area_name'] = $hotelInfo['area_name'];
                    $data[$dflag]['last_heart_time'] = date('Y-m-d H:i:s',strtotime($ret_arr[10]));
                    $data[$dflag]['type'] = $clientid;
                    $data[$dflag]['hotel_ip'] = $ret_arr[2];
                    $data[$dflag]['small_ip'] = $ret_arr[3];
                    $data[$dflag]['ads_period'] = $ret_arr[4];
                    $data[$dflag]['demand_period'] = $ret_arr[5];
                    $data[$dflag]['apk_version'] = $ret_arr[6];
                    $data[$dflag]['war_version'] = $ret_arr[7];
                    $data[$dflag]['logo_period'] = $ret_arr[8];
                    $dflag ++;
                    $mark++;
                    //$m_hotel->add($data);
                }
            }else if($clientid==2) {//机顶盒
               //$ret_arr = array('1','00E04C6A2F72','10.10.10.10','192.168.2.30','1','2','3','4','5','7','20170405054010');
               $hotelInfo =  $m_box->getHotelInfoByBoxMac($mac);
               if($hotelInfo){
                   $map[$mflag]['box_id'] = $hotelInfo['box_id'];
                   $map[$mflag]['box_mac'] = $mac;
                   $map[$mflag]['room_id'] = $hotelInfo['room_id'];
                   $map[$mflag]['room_name'] = $hotelInfo['room_name'];
                   $map[$mflag]['hotel_id']  = $hotelInfo['hotel_id'];
                   $map[$mflag]['hotel_name'] = $hotelInfo['hotel_name'];
                   $map[$mflag]['area_id'] = $hotelInfo['area_id'];
                   $map[$mflag]['area_name'] = $hotelInfo['area_name'];
                   $map[$mflag]['last_heart_time'] = date('Y-m-d H:i:s',strtotime($ret_arr[10]));
                   $map[$mflag]['type'] = $clientid;
                   $map[$mflag]['hotel_ip'] = $ret_arr[2];
                   $map[$mflag]['small_ip'] = $ret_arr[3];
                   $map[$mflag]['ads_period'] = $ret_arr[4];
                   $map[$mflag]['demand_period'] = $ret_arr[5];
                   $map[$mflag]['apk_version'] = $ret_arr[6];
                   $map[$mflag]['war_version'] = $ret_arr[7];
                   $map[$mflag]['logo_period'] = $ret_arr[8];
                   $mflag++;
                   $mark++;
               }
            }
            
            
            if($mark%100==0){
                if(!empty($data)){
                    $m_heart_log->addAll($data);
                }
                if(!empty($map)){
                    $m_heart_log->addAll($map);
                }
                $data = array();
                $map = array();
                $dflag = $mflag = 0; 
            }  
        }
       
        if(!empty($data)){
            $m_heart_log->addAll($data);
        }
        if(!empty($map)){
            $m_heart_log->addAll($map);
        }
  
        echo "数据入库成功"."\n";exit;
    } */
    public function syncData(){
        $m_heart_log = new \Common\Model\HeartLogModel();
        //$m_heart_log->truncateTable();
        $redis = SavorRedis::getInstance();
        $redis->select(13);
        $keys = $redis->keys('heartbeat*');
        
        $m_hotel = new \Common\Model\HotelModel();
        $m_box   = new \Common\Model\BoxModel();
        //$data = $map = array();
        $dflag = $mflag =  0;
        $total = count($keys);
       
        $mark =0;
        foreach($keys as $v){
            $key_arr = explode(':', $v);
            $mac = $key_arr[2];
            $clientid = $key_arr[1];
            
            $ret = $redis->get($v);
            $ret_arr = json_decode($ret,true);
            
            //print_r($ret_arr);exit;
            //echo $clientid;exit;
            if($clientid==1 && !empty($ret_arr['date'])){//小平台
                $hotelInfo = $m_hotel->getHotelInfoByMac($mac);
                if($hotelInfo){
                    
                    $hotelId = intval($ret_arr['hotelId']);
                    $heart_log_info = $m_heart_log->getInfo('hotel_id,last_heart_time',array('hotel_id'=>$hotelId,'type'=>$clientid));
                    $data = array();
                    if(empty($heart_log_info)){
                        $data['box_mac'] = $mac;
                        $data['hotel_id'] = $hotelId;
                        $data['hotel_name'] = $hotelInfo['hotel_name'];
                        $data['area_id'] = $hotelInfo['area_id'];
                        $data['area_name'] = $hotelInfo['area_name'];
                        $data['last_heart_time'] = date('Y-m-d H:i:s',strtotime($ret_arr['date']));
                        $data['type'] = $clientid;
                        $data['hotel_ip'] = $ret_arr['outside_ip'];
                        $data['small_ip'] = $ret_arr['intranet_ip'];
                        $data['ads_period'] = $ret_arr['period'];
                        $data['demand_period'] = $ret_arr['demand'];
                        $data['apk_version'] = $ret_arr['apk'];
                        $data['war_version'] = $ret_arr['war'];
                        $data['logo_period'] = $ret_arr['logo'];
                        $m_heart_log->add($data);
                    }else {
                        $last_heart_time = date('Y-m-d H:i:s',strtotime($ret_arr['date']));
                        if($last_heart_time != $heart_log_info['last_heart_time']){
                            $data['box_mac'] = $mac;
                            //$data['hotel_id'] = $hotelId;
                            $data['hotel_name'] = $hotelInfo['hotel_name'];
                            $data['area_id'] = $hotelInfo['area_id'];
                            $data['area_name'] = $hotelInfo['area_name'];
                            $data['last_heart_time'] = date('Y-m-d H:i:s',strtotime($ret_arr['date']));
                            //$data['type'] = $clientid;
                            $data['hotel_ip'] = $ret_arr['outside_ip'];
                            $data['small_ip'] = $ret_arr['intranet_ip'];
                            $data['ads_period'] = $ret_arr['period'];
                            $data['demand_period'] = $ret_arr['demand'];
                            $data['apk_version'] = $ret_arr['apk'];
                            $data['war_version'] = $ret_arr['war'];
                            $data['logo_period'] = $ret_arr['logo'];
                            
                            $m_heart_log->where(array('hotel_id'=>$hotelId,'type'=>$clientid))->save($data);
                        }
                    }
    
                }//判断酒楼是否存在完毕
                
                
            }else if($clientid==2 && !empty($ret_arr['date'])) {//机顶盒
                $hotelInfo =  $m_box->getHotelInfoByBoxMacNew($mac);
                
                if($hotelInfo){
                    $heart_log_info = $m_heart_log->getInfo('hotel_id,last_heart_time',array('hotel_id'=>$hotelInfo['hotel_id'],'box_id'=>$hotelInfo['box_id'],'type'=>$clientid));
                    $map = array();
                    if(empty($heart_log_info)){
                        
                        $map['box_id'] = $hotelInfo['box_id'];
                        $map['box_mac'] = $mac;
                        $map['room_id'] = $hotelInfo['room_id'];
                        $map['room_name'] = $hotelInfo['room_name'];
                        $map['hotel_id']  = $hotelInfo['hotel_id'];
                        $map['hotel_name'] = $hotelInfo['hotel_name'];
                        $map['area_id'] = $hotelInfo['area_id'];
                        $map['area_name'] = $hotelInfo['area_name'];
                        $map['last_heart_time'] = date('Y-m-d H:i:s',strtotime($ret_arr['date']));
                        $map['type'] = $clientid;
                        $map['hotel_ip'] = $ret_arr['outside_ip'];
                        $map['small_ip'] = $ret_arr['intranet_ip'];
                        $map['ads_period'] = $ret_arr['period'];
                        $map['demand_period'] = $ret_arr['demand'];
                        $map['apk_version'] = $ret_arr['apk'];
                        $map['war_version'] = $ret_arr['war'];
                        $map['logo_period'] = $ret_arr['logo'];
                        
                        $map['pro_period']  = $ret_arr['pro_period'];
                        $map['adv_period']  = $ret_arr['adv_period'];
                        $map['pro_download_period'] = $ret_arr['pro_download_period'];
                        $map['ads_download_period'] = $ret_arr['ads_download_period'];
                        $map['net_speed']           = $ret_arr['net_speed'];
                        $ret = $m_heart_log->add($map);
                    }else {
                        $last_heart_time = date('Y-m-d H:i:s',strtotime($ret_arr['date']));
                        
                        if($last_heart_time != $heart_log_info['last_heart_time']){
                            //$map['box_id'] = $hotelInfo['box_id'];
                            $map['box_mac'] = $mac;
                            $map['room_id'] = $hotelInfo['room_id'];
                            $map['room_name'] = $hotelInfo['room_name'];
                            //$map['hotel_id']  = $hotelInfo['hotel_id'];
                            $map['hotel_name'] = $hotelInfo['hotel_name'];
                            $map['area_id'] = $hotelInfo['area_id'];
                            $map['area_name'] = $hotelInfo['area_name'];
                            $map['last_heart_time'] = date('Y-m-d H:i:s',strtotime($ret_arr['date']));
                            //$map['type'] = $clientid;
                            $map['hotel_ip'] = $ret_arr['outside_ip'];
                            $map['small_ip'] = $ret_arr['intranet_ip'];
                            $map['ads_period'] = $ret_arr['period'];
                            $map['demand_period'] = $ret_arr['demand'];
                            $map['apk_version'] = $ret_arr['apk'];
                            $map['war_version'] = $ret_arr['war'];
                            $map['logo_period'] = $ret_arr['logo'];
                            
                            $map['pro_period']  = $ret_arr['pro_period'];
                            $map['adv_period']  = $ret_arr['adv_period'];
                            $map['pro_download_period'] = $ret_arr['pro_download_period'];
                            $map['ads_download_period'] = $ret_arr['ads_download_period'];
                            $map['net_speed']           = $ret_arr['net_speed'];
                            $m_heart_log->where(array('hotel_id'=>$hotelInfo['hotel_id'],'box_id'=>$hotelInfo['box_id'],'type'=>$clientid))->save($map);
                                    
                        }
                    }
                }//酒楼判断存在完毕
            }//小平台、机顶盒判断完毕
        }//数据循环结束
        echo "数据入库成功"."\n";exit;
    }
    /**
     * @desc 统计历史心跳上报数据
     */
    public function countHeartLog(){
        exit(0);
        $redis = SavorRedis::getInstance();
        $redis->select(13);
        $keys = $redis->keys($this->countHeartlogPre.'*');
        $m_heart_all_log = new \Common\Model\HeartAllLogModel();
        $m_hotel = new \Common\Model\HotelModel();
        $m_box   = new \Common\Model\BoxModel();
        foreach($keys as $v){
            $data = array();
            $info = $redis->get($v);
            if(!empty($info)){
                $info = json_decode($info,true);
                if(empty($info['mac']) || empty($info['clientid']) || empty($info['date'])){
                    $redis->remove($v);
                    continue;
                }
                $date = substr($info['date'],0,8);
                $loginfo = $m_heart_all_log->getOne($info['mac'], $info['clientid'], $date);
                $hour = intval(substr($info['date'], 8,2));
                
                if(empty($loginfo)){
                    if($info['clientid'] ==1){
                        $hotelInfo = $m_hotel->getHotelInfoByMac($info['mac']);
                        if($hotelInfo){
                            $data['area_id']    = $hotelInfo['area_id'];
                            $data['area_name']  = $hotelInfo['area_name'];
                            $data['hotel_id']   = $info['hotelId'];
                            $data['hotel_name'] = $hotelInfo['hotel_name'];
                            $data['mac']        = $info['mac'];
                            $data['type']       = $info['clientid'];
                            $data['date']       = $date;
                            $data['hour'.$hour] = 1;
                            $ret = $m_heart_all_log->addInfo($data);
                        }
                        
                    }else if($info['clientid'] ==2){
                        $hotelInfo =  $m_box->getHotelInfoByBoxMac($info['mac']);
                        if($hotelInfo){
                            $data['area_id']    = $hotelInfo['area_id'];
                            $data['area_name']  = $hotelInfo['area_name'];
                            $data['hotel_id']   = $info['hotelId'];
                            $data['hotel_name'] = $hotelInfo['hotel_name'];
                            $data['room_id']    = $hotelInfo['room_id'];
                            $data['room_name']  = $hotelInfo['room_name'];
                            $data['box_id']     = $hotelInfo['box_id'];
                            $data['mac']        = $info['mac'];
                            $data['type']       = $info['clientid'];
                            $data['date']       = $date;
                            $data['hour'.$hour] = 1;
                            $ret = $m_heart_all_log->addInfo($data);
                        }           
                    } 
                }else {
                    $where = array();
                    if($info['clientid'] ==1){
                        $where['mac'] = $info['mac'];
                        $where['type']= $info['clientid'];
                        $where['date']= $date;
                        $ret = $m_heart_all_log->updateInfo($where['mac'], $where['type'], $where['date'], $filed = "hour{$hour}");
                        //$ret = $m_heart_all_log->where($where)->setInc("hour{$hour}",1);
                    }else if($info['clientid'] ==2){
                        $where['mac'] = $info['mac'];
                        $where['type']= $info['clientid'];
                        $where['date']= $date;
                        $ret = $m_heart_all_log->updateInfo($where['mac'], $where['type'], $where['date'], $filed = "hour{$hour}");
                        //$ret = $m_heart_all_log->where($where)->setInc("hour{$hour}",1);
                    }
                }
                if($ret){
                    $redis->remove($v);
                }
            }
        }
        echo 'OK';
    }
}