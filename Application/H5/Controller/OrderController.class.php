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

    public function rewardmoney(){
        $content = file_get_contents('php://input');
        $orders = array();
        if(!empty($content)) {
            $res = json_decode($content, true);
            if (!empty($res['Message'])) {
                $message = base64_decode($res['Message']);
                $orders = json_decode($message,true);
            }
        }
        if(empty($orders[0]['order_id'])){
            $now_time = date('Y-m-d H:i:s');
            die($now_time.' error');
        }
        $order_id = intval($orders[0]['order_id']);
        $m_reward = new \Common\Model\Smallapp\RewardModel();
        $res_order = $m_reward->getInfo(array('id'=>$order_id));
        if(!empty($res_order) && $res_order['status']==2){
            if($res_order['staff_id']>0){
                $m_orderserial = new \Common\Model\Smallapp\OrderserialModel();
                $m_baseinc = new \Payment\Model\BaseIncModel();
                $payconfig = $m_baseinc->getPayConfig(5);
                $m_wxpay = new \Payment\Model\WxpayModel();
                $m_staff = new \Common\Model\Integral\StaffModel();

                $res_orderserial = $m_orderserial->getInfo(array('trade_no'=>$order_id));
                if(!empty($res_orderserial)){
                    $res_staff = $m_staff->getInfo(array('id'=>$res_order['staff_id'],'status'=>1));
                    if(!empty($res_staff)){
                        $reward_openid = $res_staff['openid'];

                        $trade_info = array('trade_no'=>$order_id,'money'=>$res_order['money'],'open_id'=>$reward_openid);
                        $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
                        if($res['code']==10000){
                            $condition = array('id'=>$order_id);
                            $m_reward->updateData($condition,array('status'=>3,'update_time'=>date('Y-m-d H:i:s')));

                            echo "order_id:$order_id  reward ok"."\r\n";
                        }
                    }
                }
            }else{
                $m_merchant = new \Common\Model\Integral\MerchantModel();
                $res_merchant = $m_merchant->getInfo(array('hotel_id'=>$res_order['hotel_id'],'status'=>1));
                if(!empty($res_merchant)){
                    $now_money = $res_merchant['money'] + $res_order['money'];
                    $where = array('id'=>$res_merchant['id']);
                    $m_merchant->updateData($where,array('money'=>$now_money));
                }
            }

        }
    }

    public function saleorder(){
        $content = file_get_contents('php://input');
        $orders = array();
        if(!empty($content)) {
            $res = json_decode($content, true);
            if (!empty($res['Message'])) {
                $message = base64_decode($res['Message']);
                $orders = json_decode($message,true);
            }
        }
        $log_content = date("Y-m-d H:i:s").'[order_id]'.$orders[0]['order_id'].'[content]'.$content."\n";
        $this->record_log($log_content);

        if(empty($orders[0]['order_id'])){
            return true;
        }
        $order_id = intval($orders[0]['order_id']);
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        $order_amount = $res_order['amount'];
        if($res_order['status']==51 && $res_order['sale_uid']>0 && $res_order['is_settlement']==0){
            $m_dishgoods = new \Common\Model\Smallapp\DishgoodsModel();
            $res_goods = $m_dishgoods->getInfo(array('id'=>$res_order['goods_id']));
            $distribution_config = json_decode($res_goods['distribution_config'],true);
            $order_distribution_config = array();
            foreach ($distribution_config as $k=>$v){
                if($order_amount>=$v['min'] && $order_amount<=$v['max']){
                    $order_distribution_config = $v;
                }
            }

            $sale_uid = $res_order['sale_uid'];
            $m_distuser = new \Common\Model\Smallapp\DistributionUserModel();
            $res_duser = $m_distuser->getInfo(array('id'=>$sale_uid));
            $admin_openid = '';
            $admin_money = 0;
            if($res_duser['level']==1){
                $buy_type = 1;
                $openid = $res_duser['openid'];
                $money = $order_distribution_config['reward_money'];
            }else{
                $buy_type = 2;
                $openid = $res_duser['openid'];
                $res_admin = $m_distuser->getInfo(array('id'=>$res_duser['parent_id']));
                $admin_openid = $res_admin['openid'];

                $duser_id = $res_goods['duser_id'];
                if($res_duser['parent_id']==$duser_id){
                    $distribution = $order_distribution_config['ts'];
                }else{
                    $distribution = $order_distribution_config['ty'];
                }
                $money = $distribution[1];
                $admin_money = $distribution[0];
            }
            $money = intval($money*$order_amount);
            $admin_money = intval($admin_money*$order_amount);

            $is_purse = 0;
            $test_openids = array('ofYZG417MIHCyVZkq-RbiIddn_8s','ofYZG4yZJHaV2h3lJHG5wOB9MzxE','ofYZG42whtWOvSELbvxvnXHbzty8','ofYZG4zmrApmvRSfzeA_mN-pHv2E','ofYZG47hd0j3wLzonUW6bATpDi3w');
            if(in_array($openid,$test_openids)){
                $is_purse = 1;
            }
            $is_purse = 1;
            if($is_purse==1){
                $m_ordersettlement = new \Common\Model\Smallapp\OrdersettlementModel();
                $m_userpurse = new \Common\Model\Smallapp\UserpurseModel();
                if($money>0){
                    $data = array('order_id'=>$order_id,'openid'=>$openid,'distribution_user_id'=>$res_duser['id'],'money'=>$money,'pay_status'=>3);
                    $m_ordersettlement->add($data);

                    $res_purse = $m_userpurse->getInfo(array('openid'=>$openid));
                    if(!empty($res_purse)){
                        $where = array('id'=>$res_purse['id']);
                        $m_userpurse->updateData($where,array('money'=>$money+$res_purse['money'],'update_time'=>date('Y-m-d H:i:s')));
                    }else{
                        $m_userpurse->add(array('openid'=>$openid,'money'=>$money));
                    }
                }

                if(!empty($admin_openid) && !empty($admin_money)){
                    $m_message = new \Common\Model\Smallapp\MessageModel();
                    $m_message->recordMessage($admin_openid,$order_id,12);
                    $data = array('order_id'=>$order_id,'openid'=>$admin_openid,'distribution_user_id'=>$res_duser['parent_id'],'money'=>$admin_money,'pay_status'=>3);
                    $m_ordersettlement->add($data);

                    $res_purse = $m_userpurse->getInfo(array('openid'=>$admin_openid));
                    if(!empty($res_purse)){
                        $where = array('id'=>$res_purse['id']);
                        $m_userpurse->updateData($where,array('money'=>$admin_money+$res_purse['money'],'update_time'=>date('Y-m-d H:i:s')));
                    }else{
                        $m_userpurse->add(array('openid'=>$admin_openid,'money'=>$admin_money));
                    }
                }

            }else{
                $m_exchange = new \Common\Model\Smallapp\ExchangeModel();
                $m_baseinc = new \Payment\Model\BaseIncModel();
                $m_wxpay = new \Payment\Model\WxpayModel();
                $m_ordersettlement = new \Common\Model\Smallapp\OrdersettlementModel();
                $m_paylog = new \Common\Model\Smallapp\PaylogModel();
                if($money>0){
                    $add_data = array('openid'=>$openid,'goods_id'=>0,'order_id'=>$order_id,'price'=>0,'type'=>6,
                        'amount'=>$order_amount,'total_fee'=>$money,'status'=>20);
                    $order_exchange_id = $m_exchange->add($add_data);

                    $payconfig = $m_baseinc->getPayConfig();
                    $trade_info = array('trade_no'=>$order_exchange_id,'money'=>$money,'open_id'=>$openid);
                    $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
                    $pay_status = 2;
                    if($res['code']==10000){
                        $pay_status = 1;
                        $m_exchange->updateData(array('id'=>$order_exchange_id),array('status'=>21));
                    }
                    $data = array('order_id'=>$order_id,'openid'=>$openid,'distribution_user_id'=>$res_duser['id'],'money'=>$money,'pay_status'=>$pay_status);
                    $m_ordersettlement->add($data);

                    $pay_data = array('order_id'=>$order_id,'openid'=>$openid,'wxorder_id'=>$order_exchange_id,'pay_result'=>json_encode($res));
                    $m_paylog->add($pay_data);
                }

                if(!empty($admin_openid) && !empty($admin_money)){
                    $m_message = new \Common\Model\Smallapp\MessageModel();
                    $m_message->recordMessage($admin_openid,$order_id,12);

                    $add_data = array('openid'=>$admin_openid,'goods_id'=>0,'order_id'=>$order_id,'price'=>0,'type'=>6,
                        'amount'=>1,'total_fee'=>$admin_money,'status'=>20);
                    $order_exchange_id = $m_exchange->add($add_data);

                    $trade_info = array('trade_no'=>$order_exchange_id,'money'=>$admin_money,'open_id'=>$admin_openid);
                    $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
                    $pay_status = 2;
                    if($res['code']==10000){
                        $pay_status = 1;
                        $m_exchange->updateData(array('id'=>$order_exchange_id),array('status'=>21));
                    }
                    $data = array('order_id'=>$order_id,'openid'=>$admin_openid,'distribution_user_id'=>$res_duser['parent_id'],'money'=>$admin_money,'pay_status'=>$pay_status);
                    $m_ordersettlement->add($data);
                    $pay_data = array('order_id'=>$order_id,'openid'=>$admin_openid,'wxorder_id'=>$order_exchange_id,'pay_result'=>json_encode($res));
                    $m_paylog->add($pay_data);
                }
            }

            $m_order->updateData(array('id'=>$order_id),array('is_settlement'=>1,'buy_type'=>$buy_type));
        }else{
            $log_content = date("Y-m-d H:i:s").'[order_id]'.$orders[0]['order_id'].'[sale_uid]'.$res_order['sale_uid'].'[status]'.$res_order['status'].'[is_settlement]'.$res_order['is_settlement']."\n";
            $this->record_log($log_content);
        }
    }

    public function prizemoney(){
        $content = file_get_contents('php://input');
        $orders = array();
        if(!empty($content)) {
            $res = json_decode($content, true);
            if (!empty($res['Message'])) {
                $message = base64_decode($res['Message']);
                $orders = json_decode($message,true);
            }
        }
        if(empty($orders[0]['order_id'])){
            $now_time = date('Y-m-d H:i:s');
            die($now_time.' error');
        }

        $params = explode('_',$orders[0]['order_id']);
        $prizepool_prize_id = intval($params[0]);
        $apply_id = intval($params[1]);
        if($prizepool_prize_id<=0 || $apply_id<=0){
            $now_time = date('Y-m-d H:i:s');
            die($now_time.' error');
        }
        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        $res_apply = $m_activityapply->getInfo(array('id'=>$apply_id));
        if(empty($res_apply)){
            $now_time = date('Y-m-d H:i:s');
            die($now_time.' error');
        }

        $money_queue = C('SAPP_PRIZEPOOL_MONEYQUEUE').$prizepool_prize_id;
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(1);
        $money = $redis->lpop($money_queue);
        if(empty($money)){
            $now_time = date('Y-m-d H:i:s');
            die($now_time.' error');
        }
        $openid = $res_apply['openid'];
        $m_order = new \Common\Model\Smallapp\ExchangeModel();
        $add_data = array('openid'=>$openid,'goods_id'=>0,'price'=>0,'type'=>4,
            'amount'=>1,'total_fee'=>$money,'status'=>20);
        $order_id = $m_order->add($add_data);

        $m_baseinc = new \Payment\Model\BaseIncModel();
        $payconfig = $m_baseinc->getPayConfig();
        $m_wxpay = new \Payment\Model\WxpayModel();

        $trade_info = array('trade_no'=>$order_id,'money'=>$money,'open_id'=>$openid);
        $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
        if($res['code']==10000){
            echo "order_id:$order_id  prizemoney ok"."\r\n";
        }

    }

    public function grouporderincome(){
        $now_time = date('Y-m-d H:i:s');
        $ts = I('get.ts','');
        if($ts!='yqQGxlth10z2EJ1A4M'){
            die($now_time.' error');
        }
        $hour = date('G');
        if($hour!=12){
            die($now_time.' hour error');
        }
        $nowdtime = date('Y-m-d H:i:s');
        echo $nowdtime." start\r\n";
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $where = array('otype'=>8,'pay_type'=>10,'status'=>53,'is_settlement'=>0);
        $where['add_time'] = array('egt',$this->order_start_time);

        $res_order = $m_order->getDataList('*',$where,'id asc');
        if(!empty($res_order)){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
            $m_usertask = new \Common\Model\Integral\TaskuserModel();
            $m_hotel = new \Common\Model\HotelModel();
            $m_staff = new \Common\Model\Integral\StaffModel();
            $m_userintegralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();

            $m_orderserial = new \Common\Model\Smallapp\OrderserialModel();
            $m_baseinc = new \Payment\Model\BaseIncModel();
            $payconfig = $m_baseinc->getPayConfig(5);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $m_ordersettlement = new \Common\Model\Smallapp\OrdersettlementModel();
            foreach ($res_order as $v){
                $order_id=$v['id'];
                $task_user_id = $v['task_user_id'];
                $res_orderserial = $m_orderserial->getInfo(array('trade_no'=>$order_id));
                if($v['sale_uid']==0 || $task_user_id==0 || empty($res_orderserial)){
                    continue;
                }
                $res_user = $m_user->getOne('*',array('id'=>$v['sale_uid']),'');
                $tfields = "a.openid,a.integral as uintegral,task.id task_id,task.goods_id,task.integral,task.money";
                $res_usertask = $m_usertask->getUserTaskList($tfields,array('a.id'=>$task_user_id),'a.id desc');
                if(!empty($res_user) && !empty($res_usertask)){
                    $payee_openid=$res_user['openid'];
                    $money=$res_usertask[0]['money'];
                    $integral=$res_usertask[0]['integral'];

                    $trade_info = array('trade_no'=>$order_id,'money'=>$money,'open_id'=>$payee_openid);
                    $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
                    if($res['code']==10000){
                        $condition = array('id'=>$order_id);
                        $m_order->updateData($condition,array('is_settlement'=>1));
                        $data = array('order_id'=>$order_id,'openid'=>$payee_openid,'money'=>$money);
                        $m_ordersettlement->add($data);

                        $staffwhere = array('a.openid'=>$payee_openid,'a.status'=>1,'merchant.status'=>1);
                        $field_staff = 'a.id as staff_id,merchant.id as merchant_id,merchant.hotel_id,merchant.is_integral';
                        $res_staff = $m_staff->getMerchantStaff($field_staff,$staffwhere);
                        if(!empty($res_staff)){
                            if($res_staff[0]['is_integral']==1){
                                $integralrecord_openid = $payee_openid;
                                $m_usertask->updateData(array('id'=>$task_user_id),array('integral'=>$integral+$res_usertask[0]['uintegral']));
                                $res_integral = $m_userintegral->getInfo(array('openid'=>$payee_openid));
                                if(!empty($res_integral)){
                                    $m_userintegral->updateData(array('id'=>$res_integral['id']),array('integral'=>$integral+$res_integral['integral'],'update_time'=>date('Y-m-d H:i:s')));
                                }else{
                                    $m_userintegral->addData(array('openid'=>$payee_openid,'integral'=>$integral));
                                }
                            }else{
                                $integralrecord_openid = $res_staff[0]['hotel_id'];
                                $m_merchant = new \Common\Model\Integral\MerchantModel();
                                $where = array('id'=>$res_staff[0]['merchant_id']);
                                $m_merchant->where($where)->setInc('integral',$integral);
                            }

                            $res_hotel = $m_hotel->getHotelInfoById($res_staff[0]['hotel_id']);
                            $integralrecord_data = array('openid'=>$integralrecord_openid,'area_id'=>$res_hotel['area_id'],'area_name'=>$res_hotel['area_name'],
                                'hotel_id'=>$res_staff[0]['hotel_id'],'hotel_name'=>$res_hotel['hotel_name'],'hotel_box_type'=>$res_hotel['hotel_box_type'],
                                'integral'=>$integral,'goods_id'=>$res_usertask[0]['goods_id'],'jdorder_id'=>$order_id,'type'=>14,
                                'integral_time'=>date('Y-m-d H:i:s'));
                            $m_userintegralrecord->add($integralrecord_data);

                            $integralrecord_data['openid'] = $payee_openid;
                            $integralrecord_data['integral'] = 0;
                            $integralrecord_data['money'] = $money;
                            $m_userintegralrecord->add($integralrecord_data);
                        }
                        echo "order_id:$order_id  settlement ok"."\r\n";
                    }else{
                        echo "order_id:$order_id  settlement error ".json_encode($res)."\r\n";
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

    public function handgiftrefund(){
        $order_id = I('oid',0,'intval');
        $nowdtime = date('Y-m-d H:i:s');
        echo $nowdtime." start\r\n";
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $where = array('id'=>$order_id);
        $res_order = $m_order->getDataList('*',$where,'id asc');
        if(!empty($res_order)){
            $diff_time = 86400*5;

            $m_orderserial = new \Common\Model\Smallapp\OrderserialModel();
            $m_baseinc = new \Payment\Model\BaseIncModel();
            $payconfig = $m_baseinc->getPayConfig(2);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $m_ordermap = new \Common\Model\Smallapp\OrdermapModel();
            $m_user = new \Common\Model\Smallapp\UserModel();
            foreach ($res_order as $v){
                $order_id=$v['id'];
                $now_time = time();
                $order_time = strtotime($v['add_time']);
                if($now_time-$order_time<$diff_time){
                    continue;
                }
                $p_oid = $v['gift_oid'];
                $p_id = 0;
                $pay_fee = 0;
                while ($p_oid>0){
                    $tmp_order = $m_order->getInfo(array('id'=>$p_oid));
                    if(!empty($tmp_order)){
                        $p_oid = $tmp_order['gift_oid'];
                        $p_id = $tmp_order['id'];
                        $pay_fee = $tmp_order['pay_fee'];
                    }
                }
                $res_user = $m_user->getOne('nickName',array('openid'=>$tmp_order['openid']),'');

                echo "original_order_id:{$tmp_order['id']},user:{$res_user['nickName']},pay_money:{$tmp_order['pay_fee']},pay_time:{$tmp_order['pay_time']} \r\n";

                $res_orderserial = $m_orderserial->getInfo(array('trade_no'=>$p_id));
                if(!empty($res_orderserial)){
                    $res_ordermap = $m_ordermap->getDataList('id',array('order_id'=>$p_id),'id desc',0,1);
                    $trade_no = $res_ordermap['list'][0]['id'];

                    $refund_money = $v['total_fee'];
                    if($refund_money<=0){
                        echo "order_id:$order_id  cancel error money:$refund_money"."\r\n";
                        continue;
                    }

                    $trade_info = array('trade_no'=>$trade_no,'batch_no'=>$res_orderserial['serial_order'],'pay_fee'=>$pay_fee,'refund_money'=>$refund_money);
                    echo "refund_info:".json_encode($trade_info)." \r\n";


                    $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                    if(isset($res['err_code'])){
                        $payconfig = $m_baseinc->getPayConfigOld(2);
                        $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                    }
                    if($res["return_code"]=="SUCCESS" && $res["result_code"]=="SUCCESS" && !isset($res['err_code'])){
                        $data = array('status'=>62,'finish_time'=>date('Y-m-d H:i:s'));
                        $where = array('id'=>$v['id']);
                        $m_order->updateData($where,$data);
                        echo "order_id:$order_id  cancel and refund ok"."\r\n";

                    }else{
                        echo "order_id:$order_id  cancel and refund error"."\r\n";
                    }

                }else{
                    echo "order_id:$order_id  cancel error"."\r\n";
                }

            }
        }
        echo $nowdtime." finish\r\n";
    }

    public function sellwinefailmoney(){
        $now_time = date('Y-m-d H:i:s');
        echo "sellwinefailmoney start_time:$now_time \r\n";

        $fail_date = date('Y-m-d H:00:00',strtotime("-1 hour" ));
        $m_sellwine_redpacket = new \Common\Model\Smallapp\SellwineActivityRedpacketModel();
        $where = array('status'=>array('in','12,21,22'));//状态11领取成功,12领取失败,21发送成功,22发送失败,23已抢完
        $where['add_time'] = array('elt',$fail_date);
        $res_data = $m_sellwine_redpacket->getDataList('*',$where,'id asc');
        if(!empty($res_data)){
            $smallapp_config = C('SMALLAPP_CONFIG');
            $pay_wx_config = C('PAY_WEIXIN_CONFIG_1594752111');
            $sslcert_path = APP_PATH.'Payment/Model/wxpay_lib/cert/1594752111_apiclient_cert.pem';
            $sslkey_path = APP_PATH.'Payment/Model/wxpay_lib/cert/1594752111_apiclient_key.pem';
            $payconfig = array(
                'appid'=>$smallapp_config['appid'],
                'partner'=>$pay_wx_config['partner'],
                'key'=>$pay_wx_config['key'],
                'sslcert_path'=>$sslcert_path,
                'sslkey_path'=>$sslkey_path,
            );
            $m_order = new \Common\Model\Smallapp\OrderModel();
            $m_paylog = new \Common\Model\Smallapp\PaylogModel();
            $m_exchange = new \Common\Model\Smallapp\ExchangeModel();
            $m_wxpay = new \Payment\Model\WxpayModel();
            $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
            $m_redpacket_receive = new \Common\Model\Smallapp\RedpacketReceiveModel();
            $m_user = new \Common\Model\Smallapp\UserModel();
            foreach ($res_data as $v){
                $activity_redpacket_id = $v['id'];
                $order_id = $v['order_id'];
                $total_fee = intval($v['money']);
                $openid = $v['openid'];
                if($v['type']==10){//10微信零钱,20电视红包
                    $ewhere = array('openid'=>$openid,'order_id'=>$order_id,'type'=>6);
                    $res_exchange = $m_exchange->getInfo($ewhere);
                    if(!empty($res_exchange)){
                        $order_exchange_id = $res_exchange['id'];
                    }else{
                        $add_data = array('openid'=>$openid,'goods_id'=>0,'order_id'=>$order_id,'price'=>0,'type'=>6,
                            'amount'=>1,'total_fee'=>$total_fee,'status'=>20);
                        $order_exchange_id = $m_exchange->add($add_data);
                    }
                    $trade_info = array('trade_no'=>$order_exchange_id,'money'=>$total_fee,'open_id'=>$openid);
                    $res_pay = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
                    if($res_pay['code']==10000){
                        $m_exchange->updateData(array('id'=>$order_exchange_id),array('status'=>21));
                        $m_sellwine_redpacket->updateData(array('id'=>$activity_redpacket_id),array('status'=>11,'update_time'=>date('Y-m-d H:i:s')));
                    }
                    $res_order = $m_order->getInfo(array('id'=>$v['order_id']));
                    $pay_result = json_encode($res_pay);
                    $pay_data = array('order_id'=>$order_id,'openid'=>$openid,'idcode'=>$res_order['idcode'],
                        'wxorder_id'=>$order_exchange_id,'pay_result'=>$pay_result);
                    $m_paylog->add($pay_data);

                    echo "activity_redpacket_id:$activity_redpacket_id payresult:$pay_result \r\n";
                }elseif($v['type']==20){
                    $res_redpacket = $m_redpacket->getInfo(array('order_id'=>$order_id,'operate_type'=>3));
                    $redpacket_id = $res_redpacket['id'];
                    $fields = 'a.id,a.redpacket_id,a.user_id,a.money,a.status,user.openid';
                    $where = "a.redpacket_id=$redpacket_id";
                    $res_receive = $m_redpacket_receive->getList($fields,$where,'a.id asc');
                    $receive_money = 0;
                    $is_up = 1;
                    if(!empty($res_receive)){
                        foreach ($res_receive as $rv){
                            $receive_money+=$rv['money'];
                            if($rv['status']==0){
                                $trade_info = array('trade_no'=>$rv['redpacket_id'],'money'=>$rv['money'],'open_id'=>$rv['openid']);
                                $res_rpay = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
                                if($res_rpay['code']==10000){
                                    $condition = array('id'=>$rv['id']);
                                    $m_redpacket_receive->updateData($condition,array('status'=>1,'receive_time'=>date('Y-m-d H:i:s')));
                                }else{
                                    $is_up = 0;
                                }
                                $pay_result = json_encode($res_rpay);
                                echo "activity_redpacket_id:$activity_redpacket_id,redpacket_id:$redpacket_id,receive_id:{$rv['id']} payresult:$pay_result \r\n";
                            }
                        }
                    }
                    $no_get_money = $total_fee-$receive_money;
                    if($no_get_money>0){
                        $m_redpacket->updateData(array('id'=>$redpacket_id),array('status'=>5,'grab_time'=>date('Y-m-d H:i:s')));

                        $trade_info = array('trade_no'=>$redpacket_id,'money'=>$no_get_money,'open_id'=>$openid);
                        $res_rpay = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
                        $res_user = $m_user->getOne('id',array('openid'=>$openid),'');
                        $receive_data = array('redpacket_id'=>$redpacket_id,'user_id'=>$res_user['id'],'money'=>$no_get_money,'operate_type'=>1);
                        if($res_rpay['code']==10000){
                            $receive_data['status'] = 1;
                            $receive_data['receive_time'] = date('Y-m-d H:i:s');
                        }else{
                            $is_up = 0;
                        }
                        $receive_id = $m_redpacket_receive->add($receive_data);
                        $pay_result = json_encode($res_rpay);
                        echo "activity_redpacket_id:$activity_redpacket_id,redpacket_id:$redpacket_id,receive_id:{$receive_id} payresult:$pay_result \r\n";
                    }
                    if($is_up==1){
                        $m_sellwine_redpacket->updateData(array('id'=>$activity_redpacket_id),array('status'=>23,'update_time'=>date('Y-m-d H:i:s')));
                    }
                }

            }
        }
    }

    private function record_log($log_content){
        $log_file_name = APP_PATH.'Runtime/Logs/'.'saleorder_'.date("Ymd").".log";
        @file_put_contents($log_file_name, $log_content, FILE_APPEND);
        return true;
    }
}