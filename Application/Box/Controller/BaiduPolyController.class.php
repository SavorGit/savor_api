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
                $this->valid_fields = array('box_mac'=>1001,'media_id'=>1000,
                                            'media_name'=>1000,'media_md5'=>1000,'tpmedia_id'=>1000);
                break;    
        }
        parent::_init_(); 
    }
    /**
     * @desc 记录百度聚屏广告播放
     */
    public function recordPlay(){
        $box_mac   = $this->params['box_mac'];      //机顶盒mac
        $media_id  = $this->params['media_id'] ? $this->params['media_id'] :0;     //广告资源id
        $media_name= $this->params['media_name'];   //广告资源名称
        $media_md5 = $this->params['media_md5'];    //广告资源md5
        $tpmedia_id= $this->params['tpmedia_id'];   //第三方媒体 1:百度 2:钛镁 3:奥凌
        $redis =  SavorRedis::getInstance();
        $redis->select(4);
        $times = getMillisecond();
        $cache_key = $box_mac.":".$times;
        
        $data = array();
        $data['media_id']  = $media_id;
        $data['media_name']= $media_name;
        $data['media_md5'] = $media_md5;
        $data['tpmedia_id']= $tpmedia_id;
        $redis->set($cache_key, json_encode($data));
        
        /* if(!empty($media_id)){
            $redis->set($cache_key, $media_id);
        }else {
            
        } */
        
        $this->to_back(10000);
        
    }
}