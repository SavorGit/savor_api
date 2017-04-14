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
        }
        parent::_init_();
    }
    
    public function index(){
        
        //https://mb.rerdian.com/survival/api/2/survival
        //?hotelId=10000000&period=454&mac=111&demand=555&apk=666&war=888&logo=ppp&ip=89.3143.1
        $data = array();
        $data['clientid'] = I('get.clientid','');     //上报客户端类型 1:小平台 2:机顶盒
        $data['hotelId']  = I('get.hotelId','');
        $data['period']   = I('get.period','');
        $data['mac']      = I('get.mac','');
        $data['demand']   = I('get.demand','');
        $data['apk']      = I('get.apk','');
        $data['war']      = I('get.war','');
        $data['logo']     = I('get.logo','');
        $data['intranet_ip'] = I('get.ip','');         //内网ip
        $data['outside_ip']  = get_client_ipaddr();    //外网ip
        if(empty($data['mac'])){
            $this->to_back(10004);
        }
        $redis = SavorRedis::getInstance();
       
        $redis->select(13);
        //ip库0
        /*$redis->select(0);
        //$redis->flushadb();*/
        $redis->rpush('reportData', json_encode($data));
        
        /*$str = '/heartcalcu/calculation/getHeartdata';
        $url = C('HOST_NAME').$str;
		 $curl = new Curl();
		$data = json_encode($data);
		$curl->post($url, $data); */
       /*  $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch); */
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
        
        $this->to_back($ret);
    }
    /**
     * @desc 同步心跳数据到数据库
     */
    public function syncData(){
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
    }
}