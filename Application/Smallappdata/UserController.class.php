<?php
namespace Smallappdata\Controller;
use \Common\Controller\CommonController as CommonController;

class UserController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getSessionkey':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getSessionkey(){
        $openid = $this->params['openid'];
        $cache_key = C('SAPP_DATA').'session_openid:'.$openid;
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(17);
        $res_session = $redis->get($cache_key);
        $session_key = '';
        if(!empty($res_session)){
            $session_key = $res_session;
        }
        $this->to_back(array('session_key'=>$session_key));
    }
}