<?php
namespace Heartcalcu\Controller;
use Think\Controller;
use Common\Lib\Curl;
use \Common\Controller\CommonController as CommonController;
/**
 * @desc 心跳计算
 */
class CalculationController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {

       // parent::_init_();
    }

    public function ipCalcu ($data) {
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(0);
        $out_ip = $this->params['outside_ip'];
        $hid = $this->params['hotelId'];
        $teamviewer_id = $this->params['teamviewer_id'];
        $bool = false;
        $r_data = $this->params['intranet_ip'].'*'.$hid;
        $bool = $redis->set($out_ip, $r_data);

        $hotelModel = new \Common\Model\HotelModel();
        if ($bool) {
            //hotelid不为空
            if ($hid) {
                //直接判断远程id
                if($teamviewer_id){
                    $hotel_info = $hotelModel->find($hid);
                    $remote_id = $hotel_info['remote_id'];
                    if($remote_id != $teamviewer_id) {
                        $team_ar['remote_id'] = $teamviewer_id;
                        $where =  'id='.$hid;
                        $hotelModel->saveData($team_ar, $where);
                    }
                }
                $h_res = $redis->keys($hid);
                if($h_res){
                    //hotelid为key，查找值存在
                    $h_dat = $redis->get($hid);
                    //判断字符串存在
                    if (strstr($h_dat, $out_ip)) {
                        $bool = true;
                    } else {
                        $sub = $h_dat.','.$out_ip;
                        $bool = $redis->set($hid, $sub);
                    }
                    if($bool){
                        $dat['ip'] = $h_dat;
                        $where =  'hotel_id='.$hid;
                        $hextModel = new \Common\Model\HotelExtModel();
                        $dp = $hextModel->getData('ip', $where);
                        if($dp[0]['ip'] != $dat['ip']){
                            $bool = $hextModel->saveData($dat, $where);
                        }
                    }

                } else {
                    //hotelid为key，查找值不存在
                    //更新value
                    $bool = $redis->set($hid, $this->params['outside_ip']);
                    //更新数据库存
                    if ($bool) {

                        $dat['ip'] = $out_ip;
                        $where =  'hotel_id='.$hid;
                        $hextModel = new \Common\Model\HotelExtModel();
                        $bool = $hextModel->saveData($dat, $where);
                    }

                }
            }
        }
        return $bool;




    }

    /**
     *
     * @param $data
     */
    public function numCount($data){
        return true;
        
        $bool = false;
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $mac_str = $this->params['mac'].'*'.$this->params['clientid'];
        $txt = sprintf("%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s",
                       $this->params['clientid'],$this->params['mac'],$this->params['outside_ip'],
                       $this->params['intranet_ip'],$this->params['period'],$this->params['demand'],
                       $this->params['apk'],$this->params['war'],$this->params['logo'],$this->params['hotelId'],
                       $this->params['date'],$this->params['pro_period'],$this->params['adv_period'],
                       $this->params['pro_download_period'],$this->params['ads_download_period'],$this->params['net_speed']
                );
        $bool = $redis->set($mac_str, $txt, 2592000);
        return $bool;
    }
    
    public function getHeartdata(){
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $hextModel = new \Common\Model\HotelExtModel();
        
        $cache_key = 'heartbeat:1';
        $redis->select(13);
        $data = $redis->keys($cache_key.'*');
        
        if(empty($data)){
            echo '数组为空'."\n";
            $boc = true;
            die;
        }
        $flag = 0;
        foreach ($data as $val){
            
            $heartbeat = $redis->get($val);
            $heartbeat = json_decode($heartbeat,true); 
            $this->params = $heartbeat;
            
            $hid = $this->params['hotelId'];
            if ($hid) {
                if(!is_numeric($hid)){
                    continue;
                }
                $where =  'hotel_id='.$hid;
                $res = $hextModel->getOnerow($where);
                if(!$res){
                    echo '酒楼'.$hid.'不存在'."\n";
                    continue;
                }
            }
            $client_id = $this->params['clientid'];
            $boc = false;
            if ($client_id == 1) {
                //判断该该酒楼和mac是否对应
                $tmps = $hextModel->isHaveMac('h.id', "1 and he.mac_addr='".$heartbeat['mac']."' and h.id=".$heartbeat['hotelId']);
                if(empty($tmps)){
                    $redis->remove($val);
                    continue;
                }
                
                //IP统计db0
                $res_ip = $this->ipCalcu($this->params);

                if($res_ip){
                   $boc = true;  
                }
            } 
            $flag ++;
        }
        if($boc){
            echo date('Y-m-d H:i:s').'redis数据处理成功'.$flag."\n";
        }else{
            
            echo date('Y-m-d H:i:s').'redis数据处理失败'."\n";
        }
       
    }
}