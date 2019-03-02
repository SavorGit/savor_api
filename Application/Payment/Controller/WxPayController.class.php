<?php
namespace Payment\Controller;
/**
 * 微信支付
 */
class WxPayController extends BaseController{
    
    public function __construct(){
        parent::__construct();
    }

    public function mchpaychange(){
        $order_id = I('orderid',0,'intval');
        $fwh_config = C('WX_FWH_CONFIG');
        $appid = $fwh_config['appid'];
        $pay_config = C('PAY_WEIXIN_CONFIG');
        $payconfig = array(
            'appid'=>$appid,
            'partner'=>$pay_config['partner'],
            'key'=>$pay_config['key']
        );
        $m_redpacket_receive = new \Common\Model\Smallapp\RedpacketReceiveModel();
        $fields = 'a.id,a.redpacket_id,a.user_id,a.money,user.mpopenid as openid';
        $where = "a.redpacket_id=$order_id and a.status=0";
        $order = 'id asc';
        $res_receive = $m_redpacket_receive->getList($fields,$where,$order);
        if(empty($res_receive)){
            die("redpacket_id:$order_id send bonus finish");
        }

        $red_packet_key = C('SAPP_REDPACKET');
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $key_getmoney = $red_packet_key.$order_id.':getmoney';//已经抢到红包的用户列表
        $res_getmoney = $redis->get($key_getmoney);
        if(!empty($res_getmoney)){
            $res_moneyuser = json_decode($res_getmoney,true);
        }else{
            $res_moneyuser = array();
        }
        $m_wxpay = new \Payment\Model\WxpayModel();
        foreach ($res_receive as $v){
            if(empty($v['openid']) || array_key_exists($v['openid'],$res_moneyuser)){
                continue;
            }
            $trade_info = array('trade_no'=>$v['redpacket_id'],'money'=>$v['money'],'open_id'=>$v['openid']);
            $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
            if($res['code']==10000){
                $res_moneyuser[$v['openid']] = $v['money'];
                $condition = array('id'=>$v['id']);
                $m_redpacket_receive->updateData($condition,array('status'=>1,'receive_time'=>date('Y-m-d H:i:s')));
                echo "redpacket_id:$order_id redpacket_receive_id:{$v['id']} send bonus ok \r\n";
            }
        }
        if(!empty($res_moneyuser)){
            $redis->set($key_getmoney,json_encode($res_moneyuser),86400);
        }

    }
    
   
}