<?php
namespace Payment\Model;
use Think\Model;
class BaseIncModel extends Model{

    protected $tableName='smallapp_redpacket';

    /**
     * 获取支付信息
     * @param array $trade_info
     * @return array
     */
    public function init_pay_tradeinfo($trade_info=array()){
        $pay_trade_info = array();
        if(!empty($trade_info)){
            $pay_trade_info = array('out_trade_no'=> $trade_info['trade_no'],'total_fee'=> $trade_info['total_fee'],
                'subject'=>$trade_info['trade_name'],'body'=>$trade_info['trade_name'],'buy_time'=>$trade_info['buy_time'],
            	'redirect_url'=>$trade_info['redirect_url'],
            );
            if(isset($trade_info['notify_url'])){
                $pay_trade_info['notify_url'] = $trade_info['notify_url'];
            }
            if(isset($trade_info['wx_openid'])){
            	$pay_trade_info['wx_openid'] = $trade_info['wx_openid'];
            }
            if(isset($trade_info['attach'])){
                $pay_trade_info['attach'] = $trade_info['attach'];
            }
        }
        return $pay_trade_info;
    }
    
    /**
     * 获取支付配置信息
     * @param array $payconfig
     * @return array
     */
    public function init_pay_config($payconfig){
        $payinfo = array(
            'appid'=>$payconfig['appid'],
            'partner'=>$payconfig['partner'],
            'key'=>$payconfig['key'],
            'seller_email'=>$payconfig['seller_email'],
            'sslcert_path'=>'',
            'sslkey_path'=>'',
        );
        if(isset($payconfig['sslcert_path']) && isset($payconfig['sslkey_path'])){
            $payinfo['sslcert_path'] = $payconfig['sslcert_path'];
            $payinfo['sslkey_path'] = $payconfig['sslkey_path'];
        }

        return $payinfo;
    }
    
    /**
     * 更新红包订单支付数据
     * @param array $order_extend 订单扩展信息
     * array('trade_no'=>订单号,
     'serial_no'=>流水号,
     'pay_fee'=>支付金额,
     'paylog_type'=>支付类型,
     'pay_type'=>支付方式(10微信)
     * @return boolean
     */
    public function handle_redpacket_notify($order_extend){
        $is_succ = false;
        $trade_no = $order_extend['trade_no'];
        $serial_no = $order_extend['serial_no'];
        $pay_fee = $order_extend['pay_fee'];
        $paylog_type = $order_extend['paylog_type'];
        $pay_type = $order_extend['pay_type'];
    
    
        $sql_order = "select * from savor_smallapp_redpacket where id='$trade_no'";
        $this->paynotify_log($paylog_type, $serial_no, $sql_order);
        $result_order = $this->query($sql_order);
        if(in_array($result_order[0]['status'],array(0,1,2))){
            // 判断订单支付金额是否正常
            $tmp_no_pay_fee = $result_order[0]['total_fee']-$result_order[0]['pay_fee']-$pay_fee;
            $no_pay_fee = sprintf("%01.2f",$tmp_no_pay_fee);
    
            if($no_pay_fee<=0){
                $status = 4;
            }else{
                $status = 3;
            }

            $rate = 0;
            $redis  =  \Common\Lib\SavorRedis::getInstance();
            $redis->select(12);
            $cache_key = C('SYSTEM_CONFIG');
            $redis_sys_config = $redis->get($cache_key);
            if(!empty($redis_sys_config)){
                $redis_sys_config = json_decode($redis_sys_config,true);
                foreach($redis_sys_config as $key=>$v){
                    if($v['config_key']=='red_packet_rate'){
                        $rate = $v['config_value'];
                        break;
                    }
                }
            }


            if($rate){
                $rate_fee = sprintf("%01.2f",$pay_fee*$rate);
                $tmp_money = $result_order[0]['total_fee'] - $rate_fee;
                $redpacket_money = sprintf("%01.2f",$tmp_money);
            }else{
                $redpacket_money = $result_order[0]['total_fee'];
                $rate_fee = 0;
            }

            $pay_time = date('Y-m-d H:i:s');
            $update_condition = "update savor_smallapp_redpacket set status='$status',pay_time='$pay_time',pay_fee='$pay_fee',pay_type='$pay_type',rate_fee='$rate_fee',rate='$rate' ";
            $sql_uporder = "$update_condition where id='$trade_no'";
            $this->paynotify_log($paylog_type, $serial_no, $sql_uporder);
            $row_num = $this->execute($sql_uporder);
            if($row_num){
                $is_succ = true;
                $sql_serialno = "INSERT INTO `savor_smallapp_orderserial` (`trade_no`,`serial_order`,`goods_id`,`pay_type`)VALUES ($trade_no,'$serial_no',0,$pay_type)";
                $this->execute($sql_serialno);
                $this->paynotify_log($paylog_type, $serial_no, $sql_serialno);
                if($status == 4){
                    //根据红包总金额和人数进行分配红包
                    $money = $redpacket_money;
                    $num = $result_order[0]['amount'];
                    $all_money = bonus_random($money,$num,0.3,$money);

                    $redis  =  \Common\Lib\SavorRedis::getInstance();
                    $redis->select(5);
                    $key = C('SAPP_REDPACKET').$trade_no.':bonus';
                    $all_moneys = array('unused'=>$all_money,'used'=>array());
                    $redis->set($key,json_encode($all_moneys),86400);
                    $key_queue = C('SAPP_REDPACKET').$trade_no.':bonusqueue';
                    foreach ($all_money as $mv){
                        $redis->rpush($key_queue,$mv);
                    }

                    $log_content = '订单号:'.$trade_no.' 发红包为:'.json_encode($all_money)." 费率:$rate 费率金额:$rate_fee".' 总金额:'.array_sum($all_money);
                    $this->paynotify_log($paylog_type, $serial_no, $log_content);
                    //end

                    //推送红包小程序码到电视
                    $http_host = http_host();
                    $m_user = new \Common\Model\Smallapp\UserModel();
                    $m_netty = new \Common\Model\NettyModel();

                    $box_mac = $result_order[0]['mac'];
                    $qrinfo =  $trade_no.'_'.$box_mac;
                    $mpcode = $http_host.'/h5/qrcode/mpQrcode?qrinfo='.$qrinfo;
                    $where = array('id'=>$result_order[0]['user_id']);
                    $user_info = $m_user->getOne('*',$where,'');
                    $head_pic = '';
                    if(!empty($user_info['avatarUrl'])){
                        $head_pic = base64_encode($user_info['avatarUrl']);
                    }
                    $message = array('action'=>121,'nickName'=>$user_info['nickName'],
                        'headPic'=>$head_pic,'avatarUrl'=>$user_info['avatarUrl'],'codeUrl'=>$mpcode);

                    $m_box = new \Common\Model\BoxModel();
                    $bwhere = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
                    $res_box = $m_box->getBoxInfo('a.is_4g',$bwhere);
                    if(!empty($res_box) && $res_box[0]['is_4g']==1){
                        $message['avatarUrl'] = '';
                    }

                    $m_netty->pushBox($result_order[0]['mac'],json_encode($message));

                    $log_content = '订单号:'.$trade_no.' 当前包间红包小程序码:'.$mpcode;
                    $this->paynotify_log($paylog_type, $serial_no, $log_content);

                    //发送范围 1全网餐厅电视,2当前餐厅所有电视,3当前包间电视
                    $scope = $result_order[0]['scope'];
                    if(in_array($scope,array(1,2))){

                        //发全网红包
                        $all_box = $m_netty->getPushBox(2,$box_mac);
                        if(!empty($all_box)){
                            foreach ($all_box as $v){
                                $qrinfo =  $trade_no.'_'.$v;
                                $mpcode = $http_host.'/h5/qrcode/mpQrcode?qrinfo='.$qrinfo;
                                $head_pic = '';
                                if(!empty($user_info['avatarUrl'])){
                                    $head_pic = base64_encode($user_info['avatarUrl']);
                                }
                                $message = array('action'=>121,'nickName'=>$user_info['nickName'],
                                    'headPic'=>$head_pic,'avatarUrl'=>$user_info['avatarUrl'],'codeUrl'=>$mpcode);

                                $bwhere = array('a.mac'=>$v,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
                                $res_box = $m_box->getBoxInfo('a.is_4g',$bwhere);
                                if(!empty($res_box) && $res_box[0]['is_4g']==1){
                                    $message['avatarUrl'] = '';
                                }
                                $m_netty->pushBox($v,json_encode($message));
                            }
                        }
                        if($scope == 1){
                            $key = C('SAPP_REDPACKET').'smallprogramcode';
                            $res_data = array('order_id'=>$trade_no,'add_time'=>$result_order[0]['add_time'],'box_list'=>$all_box,
                                'nickName'=>$user_info['nickName'],'avatarUrl'=>$user_info['avatarUrl']);
                            $redis->set($key,json_encode($res_data));
                        }

                        //北京发红包只能发当前包间
//                        $m_box = new \Common\Model\BoxModel();
//                        $res = $m_box->getHotelInfoByBoxMacNew($box_mac);
//                        if($res['area_id']==1){
//                            if($scope == 1){
//                                $all_box = $m_netty->getPushBox(2,$box_mac);
//                                $key = C('SAPP_REDPACKET').'smallprogramcode';
//                                $res_data = array('order_id'=>$trade_no,'add_time'=>$result_order[0]['add_time'],'box_list'=>$all_box,
//                                    'nickName'=>$user_info['nickName'],'avatarUrl'=>$user_info['avatarUrl']);
//                                $redis->set($key,json_encode($res_data));
//                            }
//                        }else{
//                            $all_box = $m_netty->getPushBox(2,$box_mac);
//                            if(!empty($all_box)){
//                                foreach ($all_box as $v){
//                                    $qrinfo =  $trade_no.'_'.$v;
//                                    $mpcode = $http_host.'/h5/qrcode/mpQrcode?qrinfo='.$qrinfo;
//                                    $message = array('action'=>121,'nickName'=>$user_info['nickName'],
//                                        'avatarUrl'=>$user_info['avatarUrl'],'codeUrl'=>$mpcode);
//                                    $m_netty->pushBox($v,json_encode($message));
//                                }
//                            }
//                            if($scope == 1){
//                                $key = C('SAPP_REDPACKET').'smallprogramcode';
//                                $res_data = array('order_id'=>$trade_no,'add_time'=>$result_order[0]['add_time'],'box_list'=>$all_box,
//                                    'nickName'=>$user_info['nickName'],'avatarUrl'=>$user_info['avatarUrl']);
//                                $redis->set($key,json_encode($res_data));
//                            }
//                        }
                    }
                    //end
                }
            }
        }else{
            $is_succ = true;
        }
        return $is_succ;
    }

    /**
     * 更新订单支付数据
     * @param array $order_extend 订单扩展信息
     * array('trade_no'=>订单号,
    'serial_no'=>流水号,
    'pay_fee'=>支付金额,
    'paylog_type'=>支付类型,
    'pay_type'=>支付方式(10微信)
     * @return boolean
     */
    public function handle_order_notify($order_extend){
        $is_succ = false;
        $trade_no = $order_extend['trade_no'];
        $serial_no = $order_extend['serial_no'];
        $pay_fee = $order_extend['pay_fee'];
        $paylog_type = $order_extend['paylog_type'];
        $pay_type = $order_extend['pay_type'];

        $sql_order = "select * from savor_smallapp_ordermap where id='$trade_no'";
        $this->paynotify_log($paylog_type, $serial_no, $sql_order);
        $result_ordermap = $this->query($sql_order);

        $trade_no = intval($result_ordermap[0]['order_id']);
        $sql_order = "select * from savor_smallapp_order where id='$trade_no'";
        $this->paynotify_log($paylog_type, $serial_no, $sql_order);
        $result_order = $this->query($sql_order);
        if(in_array($result_order[0]['status'],array(10,11))){//10已下单 11支付失败 12支付成功
            // 判断订单支付金额是否正常
            $tmp_no_pay_fee = $result_order[0]['total_fee']-$result_order[0]['pay_fee']-$pay_fee;
            $no_pay_fee = sprintf("%01.2f",$tmp_no_pay_fee);

            if($no_pay_fee<=0){
                $status = 12;
            }else{
                $status = 11;
            }
            $pay_time = date('Y-m-d H:i:s');
            $update_condition = "update savor_smallapp_order set status='$status',pay_time='$pay_time',pay_fee='$pay_fee',pay_type='$pay_type' ";
            $sql_uporder = "$update_condition where id='$trade_no'";
            $this->paynotify_log($paylog_type, $serial_no, $sql_uporder);
            $row_num = $this->execute($sql_uporder);
            if($row_num){
                $sale_uid = $result_order[0]['sale_uid'];
                if($sale_uid){
                    $m_box = new \Common\Model\BoxModel();
                    $res_box = $m_box->getHotelInfoByBoxMacNew($result_order[0]['box_mac']);
                    $m_user = new \Common\Model\Smallapp\UserModel();
                    $res_user = $m_user->getOne('openid',array('id'=>$sale_uid),'id desc');
                    $m_user_integralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
                    $record_data = array('openid'=>$res_user['openid'],'area_id'=>$res_box['area_id'],'area_name'=>$res_box['area_name'],
                        'hotel_id'=>$res_box['hotel_id'],'hotel_name'=>$res_box['hotel_name'],'hotel_box_type'=>$res_box['hotel_box_type'],
                        'room_id'=>$res_box['room_id'],'room_name'=>$res_box['room_name'],'box_id'=>$res_box['box_id'],'box_mac'=>$result_order[0]['box_mac'],
                        'box_type'=>$res_box['box_type'],'integral'=>0,'goods_id'=>$result_order[0]['goods_id'],
                        'jdorder_id'=>$result_order[0]['id'],'content'=>$result_order[0]['amount'],'type'=>3,
                        'integral_time'=>date('Y-m-d H:i:s'),'status'=>1);
                    $m_user_integralrecord->add($record_data);
                }

                $is_succ = true;
                $sql_serialno = "INSERT INTO `savor_smallapp_orderserial` (`trade_no`,`serial_order`,`goods_id`,`pay_type`)VALUES ($trade_no,'$serial_no',0,$pay_type)";
                $this->execute($sql_serialno);
                $this->paynotify_log($paylog_type, $serial_no, $sql_serialno);
            }
        }else{
            $is_succ = true;
        }
        return $is_succ;
    }

    public function handle_takeoutorder_notify($order_extend){
        $is_succ = false;
        $trade_no = $order_extend['trade_no'];
        $serial_no = $order_extend['serial_no'];
        $pay_fee = $order_extend['pay_fee'];
        $paylog_type = $order_extend['paylog_type'];
        $pay_type = $order_extend['pay_type'];

        $sql_order = "select * from savor_smallapp_ordermap where id='$trade_no'";
        $this->paynotify_log($paylog_type, $serial_no, $sql_order);
        $result_ordermap = $this->query($sql_order);

        $trade_no = intval($result_ordermap[0]['order_id']);
        $sql_order = "select * from savor_smallapp_order where id='$trade_no'";
        $this->paynotify_log($paylog_type, $serial_no, $sql_order);
        $result_order = $this->query($sql_order);
        if(in_array($result_order[0]['status'],array(10,11))){//10已下单 11支付失败 12支付成功
            // 判断订单支付金额是否正常
            $tmp_no_pay_fee = $result_order[0]['total_fee']-$result_order[0]['pay_fee']-$pay_fee;
            $no_pay_fee = sprintf("%01.2f",$tmp_no_pay_fee);

            if($no_pay_fee<=0){
                $status = 12;
            }else{
                $status = 11;
            }
            $pay_time = date('Y-m-d H:i:s');
            $update_condition = "update savor_smallapp_order set status='$status',pay_time='$pay_time',pay_fee='$pay_fee',pay_type='$pay_type' ";
            $sql_uporder = "$update_condition where id='$trade_no'";
            $this->paynotify_log($paylog_type, $serial_no, $sql_uporder);
            $row_num = $this->execute($sql_uporder);
            if($row_num){
                $is_succ = true;
                $sql_serialno = "INSERT INTO `savor_smallapp_orderserial` (`trade_no`,`serial_order`,`goods_id`,`pay_type`)VALUES ($trade_no,'$serial_no',0,$pay_type)";
                $this->execute($sql_serialno);
                $this->paynotify_log($paylog_type, $serial_no, $sql_serialno);

                $m_order = new \Common\Model\Smallapp\OrderModel();
                $is_notify_merchant = $m_order->sendMessage($trade_no);
                $log_notify = "order_id:$trade_no sms notify merchant status:".$is_notify_merchant;
                $this->paynotify_log($paylog_type, $serial_no, $log_notify);
                $is_notify_merchant = 1;
                if($is_notify_merchant){
                    $m_order->updateData(array('id'=>$trade_no),array('status'=>13));
                }
            }
        }else{
            $is_succ = true;
        }
        return $is_succ;
    }

    public function handle_shoporder_notify($order_extend){
        $is_succ = false;
        $trade_no = $order_extend['trade_no'];
        $serial_no = $order_extend['serial_no'];
        $pay_fee = $order_extend['pay_fee'];
        $paylog_type = $order_extend['paylog_type'];
        $pay_type = $order_extend['pay_type'];

        $sql_order = "select * from savor_smallapp_ordermap where id='$trade_no'";
        $this->paynotify_log($paylog_type, $serial_no, $sql_order);
        $result_ordermap = $this->query($sql_order);

        $trade_no = intval($result_ordermap[0]['order_id']);
        $sql_order = "select * from savor_smallapp_order where id='$trade_no'";
        $this->paynotify_log($paylog_type, $serial_no, $sql_order);
        $result_order = $this->query($sql_order);
        if(in_array($result_order[0]['status'],array(10,11))){//10已下单 11支付失败 12支付成功
            // 判断订单支付金额是否正常
            $tmp_no_pay_fee = $result_order[0]['total_fee']-$result_order[0]['pay_fee']-$pay_fee;
            $no_pay_fee = sprintf("%01.2f",$tmp_no_pay_fee);

            if($no_pay_fee<=0){
                $status = 12;
            }else{
                $status = 11;
            }
            $pay_time = date('Y-m-d H:i:s');
            $update_condition = "update savor_smallapp_order set status='$status',pay_time='$pay_time',pay_fee='$pay_fee',pay_type='$pay_type' ";
            $sql_uporder = "$update_condition where id='$trade_no'";
            $this->paynotify_log($paylog_type, $serial_no, $sql_uporder);
            $row_num = $this->execute($sql_uporder);
            if($row_num){
                $is_succ = true;
                $sql_serialno = "INSERT INTO `savor_smallapp_orderserial` (`trade_no`,`serial_order`,`goods_id`,`pay_type`)VALUES ($trade_no,'$serial_no',0,$pay_type)";
                $this->paynotify_log($paylog_type, $serial_no, $sql_serialno);
                $this->execute($sql_serialno);

                $otype = $result_order[0]['otype'];
                $this->paynotify_log($paylog_type, $serial_no,"order_id:$trade_no otype:$otype");
                $m_order = new \Common\Model\Smallapp\OrderModel();
                $order_ids = array();
                if($otype==127){
                    $son_order = $m_order->getDataList('*',array('parent_oid'=>$trade_no),'id asc');
                    $sql_sonorder = $m_order->getLastSql();
                    $this->paynotify_log($paylog_type, $serial_no, $sql_sonorder);
                    foreach ($son_order as $v){
                        $pay_fee = $v['total_fee'];
                        $oid = $v['id'];
                        $update_condition = "update savor_smallapp_order set status='51',pay_time='$pay_time',pay_fee='$pay_fee',pay_type='$pay_type' ";
                        $sql_uporder = "$update_condition where id='$oid'";
                        $this->paynotify_log($paylog_type, $serial_no, $sql_uporder);
                        $this->execute($sql_uporder);
                        $order_ids[] = $v;
                    }
                }else{
                    $order_ids[] = $result_order[0];
                }
                $m_income = new \Common\Model\Smallapp\UserincomeModel();
                $m_config = new \Common\Model\SysConfigModel();
                $res_config = $m_config->getAllconfig();
                $profit = $res_config['distribution_profit'];
                $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
                $laimao_seckill_goods_id = C('LAIMAO_SECKILL_GOODS_ID');
                $is_laimao = 0;
                foreach ($order_ids as $v){
                    $fields = 'og.goods_id,og.price,og.amount,goods.amount as gamount,goods.supply_price,goods.distribution_profit,goods.name';
                    $where = array('og.order_id'=>$v['id']);
                    $res_goods = $m_ordergoods->getOrdergoodsList($fields,$where,'og.id desc');
                    $income_data = array();
                    foreach ($res_goods as $gv){
                        $goods_id = $gv['goods_id'];
                        if($goods_id==$laimao_seckill_goods_id){
                            $is_laimao = 1;
                        }
                        $amount = $gv['gamount']-$gv['amount']>0?$gv['gamount']-$gv['amount']:0;
                        $upsql = "update savor_smallapp_dishgoods set amount=$amount";
                        if($amount==0){
                            $upsql.=",status=2";
                        }
                        $sql_goods = "$upsql where id=$goods_id ";
                        $this->paynotify_log($paylog_type, $serial_no, $sql_goods);
                        $this->execute($sql_goods);
                        if($v['sale_uid'] && $otype!=8){
                            if($gv['distribution_profit']>0){
                                $profit = $gv['distribution_profit'];
                            }
                            $income_fee = 0;
                            if($gv['price']>$gv['supply_price']){
                                $income_fee = ($gv['price']-$gv['supply_price'])*$profit*$gv['amount'];
                                $income_fee = sprintf("%.2f",$income_fee);
                            }
                            $total_fee = sprintf("%.2f",$gv['price']*$gv['amount']);
                            $income_data[] = array('user_id'=>$v['sale_uid'],'openid'=>$v['openid'],'order_id'=>$v['id'],
                                'goods_id'=>$gv['goods_id'],'price'=>$gv['price'],'supply_price'=>$gv['supply_price'],'amount'=>$gv['amount'],
                                'total_fee'=>$total_fee,'income_fee'=>$income_fee, 'profit'=>$profit
                            );
                        }
                    }
                    if(!empty($income_data)){
                        $m_income->addAll($income_data);
                        $sql_income = $m_income->getLastSql();
                    }else{
                        $sql_income = '';
                    }
                    $this->paynotify_log($paylog_type, $serial_no, "income:$sql_income");

                }

//                $is_notify_merchant = $m_order->sendMessage($trade_no);
//                $log_notify = "order_id:$trade_no sms notify merchant status:".$is_notify_merchant;
//                $this->paynotify_log($paylog_type, $serial_no, $log_notify);
                $is_notify_merchant = 1;
                if($is_notify_merchant){
                    $m_order->updateData(array('id'=>$trade_no),array('status'=>51));
                    $sql_uporder = $m_order->getLastSql();
                    $this->paynotify_log($paylog_type, $serial_no, $sql_uporder);
                }
                $m_message = new \Common\Model\Smallapp\MessageModel();
                $m_message->recordMessage($result_order[0]['openid'],$trade_no,5);
                if($is_laimao==1){
                    $ucconfig = C('ALIYUN_SMS_CONFIG');
                    $alisms = new \Common\Lib\AliyunSms();
                    $template_code = $ucconfig['send_laimao_orderpay_templateid'];
                    $sql_orderlocaltion = "select * from savor_smallapp_orderlocation where order_id='$trade_no'";
                    $this->paynotify_log($paylog_type, $serial_no, $sql_orderlocaltion);
                    $result_orderlocation = $this->query($sql_order);
                    $order_location = $result_orderlocation[0];
                    $params = array('hotel_name'=>$order_location['hotel_name'],'room_name'=>$order_location['room_name'],'amount'=>$result_order[0]['amount'],'order_id'=>$trade_no);
                    $alisms::sendSms(13811966726,$params,$template_code);
                }
                if($otype==8 && $result_order[0]['sale_uid'] && $result_order[0]['task_user_id']){
                    $sms_config = C('ALIYUN_SMS_CONFIG');
                    $alisms = new \Common\Lib\AliyunSms();
                    $template_code = $sms_config['send_groupbuy_user_templateid'];
                    $send_mobiles = C('GROUP_BUY_USER_MOBILE');
                    if(!empty($send_mobiles)){
                        foreach ($send_mobiles as $v){
                            $alisms::sendSms($v,'',$template_code);
                        }
                    }
                    $template_code = $sms_config['send_groupbuy_saleuser_templateid'];
                    $m_user = new \Common\Model\Smallapp\UserModel();
                    $res_user = $m_user->getOne('*',array('id'=>$result_order[0]['sale_uid']),'');
                    $params = array('uname'=>$result_order[0]['contact'],'name'=>$res_goods[0]['name'],'hour'=>48);
                    $alisms::sendSms($res_user['mobile'],$params,$template_code);
                }
            }
        }else{
            $is_succ = true;
        }
        return $is_succ;
    }

    public function handle_giftorder_notify($order_extend){
        $is_succ = false;
        $trade_no = $order_extend['trade_no'];
        $serial_no = $order_extend['serial_no'];
        $pay_fee = $order_extend['pay_fee'];
        $paylog_type = $order_extend['paylog_type'];
        $pay_type = $order_extend['pay_type'];

        $sql_order = "select * from savor_smallapp_ordermap where id='$trade_no'";
        $this->paynotify_log($paylog_type, $serial_no, $sql_order);
        $result_ordermap = $this->query($sql_order);

        $trade_no = intval($result_ordermap[0]['order_id']);
        $sql_order = "select * from savor_smallapp_order where id='$trade_no'";
        $this->paynotify_log($paylog_type, $serial_no, $sql_order);
        $result_order = $this->query($sql_order);
        if(in_array($result_order[0]['status'],array(10,11))){//10已下单 11支付失败 12支付成功
            // 判断订单支付金额是否正常
            $tmp_no_pay_fee = $result_order[0]['total_fee']-$result_order[0]['pay_fee']-$pay_fee;
            $no_pay_fee = sprintf("%01.2f",$tmp_no_pay_fee);

            if($no_pay_fee<=0){
                $status = 12;
            }else{
                $status = 11;
            }
            $pay_time = date('Y-m-d H:i:s');
            $update_condition = "update savor_smallapp_order set status='$status',pay_time='$pay_time',pay_fee='$pay_fee',pay_type='$pay_type' ";
            $sql_uporder = "$update_condition where id='$trade_no'";
            $this->paynotify_log($paylog_type, $serial_no, $sql_uporder);
            $row_num = $this->execute($sql_uporder);
            if($row_num){
                $is_succ = true;
                $sql_serialno = "INSERT INTO `savor_smallapp_orderserial` (`trade_no`,`serial_order`,`goods_id`,`pay_type`)VALUES ($trade_no,'$serial_no',0,$pay_type)";
                $this->paynotify_log($paylog_type, $serial_no, $sql_serialno);
                $this->execute($sql_serialno);

                $otype = $result_order[0]['otype'];
                $this->paynotify_log($paylog_type, $serial_no,"order_id:$trade_no otype:$otype");

                $m_income = new \Common\Model\Smallapp\UserincomeModel();
                $m_config = new \Common\Model\SysConfigModel();
                $res_config = $m_config->getAllconfig();
                $profit = $res_config['distribution_profit'];
                $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
                $fields = 'og.goods_id,og.price,og.amount,goods.amount as gamount,goods.supply_price,goods.distribution_profit';
                $where = array('og.order_id'=>$trade_no);
                $res_goods = $m_ordergoods->getOrdergoodsList($fields,$where,'og.id desc');
                $income_data = array();
                foreach ($res_goods as $gv){
                    $goods_id = $gv['goods_id'];
                    $amount = $gv['gamount']-$gv['amount']>0?$gv['gamount']-$gv['amount']:0;
                    $upsql = "update savor_smallapp_dishgoods set amount=$amount";
                    if($amount==0){
                        $upsql.=",status=2";
                    }
                    $sql_goods = "$upsql where id=$goods_id ";
                    $this->paynotify_log($paylog_type, $serial_no, $sql_goods);
                    $this->execute($sql_goods);

                    if($result_order[0]['sale_uid']){
                        if($gv['distribution_profit']>0){
                            $profit = $gv['distribution_profit'];
                        }
                        $income_fee = 0;
                        if($gv['price']>$gv['supply_price']){
                            $income_fee = ($gv['price']-$gv['supply_price'])*$profit;
                            $income_fee = sprintf("%.2f",$income_fee);
                        }
                        $total_fee = sprintf("%.2f",$gv['price']*$gv['amount']);
                        $income_data[] = array('user_id'=>$result_order[0]['sale_uid'],'openid'=>$result_order[0]['openid'],'order_id'=>$result_order[0]['id'],
                            'goods_id'=>$gv['goods_id'],'price'=>$gv['price'],'supply_price'=>$gv['supply_price'],'amount'=>$gv['amount'],
                            'total_fee'=>$total_fee,'income_fee'=>$income_fee, 'profit'=>$profit
                        );
                    }
                }
                if(!empty($income_data)){
                    $m_income->addAll($income_data);
                    $sql_income = $m_income->getLastSql();
                }else{
                    $sql_income = '';
                }
                $this->paynotify_log($paylog_type, $serial_no, "income:$sql_income");
            }
        }else{
            $is_succ = true;
        }
        return $is_succ;
    }

    public function handle_reward_notify($order_extend){
        $is_succ = false;
        $trade_no = $order_extend['trade_no'];
        $serial_no = $order_extend['serial_no'];
        $pay_fee = $order_extend['pay_fee'];
        $paylog_type = $order_extend['paylog_type'];
        $pay_type = $order_extend['pay_type'];

        $sql_order = "select * from savor_smallapp_ordermap where id='$trade_no'";
        $this->paynotify_log($paylog_type, $serial_no, $sql_order);
        $result_ordermap = $this->query($sql_order);

        $trade_no = intval($result_ordermap[0]['order_id']);
        $sql_order = "select * from savor_smallapp_reward where id='$trade_no'";
        $this->paynotify_log($paylog_type, $serial_no, $sql_order);
        $result_order = $this->query($sql_order);
        if($result_order[0]['status']==1){//1未支付,2已支付打赏,3打赏已提现
            if($pay_fee==$result_order[0]['money']){
                $status = 2;
                $update_condition = "update savor_smallapp_reward set status='$status'";
                $sql_uporder = "$update_condition where id='$trade_no'";
                $this->paynotify_log($paylog_type, $serial_no, $sql_uporder);
                $row_num = $this->execute($sql_uporder);
                if($row_num){
                    $is_succ = true;
                    $sql_serialno = "INSERT INTO `savor_smallapp_orderserial` (`trade_no`,`serial_order`,`goods_id`,`pay_type`)VALUES ($trade_no,'$serial_no',0,$pay_type)";
                    $this->execute($sql_serialno);
                    $this->paynotify_log($paylog_type, $serial_no, $sql_serialno);

                    $message_oid = $trade_no;
                    sendTopicMessage($message_oid,30);
                }
            }
        }else{
            $is_succ = true;
        }
        return $is_succ;
    }

    /**
     * 记录支付日志
     * @param string $paylog_type
     * @param int $pay_id
     * @param string $msg
     */
    public function paynotify_log($paylog_type,$pay_id, $msg){
        switch($paylog_type){
            case 1:
                $file_name = C('PAYLOGS_PATH').'wxpaypc_'.date('Ym').'.log';
                break;
            case 2:
                $file_name = C('PAYLOGS_PATH').'wxpaymobile_'.date('Ym').'.log';
                break;
            case 3:
                $file_name = C('PAYLOGS_PATH').'wxpayjsapi_'.date('Ym').'.log';
                break;
            case 100:
                $file_name = C('PAYLOGS_PATH').'wxrefund_'.date('Ym').'.log';
                break;
            case 200:
                $file_name = C('PAYLOGS_PATH').'wxmmpay_'.date('Ym').'.log';
                break;
            default:
                $file_name = C('PAYLOGS_PATH').'notify_'.date('Ym').'.log';
        }
        $fp = fopen($file_name,"a");
        flock($fp, LOCK_EX) ;
        fwrite($fp,strftime("%Y/%m/%d %H:%M:%S",time())."\t 支付流水号：{$pay_id}-- $msg \t\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    public function getPayConfigOld($pk_type=0){
        if(!$pk_type){
            $pk_type = C('PK_TYPE');//1主干版h5服务号 2主干版本 5销售端
        }
        switch ($pk_type){
            case 1:
                $fwh_config = C('WX_FWH_CONFIG');
                $appid = $fwh_config['appid'];
                $pay_config = C('PAY_WEIXIN_CONFIG');
                $payconfig = array(
                    'appid'=>$appid,
                    'partner'=>$pay_config['partner'],
                    'key'=>$pay_config['key']
                );
                break;
            case 2:
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
                break;
            case 5:
                $smallapp_config = C('SMALLAPP_SALE_CONFIG');
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
                break;
            default:
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


    public function getPayConfig($channel=0){
        //channel 1服务号H5支付 2小程序热点投屏支付 5小热点销售端支付
        switch ($channel){
            case 1:
                $fwh_config = C('WX_FWH_CONFIG');
                $appid = $fwh_config['appid'];
                $pay_config = C('PAY_WEIXIN_CONFIG');
                $payconfig = array(
                    'appid'=>$appid,
                    'partner'=>$pay_config['partner'],
                    'key'=>$pay_config['key']
                );
                break;
            case 2:
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
                break;
            case 5:
                $smallapp_config = C('SMALLAPP_SALE_CONFIG');
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
                break;
            default:
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
        }
        return $payconfig;
    }

    /**
     * 获取系统类型
     * @return number
     */
    public function getos(){
        $otype = 1;//1:PC 2:mobile
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if(preg_match('/ipad/i', $ua) || preg_match('/ipod/i', $ua) || preg_match('/iphone/i', $ua) || preg_match('/IOS/i', $ua) || preg_match('/Android/i', $ua)){
            $otype = 2;
        }
        return $otype;
    }

    public function host_name(){
        $http = 'http://';
        return $http.$_SERVER['HTTP_HOST'];
    }
    
}