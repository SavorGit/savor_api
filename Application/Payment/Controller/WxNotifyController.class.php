<?php
namespace Payment\Controller;
/**
 * 微信支付回调
 */
class WxNotifyController extends BaseController{
    
    public function __construct(){
        parent::__construct();
    }
    
    /**
     * 默认为微信PC支付回调
     */
    public function index(){
        $os_type = 1;
        $m_wxpay = new \Payment\Model\WxpayModel($os_type);
        $payconfig = $this->getPayConfig();
        $reslut = $m_wxpay->pay_notify($payconfig);
    }
    
    /**
     * 微信PC支付回调
     */
    public function pc(){
        $os_type = 1;
        $m_wxpay = new \Payment\Model\WxpayModel($os_type);
        $payconfig = $this->getPayConfig();
        $reslut = $m_wxpay->pay_notify($payconfig);
    }
    /**
     * 微信手机支付回调
     */
    public function mobile(){
        $os_type = 2;
        $m_wxpay = new \Payment\Model\WxpayModel($os_type);
        $payconfig = $this->getPayConfig();
        $reslut = $m_wxpay->pay_notify($payconfig);
    }
    
   
}