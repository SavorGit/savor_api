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

        parent::_init_();
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
                        $bool = $hextModel->saveData($dat, $where);

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
        $txt = sprintf("%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s",$this->params['clientid'],$this->params['mac'],$this->params['outside_ip'],$this->params['intranet_ip'],$this->params['period'],$this->params['demand'],$this->params['apk'],$this->params['war'],$this->params['logo'],$this->params['hotelId'],date("yyyyMMddHHmm"));
        $bool = $redis->set($mac_str, $txt, 259200);
        return $bool;
    }
    
    public function getHeartdata(){



       // var_dump($this->params);
        $client_id = $this->params['clientid'];

        if ($client_id == 1) {
            //IP统计db0
            $res_ip = $this->ipCalcu($this->params);
            //次数统计
            //mac*client_id key值是
            $res_num = $this->numCount($this->params);
        } else {
            $res_ip  = true;
            $res_num = $this->numCount($this->params);
        }
    }
}