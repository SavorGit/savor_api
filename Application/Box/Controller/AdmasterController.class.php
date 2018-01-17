<?php
namespace Box\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
class AdmasterController extends CommonController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getConfFile':
                $this->is_verify = 0;
                break;
           
        }
        parent::_init_();
       
    }
    public function getConfFile(){
        $path = 'Public/admaster/admaster_sdkconfig.xml';
        $time = filemtime($path);
        $data['update_time'] = $time;
        $data['file'] = 'http://'.$_SERVER['HTTP_HOST'].'/'.$path;
        $content = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/'.$path);
        
        $data['md5']  = md5($content);
        $this->to_back($data);
    }
}