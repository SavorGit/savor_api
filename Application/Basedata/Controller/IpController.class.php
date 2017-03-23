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
            case 'getIp':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    public function getIp(){
        $ip = get_client_ipaddr(); //获取客户端ip地址
        $redis = SavorRedis::getInstance();
        
        $info = $redis->get($ip);
       
        $m_sys_config = new \Common\Model\SysConfigModel();
        $where = "'mobileApi.getIp.command_port','mobileApi.getIp.download_port','mobileApi.getIp.netty_port','mobileApi.getIp.type'";
        $configList = $m_sys_config->getInfo($where);
        $data['type']         = $configList[3]['config_value'];
        $data['command_port'] = $configList[0]['config_value'];
        $data['download_port']= $configList[1]['config_value'];
        $data['netty_port']   = $configList[2]['config_value'];
        if($info){
            $data['ip'] = $info;
            $tmp = explode('*', $info);
            $data['hotelId'] = $tmp[1];
            $data['localIp'] = $tmp[0];
        }
        $this->to_back($data);
    }
}