<?php
namespace Payment\Controller;
use Think\Controller;
/**
 * @desc 基础类
 *
 */
class BaseController extends Controller {
    
    public function __construct(){
        parent::__construct();
        $this->handlePublicParams();
    }
    
    public function handlePublicParams(){
        $this->assign('host_name',$this->host_name());
        $this->assign('public_url',$this->host_name().'/Public');
        
    }
    public function host_name(){
        $http = 'http://';
        return $http.$_SERVER['HTTP_HOST'];
    }
    public function getPayConfig($pk_type=0){
        if(!$pk_type){
            $pk_type = C('PK_TYPE');//1走线上原来逻辑 2走新的支付方式
        }
        if($pk_type==1){
            $fwh_config = C('WX_FWH_CONFIG');
            $appid = $fwh_config['appid'];
            $pay_config = C('PAY_WEIXIN_CONFIG');
            $payconfig = array(
                'appid'=>$appid,
                'partner'=>$pay_config['partner'],
                'key'=>$pay_config['key']
            );
        }else{
            $smallapp_config = C('SMALLAPP_CONFIG');
            $pay_wx_config = C('PAY_WEIXIN_CONFIG_1554975591');
            $sslcert_path = APP_PATH.'Payment/Model/wxpay_lib/cert/1554975591_apiclient_cert.pem';
            $sslkey_path = APP_PATH.'Payment/Model/wxpay_lib/cert/1554975591_apiclient_key.pem';
            $payconfig = array(
                'appid'=>$smallapp_config['appid'],
                'partner'=>$pay_wx_config['partner'],
                'key'=>$pay_wx_config['key'],
                'sslcert_path'=>$sslcert_path,
                'sslkey_path'=>$sslkey_path,
            );
        }
        return $payconfig;
    }

}