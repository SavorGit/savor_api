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
            case 'getConfig':
                $this->is_verify = 0;
                break;
            case 'recodeQrcodeLog':
                $this->is_verify= 0;
                $this->valid_fields = array('openid'=>1001,'type'=>1001);
                break;
        }
        parent::_init_();
    }
    public function recodeQrcodeLog(){
        
        $openid = $this->params['openid'];
        $type   = intval($this->params['type']);
        $data = array();
        $data['box_mac'] = '';
        $data['openid']  = $openid;
        $data['type']    = $type;
        $data['is_overtime'] = 0;
        $m_qrcode_log = new \Common\Model\Smallapp\QrcodeLogModel();
        $m_qrcode_log->addInfo($data);
        $this->to_back(10000);
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
    public function getConfig(){
        list($t1, $t2) = explode(' ', microtime());
        $sys_time = (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
        $file_exts = C('SAPP_FILE_FORSCREEN_TYPES');
        $exp_time   = 7200000;//扫码失效时间
        $redpacket_exp_time = 1800000;
        $data['sys_time'] = $sys_time;
        $data['exp_time'] = $exp_time;
        $data['redpacket_exp_time'] = $redpacket_exp_time;
        $data['file_exts'] = $file_exts;
        $data['file_exts'] = array_keys($file_exts);
        $data['file_max_size'] = 41943040;
                                 
        $data['polling_time']  = 60;  //文件投屏默认轮询时间60s
        $this->to_back($data);
    }
}