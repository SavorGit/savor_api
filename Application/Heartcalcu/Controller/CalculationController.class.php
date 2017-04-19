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
        $bool = false;
        $r_data = $this->params['intranet_ip'].'*'.$hid;
        $bool = $redis->set($out_ip, $r_data);
        if ($bool) {
            //hotelid不为空
            if ($hid) {
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
        $bool = false;
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $mac_str = $this->params['mac'].'*'.$this->params['clientid'];
        $txt = sprintf("%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s",$this->params['clientid'],$this->params['mac'],$this->params['outside_ip'],$this->params['intranet_ip'],$this->params['period'],$this->params['demand'],$this->params['apk'],$this->params['war'],$this->params['logo'],$this->params['hotelId'],date("YmdHis"));
        $bool = $redis->set($mac_str, $txt, 604800);
        return $bool;
    }
    
    public function getHeartdata(){
//{"clientid":"1","hotelId":"20","period":"0330095004061335","mac":"00E04C5E4F5D","demand":"20170406161345","apk":"2017030902","war":"2017032002","logo":"391","intranet_ip":"192.168.1.120","outside_ip":"221.218.166.232"}redis数据处理失败 
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $hextModel = new \Common\Model\HotelExtModel();
        $rkey = 'reportData';
        $redis->select(13);
        $roll_back_arr = array();
        $count = 0;
        $max = $redis->lsize($rkey);
        $data = $redis->lgetrange($rkey,0,$max);
        if(empty($data)){
            echo '数组为空'."\n";
            $boc = true;
            die;
        }
        foreach ($data as $val){
            $this->params = json_decode($val, true);
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
                //IP统计db0
                $res_ip = $this->ipCalcu($this->params);

                if($res_ip){
                    //次数统计
                    //mac*client_id key值是
                    $res_num = $this->numCount($this->params);
                    if($res_num){
                        $boc = true;
                    }
                }
            } else {
                $res_ip  = true;
                $res_num = $this->numCount($this->params);
                if($res_num){
                    $boc = true;
                }
            }
            $redis->select(13);
            $bp = $redis->lPop($rkey);
            //var_dump($bp);
        }
        if($boc){
            echo 'redis数据处理成功'."\n";
        }else{
            
            echo 'redis数据处理失败'."\n";
        }
       /* foreach($roll_back_arr as $k=>$v){
            $redis->rpush($k,$v);
        }*/

       // var_dump($this->params);

    }
}