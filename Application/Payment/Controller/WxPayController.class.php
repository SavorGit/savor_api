<?php
namespace Payment\Controller;
/**
 * 微信支付
 */
class WxPayController extends BaseController{
    
    public function __construct(){
        parent::__construct();
    }

    public function refundMoney(){
        $fwh_config = C('WX_FWH_CONFIG');
        $appid = $fwh_config['appid'];
        $pay_config = C('PAY_WEIXIN_CONFIG');
        $payconfig = array(
            'appid'=>$appid,
            'partner'=>$pay_config['partner'],
            'key'=>$pay_config['key']
        );
        $m_order = new \Common\Model\Smallapp\RedpacketModel();
        $where = array('status'=>array('in','4,6'));
        $where['add_time'] = array('egt','2019-03-05 00:00:00');
        $res_order = $m_order->getDataList('id,user_id,pay_fee,add_time',$where,'id asc');
        $nowdtime = date('Y-m-d H:i:s');
        if(empty($res_order)){
            echo $nowdtime.' refund over'."\r\n";
            exit;
        }
        $diff_time = 86400/2;
        $now_time = time();
        $m_wxpay = new \Payment\Model\WxpayModel();
        $m_redpacketreceive = new \Common\Model\Smallapp\RedpacketReceiveModel();
        $m_orderserial = new \Common\Model\Smallapp\OrderserialModel();
        $m_refund = new \Common\Model\Smallapp\RefundModel();
        foreach ($res_order as $v){
            $trade_no = $v['id'];
            $pay_fee = $v['pay_fee'];
            $order_time = strtotime($v['add_time']);
            if($now_time-$order_time<$diff_time){
                continue;
            }
            $res_receive = $m_redpacketreceive->getDataList('money',array('redpacket_id'=>$trade_no,'status'=>1));
            $get_money = 0;
            if(!empty($res_receive)){
                foreach ($res_receive as $vm){
                    $get_money+=$vm['money'];
                }
            }
            $refund_money = sprintf("%01.2f",$pay_fee-$get_money);
            if($refund_money>0){
                $res_orderserial = $m_orderserial->getInfo(array('trade_no'=>$trade_no));
                if(!empty($res_orderserial) && !empty($res_orderserial['serial_order'])){
                    $trade_info = array('trade_no'=>$trade_no,'batch_no'=>$res_orderserial['serial_order'],'pay_fee'=>$pay_fee,'refund_money'=>$refund_money);
                    $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                    if($res["return_code"]=="SUCCESS" && $res["result_code"]=="SUCCESS" && !isset($res['err_code'])){
                        if($pay_fee==$refund_money){
                            $type = 0;
                        }else{
                            $type = 1;
                        }
                        $refund_data = array('trade_no'=>$trade_no,'user_id'=>$v['user_id'],'refund_money'=>$refund_money,'batch_no'=>$trade_info['batch_no'],
                            'type'=>$type,'status'=>2,'refund_time'=>date('Y-m-d H:i:s'),'succ_time'=>date('Y-m-d H:i:s'));
                        $m_refund->addData($refund_data);
                        $m_order->updateData(array('id'=>$trade_no),array('status'=>7));
                        $nowdtime = date('Y-m-d H:i:s');
                        echo $nowdtime.' trade_no:'.$trade_no.' refund success'."\r\n";
                    }else{
                        echo $nowdtime.' trade_no:'.$trade_no.' refund fail'."\r\n";
                    }
                }
            }
        }
    }


    public function mchpaychange(){
        $fwh_config = C('WX_FWH_CONFIG');
        $appid = $fwh_config['appid'];
        $pay_config = C('PAY_WEIXIN_CONFIG');
        $payconfig = array(
            'appid'=>$appid,
            'partner'=>$pay_config['partner'],
            'key'=>$pay_config['key']
        );
        $red_packet_key = C('SAPP_REDPACKET');
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);

        $orders = array();
        $content = file_get_contents('php://input');
        if(!empty($content)) {
            $res = json_decode($content, true);
            if (!empty($res['Message'])) {
                $message = base64_decode($res['Message']);
                $orders = json_decode($message,true);
            }
        }

        $m_redpacket_receive = new \Common\Model\Smallapp\RedpacketReceiveModel();
        $m_refund = new \Common\Model\Smallapp\RefundModel();
        $m_wxpay = new \Payment\Model\WxpayModel();
        if(!empty($orders)){
            foreach ($orders as $v){
                $order_id = $v['order_id'];
                $res_refund = $m_refund->getInfo(array('trade_no'=>$order_id));
                if(!empty($res_refund)){
                    die("redpacket_id:$order_id has refund");
                }

                $fields = 'a.id,a.redpacket_id,a.user_id,a.money,user.mpopenid as openid';
                $where = "a.redpacket_id=$order_id and a.status=0";
                $order = 'id asc';
                $res_receive = $m_redpacket_receive->getList($fields,$where,$order);
                if(empty($res_receive)){
                    die("redpacket_id:$order_id send bonus finish");
                }
                $key_getmoney = $red_packet_key.$order_id.':getmoney';//已经抢到红包的用户列表
                $res_getmoney = $redis->get($key_getmoney);
                if(!empty($res_getmoney)){
                    $res_moneyuser = json_decode($res_getmoney,true);
                }else{
                    $res_moneyuser = array();
                }

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
                        echo "redpacket_id:$order_id redpacket_receive_id:{$v['id']} send bonus ok"."\r\n";
                    }
                }
                if(!empty($res_moneyuser)){
                    $redis->set($key_getmoney,json_encode($res_moneyuser),86400);
                }
            }
        }
    }
    
   
}