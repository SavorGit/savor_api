<?php
/**
 * @desc 4G投屏接收客户端信息（接收开始投屏、结束投屏信息）
 * @since 2018-06-05
 * @author zhang.yingtao
 */

namespace Forscreen\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\BaseController as BaseController;
class IndexController extends BaseController{ 
    /**
     * 构造函数 
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'receiveStartInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>'1000','resource_url'=>'1001');
                break;
            case 'receiveStopInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>'1001');
                break;
            
        }
        parent::_init_(); 
    }
    /**
     * @desc 接收手机推送的机顶盒mac和要投屏的资源
     */
    public function receiveStartInfo(){
        $data = array();
        $data['box_mac']      = $this->params['box_mac'] ? $this->params['box_mac'] :'00226D5846F1';
        $data['resource_url'] = $this->params['resource_url'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = 'for_screen';
        $ret = $redis->rpush($cache_key, json_encode($data));
        if(empty($ret)){
            $this->to_back(91001);
        }else {
            $this->to_back(10000);
        } 
    }
    /**
     * @desc 接收投屏结束指令
     */
    public function receiveStopInfo(){
        $data = array();
        $data['box_mac'] = $this->params['box_mac'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = 'stop_screen';
        $ret = $redis->rpush($cache_key, json_encode($data));
        if(empty($ret)){
            $this->to_back(91002);
        }else {
            $this->to_back(10000);
        } 
    }
}