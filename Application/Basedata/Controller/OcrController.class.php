<?php
namespace BaseData\Controller;
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
                $this->valid_fields = array('img_url'=>1001,'openid'=>1001,'source'=>1001);
                break;
        }
        parent::_init_();
    }
    public function getIdcardInfo(){
        $openid = $this->params['openid'];
        $img_url = $this->params['img_url'];
        $source = intval($this->params['source']);//1热点饭局,5销售端


        $m_small_app = new Smallapp_api($source);
        $tokens  = $m_small_app->getWxAccessToken();
        $oss_host = get_oss_host();
        $img_url = $oss_host.$img_url;
        $img_url = urlencode($img_url);
        $ret = $m_small_app->getIdcardInfo($tokens, $img_url);
        
        $this->to_back($ret);
        
    }
    
}