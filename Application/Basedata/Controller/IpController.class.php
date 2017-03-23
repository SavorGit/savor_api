<?php
namespace BaseData\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\BaseController as BaseController;
class IpController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getLastVodList':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    public function getIp(){
        $ip = get_client_ipaddr(); //获取客户端ip地址
        $redis = SavorRedis::getInstance();
        $info = $redis->get($ip);
        $m_sys_config = '';
    }
}