<?php
namespace Heartbeat\Controller;
use Think\Controller;
use Common\Lib\Curl;
use \Common\Controller\CommonController as CommonController;
/**
 * @desc 心跳上报
 */
class ReportedController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'index':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    
    public function index(){
        
        //https://mb.rerdian.com/survival/api/2/survival
        //?hotelId=10000000&period=454&mac=111&demand=555&apk=666&war=888&logo=ppp&ip=89.3143.1
        $data = array();
        $data['clientid'] = I('get.clientid','');     //上报客户端类型 1:小平台 2:机顶盒
        $data['hotelId']  = I('get.hotelId','');
        $data['period']   = I('get.period','');
        $data['mac']      = I('get.mac','');
        $data['demand']   = I('get.demand','');
        $data['apk']      = I('get.apk','');
        $data['war']      = I('get.war','');
        $data['logo']     = I('get.logo','');
        $data['intranet_ip'] = I('get.id','');         //内网ip
        $data['outside_ip']  = get_client_ipaddr();    //外网ip
        if(empty($data['mac']) || empty($data['period'])){
            $this->to_back(10004);
        }
        
        $url = C('HOST_NAME').'/aa';
		$curl = new Curl();
		
		$data = json_encode($data);
		$curl->post($url, $data);
        $this->to_back(10000);
    }
}