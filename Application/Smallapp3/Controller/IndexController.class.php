<?php
namespace Smallapp3\Controller;
use Think\Controller;
use Common\Lib\Smallapp_api;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class IndexController extends CommonController{
    /**
     * @desc 构造函数
     */
    function _init_(){
        switch(ACTION_NAME){
            case 'gencode':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'openid'=>1001,'type'=>1000);
                break;
        }
    }
    /**
     * @desc 扫码链接电视
     */
    public function gencode(){
        $box_mac = $this->params['box_mac'];
        $openid  = $this->params['openid'];
        
        $code = rand(100, 999);
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SMALLAPP_CHECK_CODE');
        $cache_key .= $box_mac.':'.$openid;
        $info = $redis->get($cache_key);
        if(empty($info)){
            $info = array();
            $info['is_have'] = 1;
            $info['code'] = $code;
            $redis->set($cache_key, json_encode($info),7200);
            
            $key = C('SMALLAPP_CHECK_CODE')."*".$openid;
            $keys = $redis->keys($key);
            foreach($keys as $v){
                $key_arr = explode(':', $v);
                if($key_arr[2]!=$box_mac){
                    $redis->remove($v);
                }
            }       
        }else {
            $key = C('SMALLAPP_CHECK_CODE')."*".$openid;
            $keys = $redis->keys($key);
            foreach($keys as $v){
                $key_arr = explode(':', $v);
                if($key_arr[2]!=$box_mac){
                    $redis->remove($v);
                }
            }   
            $info = json_decode($info,true);
        }
        $this->to_back($info);
    }
}