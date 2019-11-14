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
        $payconfig = $this->getPayConfig();

        $operation_uid = 42996;
        $m_order = new \Common\Model\Smallapp\RedpacketModel();
        $where = array('status'=>array('in','4,6'));
        $where['user_id'] = array('neq',$operation_uid);
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
        $payconfig = $this->getPayConfig();

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
            $pk_type = C('PK_TYPE');//1走线上原来逻辑 2走新的支付方式
            foreach ($orders as $v){
                $message_oidinfo = explode('_',$v['order_id']);
                $order_id = $message_oidinfo[0];
                $receive_id = $message_oidinfo[1];

                $res_refund = $m_refund->getInfo(array('trade_no'=>$order_id));
                if(!empty($res_refund)){
                    die("redpacket_id:$order_id has refund");
                }
                $fields = 'a.id,a.redpacket_id,a.user_id,a.money,user.openid as small_openid,user.mpopenid as openid';
                $where = "a.id=$receive_id and a.status=0";
                $order = 'id asc';
                $res_receive = $m_redpacket_receive->getList($fields,$where,$order);
                if(empty($res_receive)){
                    die("redpacket_id:$order_id send bonus finish");
                }
                foreach ($res_receive as $v){

                    $key_hasget = $red_packet_key.$order_id.':hasget';//已经抢到红包资格的用户列表
                    $res_hasget = $redis->get($key_hasget);
                    if(!empty($res_hasget)){
                        $res_hasget = json_decode($res_hasget,true);
                    }else{
                        $res_hasget = array();
                    }
                    if(!array_key_exists($v['user_id'],$res_hasget)){
                        continue;
                    }

                    $key_getmoney = $red_packet_key.$order_id.':getmoney';//已经抢到红包的用户列表
                    $res_getmoney = $redis->get($key_getmoney);
                    if(!empty($res_getmoney)){
                        $res_moneyuser = json_decode($res_getmoney,true);
                    }else{
                        $res_moneyuser = array();
                    }
                    if($pk_type==1){
                        $open_id = $v['openid'];
                    }else{
                        $open_id = $v['small_openid'];
                    }
                    if(empty($open_id) || array_key_exists($v['user_id'],$res_moneyuser)){
                        continue;
                    }

                    $key_lockuser = $red_packet_key.$order_id.':lockuser';//加锁用户
                    $res_lockuser = $redis->get($key_lockuser);
                    if(!empty($res_lockuser)){
                        $res_lockuser = json_decode($res_lockuser,true);
                    }else{
                        $res_lockuser = array();
                    }
                    if(array_key_exists($v['user_id'],$res_lockuser)){
                        continue;
                    }
                    $res_lockuser[$v['user_id']] = date('Y-m-d H:i:s');
                    $redis->set($key_lockuser,json_encode($res_lockuser),86400);

                    $trade_info = array('trade_no'=>$v['redpacket_id'],'money'=>$v['money'],'open_id'=>$open_id);
                    $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);

                    unset($res_lockuser[$v['user_id']]);
                    $redis->set($key_lockuser,json_encode($res_lockuser),86400);

                    if($res['code']==10000){
                        $res_moneyuser[$v['user_id']] = $v['money'];
                        $redis->set($key_getmoney,json_encode($res_moneyuser),86400);

                        $condition = array('id'=>$v['id']);
                        $m_redpacket_receive->updateData($condition,array('status'=>1,'receive_time'=>date('Y-m-d H:i:s')));
                        echo "redpacket_id:$order_id redpacket_receive_id:{$v['id']} send bonus ok"."\r\n";
                    }
                }
            }
        }
    }

    public function paychange(){
        $order_id = intval($_REQUEST['oid']);

        $payconfig = $this->getPayConfig();

        $red_packet_key = C('SAPP_REDPACKET');
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);

        $m_redpacket_receive = new \Common\Model\Smallapp\RedpacketReceiveModel();
        $m_refund = new \Common\Model\Smallapp\RefundModel();
        $m_wxpay = new \Payment\Model\WxpayModel();

        $orders = array(array('order_id'=>$order_id));

        if(!empty($orders)){
            $pk_type = C('PK_TYPE');//1走线上原来逻辑 2走新的支付方式
            foreach ($orders as $v){
                $order_id = $v['order_id'];
                $res_refund = $m_refund->getInfo(array('trade_no'=>$order_id));
                if(!empty($res_refund)){
                    die("redpacket_id:$order_id has refund");
                }
                $fields = 'a.id,a.redpacket_id,a.user_id,a.money,user.openid as small_openid,user.mpopenid as openid';
                $where = "a.redpacket_id=$order_id and a.status=0";
                $order = 'id asc';
                $res_receive = $m_redpacket_receive->getList($fields,$where,$order);
                if(empty($res_receive)){
                    die("redpacket_id:$order_id send bonus finish");
                }
                foreach ($res_receive as $v){

                    $key_hasget = $red_packet_key.$order_id.':hasget';//已经抢到红包资格的用户列表
                    $res_hasget = $redis->get($key_hasget);
                    if(!empty($res_hasget)){
                        $res_hasget = json_decode($res_hasget,true);
                    }else{
                        $res_hasget = array();
                    }
                    if(!array_key_exists($v['user_id'],$res_hasget)){
                        continue;
                    }

                    $key_getmoney = $red_packet_key.$order_id.':getmoney';//已经抢到红包的用户列表
                    $res_getmoney = $redis->get($key_getmoney);
                    if(!empty($res_getmoney)){
                        $res_moneyuser = json_decode($res_getmoney,true);
                    }else{
                        $res_moneyuser = array();
                    }
                    if($pk_type==1){
                        $open_id = $v['openid'];
                    }else{
                        $open_id = $v['small_openid'];
                    }
                    if(empty($open_id) || array_key_exists($v['user_id'],$res_moneyuser)){
                        continue;
                    }

                    $key_lockuser = $red_packet_key.$order_id.':lockuser';//加锁用户
                    $res_lockuser = $redis->get($key_lockuser);
                    if(!empty($res_lockuser)){
                        $res_lockuser = json_decode($res_lockuser,true);
                    }else{
                        $res_lockuser = array();
                    }
                    if(array_key_exists($v['user_id'],$res_lockuser)){
                        continue;
                    }
                    $res_lockuser[$v['user_id']] = date('Y-m-d H:i:s');
                    $redis->set($key_lockuser,json_encode($res_lockuser),86400);

                    $trade_info = array('trade_no'=>$v['redpacket_id'],'money'=>$v['money'],'open_id'=>$open_id);
                    $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);

                    unset($res_lockuser[$v['user_id']]);
                    $redis->set($key_lockuser,json_encode($res_lockuser),86400);

                    if($res['code']==10000){
                        $res_moneyuser[$v['user_id']] = $v['money'];
                        $redis->set($key_getmoney,json_encode($res_moneyuser),86400);

                        $condition = array('id'=>$v['id']);
                        $m_redpacket_receive->updateData($condition,array('status'=>1,'receive_time'=>date('Y-m-d H:i:s')));
                        echo "redpacket_id:$order_id redpacket_receive_id:{$v['id']} send bonus ok"."\r\n";
                    }
                }
            }
        }
    }

    public function integralwithdraw(){
        $params = $_REQUEST['params'];
        $hash_ids_key = C('HASH_IDS_KEY_ADMIN');
        $hashids = new \Common\Lib\Hashids($hash_ids_key);
        $decode_info = $hashids->decode($params);
        if(empty($decode_info)){
            $error = array('code'=>99001,'msg'=>'decode error');
            $this->ajaxReturn($error);
        }
        $order_id = intval($decode_info[0]);
        $m_order = new \Common\Model\Smallapp\ExchangeModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order) || $res_order['status']!=20){
            $error = array('code'=>99002,'msg'=>'status error');
            $this->ajaxReturn($error);
        }
        $openid = $res_order['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info) || empty($user_info['openid'])){
            $res = array('code'=>99003,'msg'=>'openid error');
            $this->ajaxReturn($res);
        }

        $goods_id = $res_order['goods_id'];
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if($res_goods['status']!=2 || $res_goods['type']!=30){
            $error = array('code'=>99004,'msg'=>'goods info error');
            $this->ajaxReturn($error);
        }
        /*
        $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
        $res_integral = $m_userintegral->getInfo(array('openid'=>$openid));
        if(empty($res_integral) || $res_integral['integral']<$res_goods['rebate_integral']){
            $res = array('code'=>99005,'msg'=>'integral not enough');
            $this->ajaxReturn($res);
        }
        */
        $payconfig = $this->getPayConfig(5);
        $money = $res_order['total_fee'];
        $trade_info = array('trade_no'=>$order_id,'money'=>$money,'open_id'=>$user_info['openid']);
        $m_wxpay = new \Payment\Model\WxpayModel();
        $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
        $this->ajaxReturn($res);
    }
    
   
}