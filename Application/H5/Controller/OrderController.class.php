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

    public function reward(){
        $now_time = date('Y-m-d H:i:s');
        $ts = I('get.ts','');
        if($ts!='qQHilxeO0zvgok47j'){
            die($now_time.' error');
        }
        $hour = date('G');
        if($hour!=10){
            die($now_time.' hour error');
        }
        $nowdtime = date('Y-m-d H:i:s');
        echo $nowdtime." start\r\n";

        $start_time = date("Y-m-d 00:00:00", strtotime("-1 day"));
        $end_time = date("Y-m-d 23:59:59", strtotime("-1 day"));

        $where = array('status'=>2);
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
        $m_reward = new \Common\Model\Smallapp\RewardModel();
        $res_order = $m_reward->getDataList('*',$where,'id asc');
        if(!empty($res_order)){
            $m_orderserial = new \Common\Model\Smallapp\OrderserialModel();
            $m_baseinc = new \Payment\Model\BaseIncModel();
            $payconfig = $m_baseinc->getPayConfig(5);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $m_staff = new \Common\Model\Integral\StaffModel();
            foreach ($res_order as $v){
                $order_id=$v['id'];
                $res_orderserial = $m_orderserial->getInfo(array('trade_no'=>$order_id));
                if(!empty($res_orderserial)){
                    $res_staff = $m_staff->getInfo(array('id'=>$v['staff_id'],'status'=>1));
                    if(!empty($res_staff)){
                        $reward_openid = $res_staff['openid'];

                        $trade_info = array('trade_no'=>$order_id,'money'=>$v['money'],'open_id'=>$reward_openid);
                        $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
                        if($res['code']==10000){
                            $condition = array('id'=>$order_id);
                            $m_reward->updateData($condition,array('status'=>3,'update_time'=>date('Y-m-d H:i:s')));

                            echo "order_id:$order_id  reward ok"."\r\n";
                        }
                    }
                }
            }
        }
        echo $nowdtime." finish\r\n";
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
                        if(isset($res['err_code'])){
                            $payconfig = $m_baseinc->getPayConfigOld(2);
                            $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                        }
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
            $m_income = new \Common\Model\Smallapp\UserincomeModel();
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
                        $refund_amount = $v['amount'];
                        $cancel_oids = array($order_id);
                        if($v['status']==61){
                            $receive_orders = $m_order->getDataList('id,amount,total_fee,address,status',array('gift_oid'=>$order_id),'id desc');
                            $receive_money = 0;
                            $receive_amount = 0;
                            foreach ($receive_orders as $ov){
                                if($ov['status']==63 && empty($ov['address'])){
                                    $cancel_oids[]=$ov['id'];
                                }else{
                                    $receive_money+=$ov['total_fee'];
                                    $receive_amount+=$ov['amount'];
                                }
                            }
                            $refund_amount = $refund_amount-$receive_amount;
                            $refund_money = sprintf("%.2f",$pay_fee-$receive_money);
                        }
                        if($refund_money<=0){
                            echo "order_id:$order_id  cancel error money:$refund_money"."\r\n";
                            continue;
                        }
                        $cancel_info = json_encode(array('oids'=>$cancel_oids,'refund_money'=>$refund_money));
                        echo "order_id:$order_id  cancel info:$cancel_info"."\r\n";

                        $trade_info = array('trade_no'=>$trade_no,'batch_no'=>$res_orderserial['serial_order'],'pay_fee'=>$pay_fee,'refund_money'=>$refund_money);
                        $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                        if(isset($res['err_code'])){
                            $payconfig = $m_baseinc->getPayConfigOld(2);
                            $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                        }

                        if($res["return_code"]=="SUCCESS" && $res["result_code"]=="SUCCESS" && !isset($res['err_code'])){
                            $data = array('status'=>62,'finish_time'=>date('Y-m-d H:i:s'));
                            $where = array('id'=>array('in',$cancel_oids));
                            $m_order->updateData($where,$data);
                            echo "order_id:$order_id  cancel and refund ok"."\r\n";

                            if($v['sale_uid']){
                                $res_income = $m_income->getDataList('*',array('user_id'=>$v['sale_uid'],'order_id'=>$order_id),'id desc');
                                if(!empty($res_income)){
                                    $income_amount = $res_income[0]['amount']-$refund_amount>0?$res_income[0]['amount']-$refund_amount:0;
                                    $income_fee = ($res_income[0]['price']-$res_income[0]['supply_price'])*$res_income[0]['profit']*$income_amount;
                                    $income_fee = sprintf("%.2f",$income_fee);

                                    $income_data = array('amount'=>$income_amount,'income_fee'=>$income_fee,'update_time'=>date('Y-m-d H:i:s'));
                                    $m_income->updateData(array('id'=>$res_income[0]['id']),$income_data);
                                    $income_sql = $m_income->getLastSql();
                                    $income_info = 'old_amount:'.$res_income[0]['amount'].' refund_amount:'.$refund_amount.' sql:'.$income_sql;
                                    echo "order_id:$order_id income_info: $income_info"."\r\n";
                                }
                            }

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


    public function giftgivefailure(){
        $nowdtime = date('Y-m-d H:i:s');
        echo $nowdtime." start\r\n";
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $where = array('otype'=>6,'status'=>array('in',array(12,61,17)));
        $where['gift_oid'] = array('eq',0);
        $where['add_time'] = array('gt','2020-07-29 17:09:11');
//        $where['id'] = array('eq',1001367);

        $res_order = $m_order->getDataList('*',$where,'id asc');
        if(!empty($res_order)){
            $diff_time = 86400*5;

            $m_orderserial = new \Common\Model\Smallapp\OrderserialModel();
            $m_baseinc = new \Payment\Model\BaseIncModel();
            $payconfig = $m_baseinc->getPayConfig(2);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $m_ordermap = new \Common\Model\Smallapp\OrdermapModel();
            $m_income = new \Common\Model\Smallapp\UserincomeModel();
            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
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
                        $refund_amount = $v['amount'];
                        $receive_money = 0;
                        $receive_amount = 0;
                        $cancel_oids = array();
                        $has_receive_status = array(51,52,53,63);
                        switch ($v['status']){
                            case 12:
                                $cancel_oids[] = $order_id;
                                break;
                            case 61:
                            case 17:
                                $receive_orders = $m_order->getDataList('id,amount,receive_num,total_fee,address,status',array('gift_oid'=>$order_id),'id desc');
                                foreach ($receive_orders as $ov){
                                    if(in_array($ov['status'],$has_receive_status)){
                                        if(!empty($ov['address'])){
                                            $res_ogoods = $m_ordergoods->getDataList('*',array('order_id'=>$ov['id']),'id desc');
                                            $now_receive_num = $ov['receive_num']>0?$ov['receive_num']:$ov['amount'];
                                            $receive_total_fee = sprintf("%.2f",$now_receive_num*$res_ogoods[0]['price']);
                                            $receive_money+=$receive_total_fee;
                                            $receive_amount+=$ov['receive_num'];
                                        }else{
                                            $cancel_oids[]=$ov['id'];
                                        }
                                    }else{
                                        $cancel_oids[]=$ov['id'];
                                    }

                                    $oidtrees = ",{$ov['id']},";
                                    $sunwhere = array('gift_oidtrees'=>array('like',"%$oidtrees%"));
                                    $sunwhere['otype'] = 7;
                                    $receive_sunorders = $m_order->getDataList('id,amount,receive_num,total_fee,address,status',$sunwhere,'id desc');
                                    $is_son_cancel = 0;
                                    if(!empty($receive_sunorders)){
                                        foreach ($receive_sunorders as $sov){
                                            if(in_array($sov['status'],array(51,52,53,63,71))){
                                                if(in_array($sov['status'],$has_receive_status) && $sov['receive_num'] && !empty($sov['address'])){
                                                    $res_sogoods = $m_ordergoods->getDataList('*',array('order_id'=>$sov['id']),'id desc');
                                                    $receive_stotal_fee = sprintf("%.2f",$sov['receive_num']*$res_sogoods[0]['price']);
                                                    $receive_money+=$receive_stotal_fee;
                                                    $receive_amount+=$sov['receive_num'];
                                                }else{
                                                    $cancel_oids[]=$sov['id'];
                                                    $is_son_cancel = 1;
                                                }
                                            }
                                        }
                                        if($is_son_cancel){
                                            $cancel_oids[]=$ov['id'];
                                        }
                                    }
                                }
                                if($v['status']==61){
                                    $cancel_oids[]=$order_id;
                                }else{
                                    if(!empty($cancel_oids)){
                                        $cancel_oids[]=$order_id;
                                    }
                                }
                                break;
                        }
                        $refund_amount = $refund_amount-$receive_amount;
                        $refund_money = sprintf("%.2f",$pay_fee-$receive_money);
                        if(!empty($cancel_oids)){
                            $cancel_oids = array_unique($cancel_oids);
                        }
                        if($refund_money<=0){
                            echo "order_id:$order_id  cancel error money:$refund_money"."\r\n";
                            continue;
                        }
                        if($refund_amount<=0){
                            echo "order_id:$order_id  cancel error refund_amount:$refund_amount"."\r\n";
                            continue;
                        }

                        $cancel_info = json_encode(array('oids'=>array_values($cancel_oids),'refund_money'=>$refund_money));
                        echo "order_id:$order_id  cancel info:$cancel_info"."\r\n";

                        $trade_info = array('trade_no'=>$trade_no,'batch_no'=>$res_orderserial['serial_order'],'pay_fee'=>$pay_fee,'refund_money'=>$refund_money);
                        $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                        if(isset($res['err_code'])){
                            $payconfig = $m_baseinc->getPayConfigOld(2);
                            $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                        }
                        if($res["return_code"]=="SUCCESS" && $res["result_code"]=="SUCCESS" && !isset($res['err_code'])){
                            $data = array('status'=>62,'finish_time'=>date('Y-m-d H:i:s'));
                            $where = array('id'=>array('in',$cancel_oids));
                            $m_order->updateData($where,$data);
                            echo "order_id:$order_id  cancel and refund ok"."\r\n";

                            if($v['sale_uid']){
                                $res_income = $m_income->getDataList('*',array('user_id'=>$v['sale_uid'],'order_id'=>$order_id),'id desc');
                                if(!empty($res_income)){
                                    $income_amount = $res_income[0]['amount']-$refund_amount>0?$res_income[0]['amount']-$refund_amount:0;
                                    $income_fee = ($res_income[0]['price']-$res_income[0]['supply_price'])*$res_income[0]['profit']*$income_amount;
                                    $income_fee = sprintf("%.2f",$income_fee);

                                    $income_data = array('amount'=>$income_amount,'income_fee'=>$income_fee,'update_time'=>date('Y-m-d H:i:s'));
                                    $m_income->updateData(array('id'=>$res_income[0]['id']),$income_data);
                                    $income_sql = $m_income->getLastSql();
                                    $income_info = 'old_amount:'.$res_income[0]['amount'].' refund_amount:'.$refund_amount.' sql:'.$income_sql;
                                    echo "order_id:$order_id income_info: $income_info"."\r\n";
                                }
                            }
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