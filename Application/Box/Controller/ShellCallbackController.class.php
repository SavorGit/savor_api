<?php
namespace Box\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;
class ShellCallbackController extends CommonController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'pushResult':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>'1001','data'=>'1000');
                break;
                 
        }
        parent::_init_();
    }
    /**
     * @desc 接受盒子执行shell命令后的结果
     */
    public function pushResult(){
        $box_mac = $this->params['box_mac'];
        $data = $this->params['data'];
        $redis = SavorRedis::getInstance();
        $redis->select(11);
        $cache_key = "BOX:SHELL:".$box_mac;
        $data = str_replace('\\', '', $data);
        $redis->set($cache_key, $data, 3600);
        $this->to_back(10000);
    }
}