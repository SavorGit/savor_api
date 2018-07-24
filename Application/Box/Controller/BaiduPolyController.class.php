<?php
namespace Box\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;
class BaiduPolyController extends CommonController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'recordPlay':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'media_id'=>1001);
                break;    
        }
        parent::_init_(); 
    }
    /**
     * @desc 记录百度聚屏广告播放
     */
    public function recordPlay(){
        $box_mac = $this->params['box_mac'];      //机顶盒mac
        $media_id = $this->params['media_id'];    //广告资源id
        $redis =  SavorRedis::getInstance();
        $redis->select(4);
        $times = getMillisecond();
        $cache_key = $box_mac.":".$times;
        $redis->set($cache_key, $media_id);
        $this->to_back(10000);
        
    }
}