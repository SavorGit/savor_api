<?php
namespace Smallsale21\Controller;
use Common\Lib\Smallapp_api;
use \Common\Controller\CommonController as CommonController;
class OcrController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getIdcardInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('img_url'=>1001,'openid'=>1001);
                break;
        }
        parent::_init_();
    }
    public function getIdcardInfo(){
        $m_small_app = new Smallapp_api(5);
        $tokens  = $m_small_app->getWxAccessToken();
        $oss_host = get_oss_host();
        $img_url = $oss_host.$this->params['img_url'];
        $img_url = urlencode($img_url);
        $ret = $m_small_app->getIdcardInfo($tokens, $img_url);
        
        $this->to_back($ret);
        
    }
    
}