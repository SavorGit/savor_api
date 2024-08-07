<?php
namespace Smallsale23\Controller;
use \Common\Controller\CommonController as CommonController;
class WithdrawController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getMoneyList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001);
                break;
            case 'wxchange':
                $this->is_verify = 1;
                $this->valid_fields = array('id'=>1001,'openid'=>1001,'hotel_id'=>1001);
                break;
            case 'claimMoney':
                $this->is_verify = 1;
                $this->valid_fields = array('task_id'=>1001,'openid'=>1001,'hotel_id'=>1001);
                break;
            case 'exchangerecord':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
            case 'income':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'money'=>1001);
                break;
            case 'checkMoney':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'goods_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getMoneyList(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
        $res_userintegral = $m_userintegral->getInfo(array('openid'=>$openid));
        $integral = 0;
        if(!empty($res_userintegral)){
            $integral = intval($res_userintegral['integral']);
        }
        $m_integralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $fields = 'sum(integral) as total_integral';
        $where = array('openid'=>$openid,'type'=>array('in',array(17,18,19,25)),'status'=>2);
        $freeze_integral = 0;
        $res_integral = $m_integralrecord->getALLDataList($fields,$where,'','','');
        if(!empty($res_integral)){
            $freeze_integral = intval($res_integral[0]['total_integral']);
        }

        $nowtime = date('Y-m-d H:i:s');
        $where = array('h.hotel_id'=>$hotel_id,'g.type'=>30,'g.status'=>2);
        $where['g.start_time'] = array('elt',$nowtime);
        $where['g.end_time'] = array('egt',$nowtime);
        $orderby = 'g.price asc';
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $fields = 'g.id,g.name goods_name,g.rebate_integral as integral,g.is_audit';
        $res_goods = $m_hotelgoods->getList($fields,$where,$orderby,'','');
        $data = array('integral'=>$integral,'freeze_integral'=>$freeze_integral,'datalist'=>$res_goods);
        $this->to_back($data);
    }

    public function wxchange(){
        $id = $this->params['id'];
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];

        $openids = C('WX_UBLACKLIST');
        $is_inlist = 0;
        if(in_array($openid,$openids)){
            $is_inlist = 1;
        }

        $exchange_num = 5;
        $sale_key = C('SAPP_SALE');
        $cache_key = $sale_key.'exchange:'.'openid'.$openid.date('Ymd');
        $space_cache_key = $sale_key.'exchange:spacetime:'.$openid;
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $res_spacecache = $redis->get($space_cache_key);
        if(!empty($res_spacecache)){
            $this->to_back(93229);
        }

        $res_cache = $redis->get($cache_key);
        if(!empty($res_cache)){
            $order_exchange = json_decode($res_cache,true);
            if(count($order_exchange)>=$exchange_num){
                $this->to_back(93018);
            }
        }else{
            $order_exchange = array();
        }
        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_stock = new \Common\Model\Finance\StockModel();
        $is_out = $m_stock->checkHotelThreshold($hotel_id);
        if($is_out==0){
            $this->to_back(93108);
        }

        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $res_hotelgoods = $m_hotelgoods->getInfo(array('hotel_id'=>$hotel_id,'goods_id'=>$id));
        if(empty($res_hotelgoods)){
            $this->to_back(93016);
        }

        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$id));
        if($res_goods['status']!=2 || $res_goods['type']!=30){
            $this->to_back(93016);
        }

        $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
        $res_integral = $m_userintegral->getInfo(array('openid'=>$openid));
        if(empty($res_integral) || $res_integral['integral']<$res_goods['rebate_integral']){
            $this->to_back(93017);
        }
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getHotelInfoById($hotel_id);
        $total_fee = sprintf("%.2f",1*$res_goods['price']);
        $m_order = new \Common\Model\Smallapp\ExchangeModel();
        $add_data = array('openid'=>$openid,'goods_id'=>$id,'price'=>$res_goods['price'],'hotel_id'=>$hotel_id,
            'amount'=>1,'total_fee'=>$total_fee,'status'=>20);
        if($is_inlist){
            $add_data['audit_status'] = 2;
            $res_goods['is_audit'] = 1;
        }

        $order_id = $m_order->add($add_data);

        $integralrecord_data = array('openid'=>$openid,'area_id'=>$res_hotel['area_id'],'area_name'=>$res_hotel['area_name'],
            'hotel_id'=>$hotel_id,'hotel_name'=>$res_hotel['hotel_name'],'hotel_box_type'=>$res_hotel['hotel_box_type'],
            'integral'=>-$res_goods['rebate_integral'],'goods_id'=>$id,'jdorder_id'=>$order_id,'source'=>2,'content'=>1,'type'=>4,
            'integral_time'=>date('Y-m-d H:i:s'));
        $m_userintegralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $m_userintegralrecord->add($integralrecord_data);

        $userintegral = $res_integral['integral'] - $res_goods['rebate_integral'];
        $m_userintegral->updateData(array('id'=>$res_integral['id']),array('integral'=>$userintegral));

        $order_exchange[] = array($order_id=>date('Y-m-d H:i:s'));
        $redis->select(14);
        $redis->set($cache_key,json_encode($order_exchange),86400);
        $redis->set($space_cache_key,$order_id,60);

        if($res_goods['is_audit']==0){
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

            $money = $res_goods['price'];
            $trade_info = array('trade_no'=>$order_id,'money'=>$money,'open_id'=>$openid);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
            $m_paylog = new \Common\Model\Smallapp\PaylogModel();
            $pay_data = array('order_id'=>$order_id,'openid'=>$openid,'wxorder_id'=>$order_id,'pay_result'=>json_encode($res));
            $m_paylog->add($pay_data);
            if($res['code']==10000){
                $m_order->updateData(array('id'=>$order_id),array('status'=>21));
            }else{
                if($res['code']==10003){
                    //发送短信
                    $ucconfig = C('ALIYUN_SMS_CONFIG');
                    $alisms = new \Common\Lib\AliyunSms();
                    $params = array('merchant_no'=>1554975591);
                    $template_code = $ucconfig['wx_money_not_enough_templateid'];

                    $phones = C('WEIXIN_MONEY_NOTICE');
                    $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
                    foreach ($phones as $vp){
                        $res_data = $alisms::sendSms($vp,$params,$template_code);
                        $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                            'url'=>join(',',$params),'tel'=>$vp,'resp_code'=>$res_data->Code,'msg_type'=>3
                        );
                        $m_account_sms_log->addData($data);
                    }
                }
            }

            $message = '您已提现成功，请注意查收。';
            $tips = '可能会因为网络问题有延迟到账情况，请耐心等待。';
        }else{
            $message = '您已成功提交申请！';
            $tips = '通过审核后系统会及时进行发放，请注意查收';
        }
        $res = array('message'=>$message,'tips'=>$tips,'integral'=>$userintegral);
        $this->to_back($res);
    }


    public function exchangerecord(){
        $hotel_id = intval($this->params['hotel_id']);

        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = C('SAPP_SALE').'exchangerecord';
        $res_cache = $redis->get($cache_key);
        $cache_record = array();
        if(!empty($res_cache)){
            $res_cache = json_decode($res_cache,true);
            shuffle($res_cache);
            $cache_record = array_slice($res_cache,0,10);
        }
        $m_integralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $where = array('hotel_id'=>$hotel_id,'type'=>4);
        $where["DATE_FORMAT(add_time,'%Y-%m-%d')"]=date('Y-m-d');
        $res_integral = $m_integralrecord->field('openid,area_id,area_name,goods_id')->where($where)->select();
        $hotel_record = array();
        if(!empty($res_integral)){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $m_goods = new \Common\Model\Smallapp\GoodsModel();
            foreach ($res_integral as $v){
                $res_user = $m_user->getOne('nickName',array('openid'=>$v['openid']),'id desc');
                $res_goods = $m_goods->getInfo(array('id'=>$v['goods_id']));
                $money = intval($res_goods['price']);
                $info = array('area_id'=>$v['area_id'],'area_name'=>$v['area_name'],'name'=>$res_user['nickName'],'money'=>$money);
                $hotel_record[]=$info;
            }
        }
        $res_record = array_merge($hotel_record,$cache_record);
        $datalist = array();
        $tips = C('exchange_tips');
        foreach ($res_record as $v){
            $message = sprintf($tips,$v['area_name'],$v['name'],$v['money']);
            $datalist[]=$message;
        }
        $m_box = new \Common\Model\BoxModel();
        $where = array('hotel.id'=>$hotel_id,'box.state'=>1,'box.flag'=>0);
        $res_box = $m_box->getBoxByCondition('box.mac,box.name as box_name,room.name as room_name',$where);
        if(!empty($res_box)){
            $boxs = array();
            foreach ($res_box as $v){
                $boxs[$v['mac']] = array('box_name'=>$v['box_name'],'room_name'=>$v['room_name']);
            }
            $m_reward = new \Common\Model\Smallapp\RewardModel();
            $m_comment = new \Common\Model\Smallapp\CommentModel();
            $where = array();
            $start_time = date('Y-m-d 00:00:00');
            $end_time = date('Y-m-d 23:59:59');
            $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
            $where['box_mac'] = array('in',array_keys($boxs));
            $where['status'] = 1;
            $res_comment = $m_comment->getDataList('id,reward_id,box_mac',$where,'id desc');
            if(!empty($res_comment)){
                $comment_tips = C('comment_tips');
                $reward_tips = C('reward_tips');
                $comment = $reward = array();
                foreach ($res_comment as $v){
                    $room_name = $boxs[$v['box_mac']]['room_name'];
                    $comment[] = sprintf($comment_tips,$room_name);
                    if($v['reward_id']>0){
                        $res_reward = $m_reward->getInfo(array('id'=>$v['reward_id'],'status'=>2));
                        if(!empty($res_reward)){
                            $reward[]=sprintf($reward_tips,$room_name);
                        }
                    }
                }
                $datalist = array_merge($reward,$comment,$datalist);
            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function income(){
        $openid = $this->params['openid'];
        $money = $this->params['money'];
        $exchange_num = 1;
        $sale_key = C('SAPP_SALE');
        $cache_key = $sale_key.'income:'.'openid'.$openid.date('Ymd');
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $res_cache = $redis->get($cache_key);
        if(!empty($res_cache)){
            $order_exchange = json_decode($res_cache,true);
            if(count($order_exchange)>=$exchange_num){
                $this->to_back(93018);
            }
        }else{
            $order_exchange = array();
        }
        $money = sprintf("%.2f",$money);
        if($money>5000){
            $this->to_back(93049);
        }

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>5);
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth,role_id';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $fields = 'sum(income_fee) as total_income_fee';
        $where_income = array('user_id'=>$res_user['user_id'],'is_withdraw'=>0);
        $day_time = date("Y-m-d H:i:s",strtotime("-7 day"));
        $where_income['add_time'] = array('elt',$day_time);
        $m_income = new \Common\Model\Smallapp\UserincomeModel();
        $res_income = $m_income->getDataList($fields,$where_income,'id desc');
        $withdraw_fee = 0;
        if(!empty($res_income)){
            $withdraw_fee =  $res_income[0]['total_income_fee'];
        }

        if($money>$withdraw_fee){
            $this->to_back(93050);
        }

        $total_fee = $money;
        $m_order = new \Common\Model\Smallapp\ExchangeModel();
        $add_data = array('openid'=>$openid,'goods_id'=>0,'price'=>0,'type'=>2,
            'amount'=>1,'total_fee'=>$total_fee,'status'=>20);
        $order_id = $m_order->add($add_data);

        $order_exchange[] = array($order_id=>date('Y-m-d H:i:s'));
        $redis->set($cache_key,json_encode($order_exchange),86400);

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
        $trade_info = array('trade_no'=>$order_id,'money'=>$money,'open_id'=>$openid);
        $m_wxpay = new \Payment\Model\WxpayModel();
        $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
        $m_income->updateData($where_income,array('is_withdraw'=>1,'update_time'=>date('Y-m-d H:i:s')));

        if($res['code']==10000){
            $m_order->updateData(array('id'=>$order_id),array('status'=>21));
        }else{
            if($res['code']==10003){
                //发送短信
                $ucconfig = C('ALIYUN_SMS_CONFIG');
                $alisms = new \Common\Lib\AliyunSms();
                $params = array('merchant_no'=>1594752111);
                $template_code = $ucconfig['wx_money_not_enough_templateid'];

                $phones = C('WEIXIN_MONEY_NOTICE');
                $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
                foreach ($phones as $vp){
                    $res_data = $alisms::sendSms($vp,$params,$template_code);
                    $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                        'url'=>join(',',$params),'tel'=>$vp,'resp_code'=>$res_data->Code,'msg_type'=>3
                    );
                    $m_account_sms_log->addData($data);
                }
            }
        }
        $message = '您已提现成功，请注意查收。';
        $tips = '可能会因为网络问题有延迟到账情况，请耐心等待。';

        $fields = 'sum(income_fee) as total_income_fee';
        $where_income = array('user_id'=>$res_user['user_id'],'is_withdraw'=>0);
        $m_income = new \Common\Model\Smallapp\UserincomeModel();
        $res_income = $m_income->getDataList($fields,$where_income,'id desc');
        $income_fee = 0;
        if(!empty($res_income[0]['total_income_fee'])){
            $income_fee =  $res_income[0]['total_income_fee'];
        }
        $res = array('message'=>$message,'tips'=>$tips,'withdraw_fee'=>0,'income_fee'=>$income_fee);
        $this->to_back($res);
    }

    public function checkMoney(){
        $openid = $this->params['openid'];
        $goods_id = $this->params['goods_id'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>5);
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,name,idnumber';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }

        $is_alert = 0;
        if(empty($res_user['name']) || empty($res_user['idnumber'])){
            $m_goods = new \Common\Model\Smallapp\GoodsModel();
            $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
            $total_money = intval($res_goods['price']);

            $m_exchange = new \Common\Model\Smallapp\ExchangeModel();
            $fields = 'sum(total_fee) as total_fee';
            $where = array('openid'=>$openid,'type'=>1);
            $start_time = date('Y-m-01 00:00:00');
            $end_time = date('Y-m-31 23:59:59');
            $where['add_time'] = array(array('EGT',$start_time),array('ELT',$end_time));
            $res_exchange = $m_exchange->getDataList($fields,$where,'');

            if(!empty($res_exchange)){
                $total_money = $total_money + intval($res_exchange[0]['total_fee']);
            }
            $m_sysconfig = new \Common\Model\SysConfigModel();
            $all_config = $m_sysconfig->getAllconfig();
            $sys_exchange_money = intval($all_config['exchange_money']);
            if($total_money>=$sys_exchange_money){
                $is_alert = 1;
            }
        }
        $this->to_back(array('is_alert'=>$is_alert));
    }


    public function claimMoney(){
        $task_id = intval($this->params['task_id']);
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,a.hotel_id,a.room_ids,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $staff_info = $res_staff[0];

        $m_usertask = new \Common\Model\Smallapp\UserTaskModel();
        $where = array('id'=>$task_id,'openid'=>$openid);
        $res_usertask = $m_usertask->getInfo($where);
        if(empty($res_usertask)){
            $this->to_back(93060);
        }
        if($res_usertask['status']==1){
            $this->to_back(93066);
        }
        if($res_usertask['status']==3){
            $this->to_back(93061);
        }
        if(in_array($res_usertask['status'],array(4,5))){
            $this->to_back(93062);
        }
        $data = array();
        if($res_usertask['status']==2 && $res_usertask['money']==$res_usertask['get_money']){
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
            $total_fee = $res_usertask['money'];
            $m_order = new \Common\Model\Smallapp\ExchangeModel();
            $add_data = array('openid'=>$openid,'goods_id'=>0,'price'=>0,'type'=>3,
                'amount'=>1,'total_fee'=>$total_fee,'status'=>20);
            $order_id = $m_order->add($add_data);

            $trade_info = array('trade_no'=>$order_id,'money'=>$total_fee,'open_id'=>$openid);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
            if($res['code']==10000){
                $m_order->updateData(array('id'=>$order_id),array('status'=>21));
                $m_usertask->updateData(array('id'=>$task_id),array('status'=>4,'withdraw_time'=>date('Y-m-d H:i:s')));
            }else{
                $m_usertask->updateData(array('id'=>$task_id),array('status'=>5,'withdraw_time'=>date('Y-m-d H:i:s')));

                if($res['code']==10003){
                    //发送短信
                    $ucconfig = C('ALIYUN_SMS_CONFIG');
                    $alisms = new \Common\Lib\AliyunSms();
                    $params = array('merchant_no'=>1594752111);
                    $template_code = $ucconfig['wx_money_not_enough_templateid'];

                    $phones = C('WEIXIN_MONEY_NOTICE');
                    $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
                    foreach ($phones as $vp){
                        $res_data = $alisms::sendSms($vp,$params,$template_code);
                        $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                            'url'=>join(',',$params),'tel'=>$vp,'resp_code'=>$res_data->Code,'msg_type'=>3
                        );
                        $m_account_sms_log->addData($data);
                    }
                }
            }
            $m_usertaskrecord = new \Common\Model\Smallapp\UserTaskRecordModel();
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getOneById('name',$hotel_id);

            $record_data = array('openid'=>$openid,'hotel_id'=>$hotel_id,'hotel_name'=>$res_hotel['name'],
                'task_hotel_id'=>$res_usertask['task_hotel_id'],'meal_num'=>$res_usertask['meal_num'],
                'interact_num'=>$res_usertask['interact_num'],'comment_num'=>$res_usertask['comment_num'],'money'=>$total_fee,
                'type'=>3
            );
            $m_usertaskrecord->add($record_data);

            $message = '您已提现成功，请注意查收。';
            $tips = '可能会因为网络问题有延迟到账情况，请耐心等待。';
            $data = array('message'=>$message,'tips'=>$tips);
        }
        $this->to_back($data);
    }

}