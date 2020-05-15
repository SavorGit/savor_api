<?php
/*
 * 订单
 */
namespace H5\Controller;
use Think\Controller;

class OrderController extends Controller {

    public  $order_start_time = '2020-04-02 21:30:00';


    public function redpacketRefundmoney(){
        $m_baseinc = new \Payment\Model\BaseIncModel();
        $payconfig = $m_baseinc->getPayConfig();

        $operation_uid = 42996;
        $m_order = new \Common\Model\Smallapp\RedpacketModel();
        $where = array('status'=>array('in','4,6'));
        $where['user_id'] = array('neq',$operation_uid);
        $where['add_time'] = array('egt','2019-03-05 00:00:00');
        $res_order = $m_order->getDataList('id,user_id,pay_fee,rate_fee,add_time',$where,'id asc');
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
            $rate_fee = $v['rate_fee'];
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
            $refund_money = sprintf("%01.2f",$pay_fee-$rate_fee-$get_money);
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

    public function settlement(){
        $now_time = date('Y-m-d H:i:s');
        $ts = I('get.ts','');
        if($ts!='qQGhlthO0zvGJ4P7M'){
            die($now_time.' error');
        }
        $hour = date('G');
        if($hour!=12){
            die($now_time.' hour error');
        }
        $nowdtime = date('Y-m-d H:i:s');
        echo $nowdtime." start\r\n";
        $end_time = date("Y-m-d 23:59:59", strtotime("-1 day"));
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $where = array('otype'=>3,'pay_type'=>10,'status'=>17,'is_settlement'=>0);
        $where['add_time'] = array('egt',$this->order_start_time);
        $where['finish_time'] = array('elt',$end_time);
//        $where['finish_time'] = array(array('egt','2020-03-30 09:00:00'),array('elt','2020-03-31 14:00:00'), 'and');

        $res_order = $m_order->getDataList('*',$where,'id asc');
        if(!empty($res_order)){
            $m_orderserial = new \Common\Model\Smallapp\OrderserialModel();
            $m_payee = new \Common\Model\Smallapp\MerchantPayeeModel();
            $m_baseinc = new \Payment\Model\BaseIncModel();
            $payconfig = $m_baseinc->getPayConfig(5);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $m_ordersettlement = new \Common\Model\Smallapp\OrdersettlementModel();
            foreach ($res_order as $v){
                $order_id=$v['id'];
                $merchant_id=$v['merchant_id'];
                $res_orderserial = $m_orderserial->getInfo(array('trade_no'=>$order_id));
                if(!empty($res_orderserial)){
                    $res_payee = $m_payee->getInfo(array('merchant_id'=>$merchant_id,'status'=>2));
                    if(!empty($res_payee)){
                        $payee_openid = $res_payee['openid'];
                        $money=$v['total_fee']-$v['delivery_fee'];

                        $trade_info = array('trade_no'=>$order_id,'money'=>$money,'open_id'=>$payee_openid);
                        $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
                        if($res['code']==10000){
                            $condition = array('id'=>$order_id);
                            $m_order->updateData($condition,array('is_settlement'=>1));

                            $data = array('order_id'=>$order_id,'openid'=>$payee_openid,'money'=>$money);
                            $m_ordersettlement->add($data);
                            echo "order_id:$order_id  settlement ok"."\r\n";
                        }
                    }
                }
            }
        }
        echo $nowdtime." finish\r\n";
    }

    public function cancel(){
        $now_time = date('Y-m-d H:i:s');
        $ts = I('get.cs','');
        if($ts!='ehlthj0z8GoJ4P4Md'){
            die($now_time.' error');
        }
        $nowdtime = date('Y-m-d H:i:s');
        echo $nowdtime." start\r\n";
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $where = array('otype'=>3,'status'=>13);
        $where['add_time'] = array('egt',$this->order_start_time);

        $res_order = $m_order->getDataList('*',$where,'id asc');
        if(!empty($res_order)){
            $diff_time = 20*60;

            $m_orderserial = new \Common\Model\Smallapp\OrderserialModel();
            $m_baseinc = new \Payment\Model\BaseIncModel();
            $payconfig = $m_baseinc->getPayConfig(2);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $m_ordermap = new \Common\Model\Smallapp\OrdermapModel();
            foreach ($res_order as $v){
                $order_id=$v['id'];
                $now_time = time();
                $order_time = strtotime($v['add_time']);
                if($now_time-$order_time<$diff_time){
                    continue;
                }
                if($v['pay_type']==10){
                    $res_orderserial = $m_orderserial->getInfo(array('trade_no'=>$order_id));
                    if(!empty($res_orderserial)){
                        $res_ordermap = $m_ordermap->getDataList('id',array('order_id'=>$order_id),'id desc',0,1);
                        $trade_no = $res_ordermap['list'][0]['id'];

                        $trade_info = array('trade_no'=>$trade_no,'batch_no'=>$res_orderserial['serial_order'],'pay_fee'=>$v['pay_fee'],'refund_money'=>$v['pay_fee']);
                        $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                        if($res["return_code"]=="SUCCESS" && $res["result_code"]=="SUCCESS" && !isset($res['err_code'])){
                            $m_order->updateData(array('id'=>$order_id),array('status'=>18,'finish_time'=>date('Y-m-d H:i:s')));
                            echo "order_id:$order_id  cancel and refund ok"."\r\n";
                        }else{
                            echo "order_id:$order_id  cancel and refund error"."\r\n";
                        }
                    }else{
                        echo "order_id:$order_id  cancel error"."\r\n";
                    }
                }else{
                    $m_order->updateData(array('id'=>$order_id),array('status'=>18,'finish_time'=>date('Y-m-d H:i:s')));
                    echo "order_id:$order_id  cancel ok"."\r\n";
                }
            }
        }
        echo $nowdtime." finish\r\n";
    }


    public function giftfailure(){
        $nowdtime = date('Y-m-d H:i:s');
        echo $nowdtime." start\r\n";
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $where = array('otype'=>6,'status'=>array('in',array(12,61)));

        $res_order = $m_order->getDataList('*',$where,'id asc');
        if(!empty($res_order)){
            $diff_time = 86400*5;

            $m_orderserial = new \Common\Model\Smallapp\OrderserialModel();
            $m_baseinc = new \Payment\Model\BaseIncModel();
            $payconfig = $m_baseinc->getPayConfig(2);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $m_ordermap = new \Common\Model\Smallapp\OrdermapModel();
            foreach ($res_order as $v){
                $order_id=$v['id'];
                $now_time = time();
                $order_time = strtotime($v['add_time']);
                if($now_time-$order_time<$diff_time){
                    continue;
                }
                if($v['pay_type']==10){
                    $res_orderserial = $m_orderserial->getInfo(array('trade_no'=>$order_id));
                    if(!empty($res_orderserial)){
                        $res_ordermap = $m_ordermap->getDataList('id',array('order_id'=>$order_id),'id desc',0,1);
                        $trade_no = $res_ordermap['list'][0]['id'];

                        $pay_fee = $v['pay_fee'];
                        $refund_money = $v['pay_fee'];
                        $cancel_oids = array();
                        if($v['status']==61){
                            $res_orders = $m_order->getDataList('id,total_fee,address',array('gift_oid'=>$order_id),'id desc');
                            $receive_money = 0;
                            foreach ($res_orders as $ov){
                                if(empty($ov['address'])){
                                    $cancel_oids[]=$ov['id'];
                                }else{
                                    $receive_money+=$ov['total_fee'];
                                }
                            }
                            $refund_money = sprintf("%.2f",$pay_fee-$receive_money);
                        }else{
                            $cancel_oids[]=$order_id;
                        }
                        $cancel_info = json_encode(array('oids'=>$cancel_oids,'refund_money'=>$refund_money));
                        echo "order_id:$order_id  cancel info:$cancel_info"."\r\n";

                        $trade_info = array('trade_no'=>$trade_no,'batch_no'=>$res_orderserial['serial_order'],'pay_fee'=>$pay_fee,'refund_money'=>$refund_money);
                        $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                        if($res["return_code"]=="SUCCESS" && $res["result_code"]=="SUCCESS" && !isset($res['err_code'])){
                            $data = array('status'=>62,'finish_time'=>date('Y-m-d H:i:s'));
                            $where = array('id'=>array('in',$cancel_oids));
                            $m_order->updateData($where,$data);
                            echo "order_id:$order_id  cancel and refund ok"."\r\n";
                        }else{
                            echo "order_id:$order_id  cancel and refund error"."\r\n";
                        }
                    }else{
                        echo "order_id:$order_id  cancel error"."\r\n";
                    }
                }else{
                    echo "order_id:$order_id  cancel error paytype error"."\r\n";
                }
            }
        }
        echo $nowdtime." finish\r\n";
    }
}