<?php
namespace Small\Controller;
use Think\Controller;
use \Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;
class NetReportController extends CommonController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'reportInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('netparams'=>1001);
                break;
            
           
        }
        parent::_init_();
       
    }
    /**
     * @desc 
     */
    public function reportInfo(){
        $netparams = $this->params['netparams'];
        
        $netparams = str_replace('\\', '', $netparams);
        //echo $netparams;exit;
        $netparams = json_decode($netparams,true);
        
        $redis = new SavorRedis();
        $redis->select(10);
        $cache_key = C('NET_REPORT_KEY');
        foreach($netparams as $key=>$val){
            $keys = $cache_key.$val['box_id'];
            $redis->set($keys, json_encode($val),300);
        }
        
        $this->to_back(10000);
    }
}