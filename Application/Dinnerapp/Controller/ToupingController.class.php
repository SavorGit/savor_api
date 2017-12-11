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
                $this->valid_fields = array('mobile'=>1001,'invite_code'=>1001,'hotel_id'=>1001,'room_id'=>1001,'screen_result'=>1001,
                                            'screen_type'=>1001,'screen_num'=>1001,'screen_time'=>1001
                );
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 上报日志
     */
    public function reportLog(){
        $client_arr = C('CLIENT_NAME_ARR');
        $save['mobile'] = $this->params['mobile'];
        $save['invite_code'] = $this->params['invite_code'];
        $save['hotel_id'] = $this->params['hotel_id'];
        $save['room_id'] = $this->params['room_id'];
        $save['welcome_word'] = $this->params['welcome_word'];
        $save['welcome_template'] = $this->params['welcome_template'];
        $save['screen_result'] = $this->params['screen_result'];
        $save['screen_type'] = $this->params['screen_type'];
        $traceinfo = $this->traceinfo;
        $save['device_type'] = $client_arr[$traceinfo['clientname']];
        $save['device_id'] = $traceinfo['device_id'];

        $save['screen_num'] = $this->params['screen_num'];
        $save['screen_time'] = $this->params['screen_time'];
        $save['info'] = $this->params['info'];
        $save['create_time'] = date("Y-m-d H:i:s");
        foreach($save as $k=>$v) {
            if(empty($v)){
                unset($save[$k]);
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
        exit(1);
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
