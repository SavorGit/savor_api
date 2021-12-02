<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class ForscreenController extends CommonController{

    function _init_() {
        switch(ACTION_NAME) {
            case 'simulateForscreen':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001);
                break;
            case 'getSimulateResult':
                $this->is_verify =1;
                $this->valid_fields = array('req_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function simulateForscreen(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];

        $now_timestamps = getMillisecond();
        $file_size = 1149039;
        $file_size = round($file_size/1048576,1);
        $url = 'forscreen/resource/15368043845967.mp4';
        $netty_data = array('action'=>999,'url'=>$url,'openid'=>$openid,'resource_type'=>2,
            'video_id'=>$now_timestamps,'filename'=>"{$now_timestamps}.mp4",
        );
        $req_id = forscreen_serial($openid,$now_timestamps,$url);
        $m_netty = new \Common\Model\NettyModel();
        $res_push = $m_netty->pushBox($box_mac,json_encode($netty_data),$req_id);
        if($res_push['error_code']){
            $this->to_back($res_push['error_code']);
        }else{
            $res_data = array('req_id'=>$req_id,'file_size'=>$file_size.'M','api_timeout'=>10000);
            $this->to_back($res_data);
        }
    }

    public function getSimulateResult(){
        $req_id = $this->params['req_id'];

        $redis = new \Common\Lib\SavorRedis();
        $redis->select(5);
        $cache_key = C('SAPP_FORSCREENTRACK').$req_id;
        $res_cache = $redis->get($cache_key);
        $file_size = 1149039/1024;
        $download_time = 0;
        $avg_speed = '';
        if(!empty($res_cache)){
            $cache_data = json_decode($res_cache,true);
            if(!empty($cache_data['box_downstime']) && !empty($cache_data['box_downetime'])){
                $download_time = $cache_data['box_downetime']-$cache_data['box_downstime'];
                if($download_time<=0){
                    $download_time = 1;
                }
                $avg_speed = round($file_size/$download_time).'k/s';
            }
        }
        $res_data = array('download_time'=>intval($download_time),'avg_speed'=>$avg_speed);
        $this->to_back($res_data);
    }
}
