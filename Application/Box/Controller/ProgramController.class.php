<?php
namespace Box\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;
class ProgramController extends CommonController{ 
    private $box_download_pre ;
    private $box_program_play_pre;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'reportDownloadInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>'1001','type'=>'1001','resource_info'=>'1000');
                break;
            case 'reportPlayInfo';
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>'1001','resource_info'=>'1000');
                break;
                 
        }
        parent::_init_();
        $this->box_download_pre = 'box:download:';
        $this->box_program_play_pre = 'box:play:';
    }
    /**
     * @desc 机顶盒当前下载中资源上报(1广告、2节目、3宣传片)
     */
    public function reportDownloadInfo(){
        $box_mac = $this->params['box_mac'];
        $type = $this->params['type'];
        $resource_info = str_replace("\\", '',$this->params['resource_info'] );
        if(!empty($resource_info)){
            $redis = new SavorRedis();
            $redis->select(14);
            $cache_key = $this->box_download_pre.$type.':'.$box_mac;
            $redis_resource_info = $redis->get($cache_key);
            if(md5($resource_info) != md5($redis_resource_info)){
                $redis->set($cache_key, $resource_info);
                $this->to_back(10000);
            }else {
                $this->to_back(30072);
            }
        }else {
            $this->to_back(30071);
        }   
    }
    /**
     * @desc 机顶盒当前已下载(播放中)的节目单资源
     */
    public function reportPlayInfo(){
        $box_mac = $this->params['box_mac'];
        $resource_info = str_replace("\\", '',$this->params['resource_info'] );
        $redis = new SavorRedis();
        $redis->select(14);
        $cache_key = $this->box_program_play_pre.$box_mac;
        
        $play_info = $redis->get($cache_key);
        if(md5($resource_info)!== md5($play_info)){
            $redis->set($cache_key, $resource_info);
            $this->to_back(10000);
        }else {
            $this->to_back(30073);
        }  
    }
}