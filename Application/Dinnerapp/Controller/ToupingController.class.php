<?php
namespace Dinnerapp\Controller;

use Think\Controller;
use \Common\Controller\BaseController as BaseController;
use \Common\Lib\SavorRedis;


class ToupingController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'reportLog':
                $this->is_verify = 1;
                $this->valid_fields = array(
                    'device_id'=>1001,
                    'hotel_id'=>1001,
                    'room_id'=>1001,
                );
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 上报日志
     */
    public function reportLog(){

        $save['wifi'] = $this->params['wifi'];
        $save['hotel_id'] = $this->params['hotel_id'];
        $save['room_id'] = $this->params['room_id'];
        $save['device_type'] = $this->params['device_type'];
        $save['device_id'] = $this->params['device_id'];
        $save['state'] = $this->params['state'];
        $save['ads_type'] = $this->params['ads_type'];
        $save['info'] = json_decode($this->params['info']);
        $save['screen_num'] = $this->params['screen_num'];
        $save['screen_time'] = $this->params['screen_time'];
        $save['create_time'] = date("Y-m-d H:i:s");
        $save['screen_type'] = $this->params['screen_type'];
        foreach($save as $k=>$v) {
            if(is_null($v)) {
                $save[$k] = '';
            }
        }
        $redis = SavorRedis::getInstance();
        $redis->select(13);
        $bool = $redis->rpush('dinnertoupinglog', json_encode($save));
        if($bool) {
            $this->to_back(10000);
        } else {
            $this->to_back(60001);
        }
    }

    public function  syncData(){
        $redis = SavorRedis::getInstance();
        $key = 'dinnertoupinglog';
        $redis->select(13);
        $count = $redis->lsize($key);
        $num = 0;
        $rool_back = array();
        $size = 2;
        $dinner_hall_Model = new \Common\Model\DinnerHallLogModel();
        $insert_arr = array();
        while($data = $redis->lpop($key)) {
            $data = json_decode($data, true);

            $insert_arr[] = $data;
            $num++;
            if($num%$size == 0) {

                $bool = $dinner_hall_Model->addAll($insert_arr);
                if($bool) {
                    $page = $num/$size;
                    echo '第'.$page.'页完毕'.PHP_EOL;
                    //sleep(2);
                    $insert_arr = array();
                } else {
                    $rool_back = $insert_arr;
                }
            }

        }
        if($insert_arr) {
            $bool = $dinner_hall_Model->addAll($insert_arr);
            if($bool) {
                $page = $page+1;
                echo '23i第'.$page.'页完毕'.PHP_EOL;
            } else {
                $rool_back = $insert_arr;
            }
        }
        if($rool_back) {
            foreach ($rool_back as $k=>$v) {
                foreach ($v as $ks=>$vs) {
                    $bool = $redis->rpush('dinnertoupinglog', json_encode($vs));
                }
            }
        } else {
            echo '处理成功';
        }
    }

}
