<?php
/*
 * 订单
 */
namespace H5\Controller;
use Think\Controller;

class OrderController extends Controller {

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

}