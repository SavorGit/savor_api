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
        $params = I('params','');
        $oinfo = decrypt_data($params);
        if(empty($oinfo['trade_no'])){
            $error_info = array('code'=>10001,'msg'=>'params error');
            die(json_encode($error_info));
        }
        $trade_no = $oinfo['trade_no'];
        $batch_no = $oinfo['batch_no'];
        $pk_type = $oinfo['pk_type'];
        $pay_fee = $oinfo['pay_fee'];
        $refund_money = $oinfo['refund_money'];


        $m_baseinc = new \Payment\Model\BaseIncModel();
        $payconfig = $m_baseinc->getPayConfig($pk_type);

        $trade_info = array('trade_no'=>$trade_no,'batch_no'=>$batch_no,'pay_fee'=>$pay_fee,'refund_money'=>$refund_money);
        $m_wxpay = new \Payment\Model\WxpayModel();
        $res = $m_wxpay->wxrefund($trade_info,$payconfig);
        if(isset($res['err_code'])){
            $payconfig = $m_baseinc->getPayConfigOld($pk_type);
            $res = $m_wxpay->wxrefund($trade_info,$payconfig);
        }
        $is_refund = 0;
        if($res["return_code"]=="SUCCESS" && $res["result_code"]=="SUCCESS" && !isset($res['err_code'])){
            $is_refund = 1;
            $type = 0;
            if($trade_info['pay_fee']-$trade_info['refund_money']>0){
                $type = 1;
            }
            $m_refund = new \Common\Model\Smallapp\RefundModel();
            $refund_data = array('trade_no'=>$batch_no,'refund_money'=>$refund_money,'batch_no'=>$batch_no,
                'type'=>$type,'status'=>2,'refund_time'=>date('Y-m-d H:i:s'),'succ_time'=>date('Y-m-d H:i:s'));
            $m_refund->addData($refund_data);
        }
        $res['code'] = 10000;
        $res['is_refund'] = $is_refund;
        die(json_encode($res));
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
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $is_test = false;
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
                $res_order = $m_redpacket->getInfo(array('id'=>$order_id));
                $m_message = new \Common\Model\Smallapp\MessageModel();
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

                    if($is_test && $res_order['operate_type']==2){
                        $res = array('code'=>10000);
                    }else{
                        $trade_info = array('trade_no'=>$v['redpacket_id'],'money'=>$v['money'],'open_id'=>$open_id);
                        $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
                    }

                    unset($res_lockuser[$v['user_id']]);
                    $redis->set($key_lockuser,json_encode($res_lockuser),86400);

                    if($res['code']==10000){
                        $res_moneyuser[$v['user_id']] = $v['money'];
                        $redis->set($key_getmoney,json_encode($res_moneyuser),86400);

                        $condition = array('id'=>$v['id']);
                        $m_redpacket_receive->updateData($condition,array('status'=>1,'receive_time'=>date('Y-m-d H:i:s')));
                        echo "redpacket_id:$order_id redpacket_receive_id:{$v['id']} send bonus ok"."\r\n";
                        $m_message->recordMessage($open_id,$v['id'],4);
                    }
                }
            }
        }
    }

    public function reissueredpacket(){
        $start_time = date('Y-m-d H:i:s',strtotime("-20 minutes"));
        $end_time = date('Y-m-d H:i:s',strtotime("-10 minutes"));
        $where = array('status'=>6,'operate_type'=>array('neq',3));
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $res = $m_redpacket->getDataList('*',$where,'id asc');
        if(empty($res)){
            $now_time = date('Y-m-d H:i:s');
            echo "reissue_time: $now_time no bonus";
            exit;
        }
        $pk_type = C('PK_TYPE');//1走线上原来逻辑 2走新的支付方式
        $payconfig = $this->getPayConfig();
        $red_packet_key = C('SAPP_REDPACKET');
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);

        $m_redpacket_receive = new \Common\Model\Smallapp\RedpacketReceiveModel();
        $m_refund = new \Common\Model\Smallapp\RefundModel();
        $m_wxpay = new \Payment\Model\WxpayModel();
        foreach ($res as $rv){
            $order_id = $rv['id'];
            $res_refund = $m_refund->getInfo(array('trade_no'=>$order_id));
            if(!empty($res_refund)){
                echo "redpacket_id:$order_id has refund \r\n";
                continue;
            }
            $fields = 'a.id,a.redpacket_id,a.user_id,a.money,user.openid as small_openid,user.mpopenid as openid';
            $where = "a.redpacket_id=$order_id and a.status=0";
            $order = 'id asc';
            $res_receive = $m_redpacket_receive->getList($fields,$where,$order);
            if(empty($res_receive)){
                echo "redpacket_id:$order_id no send fail bonus \r\n";
            }
            $total_money = $rv['total_fee']-$rv['rate_fee'];
            $sum_fields = 'sum(money) as rmoney';
            $swhere = array('redpacket_id'=>$order_id,'status'=>1);
            $res_money = $m_redpacket_receive->getDataList($sum_fields,$swhere,'id desc');
            $receive_money = 0;
            if(!empty($res_money)){
                $receive_money = $res_money[0]['rmoney'];
            }
            $now_receive_money = 0;
            foreach ($res_receive as $v){
                $key_hasget = $red_packet_key.$order_id.':hasget';//已经抢到红包资格的用户列表
                $res_hasget = $redis->get($key_hasget);
                if(!empty($res_hasget)){
                    $res_hasget = json_decode($res_hasget,true);
                }else{
                    $res_hasget = array();
                }
                if(!array_key_exists($v['user_id'],$res_hasget)){
                    echo "redpacket_id:$order_id receive_id:{$v['id']} no hasget \r\n";
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
                    echo "redpacket_id:$order_id receive_id:{$v['id']} hasget \r\n";
                    continue;
                }
                $now_receive_money+=$v['money'];
                if($now_receive_money+$receive_money>$total_money){
                    echo "redpacket_id:$order_id receive_money gt total_money \r\n";
                    continue;
                }

                $trade_info = array('trade_no'=>$v['redpacket_id'],'money'=>$v['money'],'open_id'=>$open_id);
                $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
                if($res['code']==10000){
                    $res_moneyuser[$v['user_id']] = $v['money'];
                    $redis->set($key_getmoney,json_encode($res_moneyuser),86400);

                    $condition = array('id'=>$v['id']);
                    $m_redpacket_receive->updateData($condition,array('status'=>1,'receive_time'=>date('Y-m-d H:i:s')));
                    echo "redpacket_id:$order_id receive_id:{$v['id']} send bonus ok"."\r\n";

                    if($now_receive_money+$receive_money==$total_money){
                        $m_redpacket->updateData(array('id'=>$order_id),array('status'=>5,'grab_time'=>date('Y-m-d H:i:s')));
                        echo "redpacket_id:$order_id send bonus finish \r\n";
                    }
                    continue;
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