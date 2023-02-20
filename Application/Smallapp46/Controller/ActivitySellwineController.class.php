<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class ActivitySellwineController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'scanGoodscode':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'idcode'=>1001,'box_mac'=>1001,'order_id'=>1001);
                break;
            case 'receivecash':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001,'rtype'=>1001,'num'=>1001);
                break;
        }
        parent::_init_();
    }

    public function scanGoodscode(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];
        $box_mac = $this->params['box_mac'];
        $order_id = intval($this->params['order_id']);

        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        $where = array('openid'=>$openid);
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res_user = $m_user->getOne('id,mobile', $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $res_records = $m_stock_record->getALLDataList('*',array('idcode'=>$idcode,'dstatus'=>1),'id desc','0,1','');
        if($res_records[0]['type']<5){
            $this->to_back(93096);
        }
        if($res_records[0]['type']==6){
            $this->to_back(93095);
        }elseif($res_records[0]['type']==7){
            if(in_array($res_records[0]['wo_status'],array(1,4))){
                $this->to_back(93098);
            }elseif($res_records[0]['wo_status']==2){
                $this->to_back(93094);
            }
        }
        $goods_id = $res_records[0]['goods_id'];
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order) || $res_order['openid']!=$openid){
            $this->to_back(90134);
        }
        $res_bindorder = $m_order->getInfo(array('otype'=>9,'idcode'=>$idcode));
        if(!empty($res_bindorder)){
            $this->to_back(90196);
        }
        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $gfields = 'goods.id as goods_id,goods.finance_goods_id';
        $res_ordergoods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
        if($goods_id!=$res_ordergoods[0]['finance_goods_id']){
            $this->to_back(90192);
        }
        $m_sellwine_activity = new \Common\Model\Smallapp\SellwineActivityModel();
        $res_activity = $m_sellwine_activity->getInfo(array('id'=>$res_order['sellwine_activity_id']));
        $daily_money_limit = $res_activity['daily_money_limit'];
        $money_limit = $res_activity['money_limit'];

        $m_sellwine_redpacket = new \Common\Model\Smallapp\SellwineActivityRedpacketModel();
        $rwhere = array('openid'=>$openid,'sellwine_activity_id'=>$res_order['sellwine_activity_id'],'status'=>array('in','11,21'));
        $res_data = $m_sellwine_redpacket->getDataList('sum(money) as total_money',$rwhere,'');
        $total_money = intval($res_data[0]['total_money']);
        if($total_money>=$money_limit){
            $this->to_back(90193);
        }
        $rwhere['DATE(add_time)'] = date('Y-m-d');
        $daily_money = intval($res_data[0]['total_money']);
        if($daily_money>=$daily_money_limit){
            $this->to_back(90194);
        }
        $m_order->updateData(array('id'=>$order_id),array('idcode'=>$idcode,'bind_idcode_time'=>date('Y-m-d H:i:s')));

        $m_sellwine_activity_goods = new \Common\Model\Smallapp\SellwineActivityGoodsModel();
        $res_goods = $m_sellwine_activity_goods->getInfo(array('activity_id'=>$res_order['sellwine_activity_id'],'finance_goods_id'=>$goods_id,'status'=>1));
        $red_money = intval($res_goods['money']);
        $receive_types = array(array('id'=>10,'name'=>'自己独吞-微信零钱','is_check'=>0));
        $m_netty = new \Common\Model\NettyModel();
        $req_id = getMillisecond();
        $res_netty = $m_netty->pushBox($box_mac,'',$req_id);
        if($res_netty['code']==10000){
            $num = intval($red_money/0.3);
            array_unshift($receive_types,array('id'=>20,'name'=>'与包间朋友分享-电视红包','is_check'=>1,'redpacket_num'=>$num));
        }else{
            $receive_types[0]['is_check'] = 1;
        }
        $tips = "恭喜您获得{$red_money}元红包";
        $res_data = array('order_id'=>$order_id,'tips'=>$tips,'receive_types'=>$receive_types);
        $this->to_back($res_data);
    }

    public function receivecash(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);
        $rtype = intval($this->params['rtype']);//10微信零钱,20电视红包
        $num = intval($this->params['num']);
        $box_mac = $this->params['box_mac'];

        $rtype_map = array('10'=>'微信零钱','20'=>'电视红包');
        $where = array('openid'=>$openid);
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res_user = $m_user->getOne('id,mobile', $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order) || $res_order['openid']!=$openid){
            $this->to_back(90134);
        }
        $m_sellwine_activity = new \Common\Model\Smallapp\SellwineActivityModel();
        $res_activity = $m_sellwine_activity->getInfo(array('id'=>$res_order['sellwine_activity_id']));
        $daily_money_limit = $res_activity['daily_money_limit'];
        $money_limit = $res_activity['money_limit'];

        $m_sellwine_redpacket = new \Common\Model\Smallapp\SellwineActivityRedpacketModel();
        $rwhere = array('openid'=>$openid,'sellwine_activity_id'=>$res_order['sellwine_activity_id'],'status'=>array('in','11,21'));
        $res_data = $m_sellwine_redpacket->getDataList('sum(money) as total_money',$rwhere,'');
        $total_money = intval($res_data[0]['total_money']);
        if($total_money>=$money_limit){
            $this->to_back(90193);
        }
        $rwhere['DATE(add_time)'] = date('Y-m-d');
        $daily_money = intval($res_data[0]['total_money']);
        if($daily_money>=$daily_money_limit){
            $this->to_back(90194);
        }

        $m_sellwine_redpacket = new \Common\Model\Smallapp\SellwineActivityRedpacketModel();
        $rwhere = array('openid'=>$openid,'sellwine_activity_id'=>$res_order['sellwine_activity_id'],
            'order_id'=>$order_id,'status'=>array('in','11,21'));
        $res_data = $m_sellwine_redpacket->getDataList('sum(money) as total_money',$rwhere,'');
        $total_money = intval($res_data[0]['total_money']);
        if($total_money>0){
            $this->to_back(90195);
        }

        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $gfields = 'goods.id as goods_id,goods.finance_goods_id';
        $res_ordergoods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
        $goods_id = intval($res_ordergoods[0]['finance_goods_id']);
        $m_sellwine_activity_goods = new \Common\Model\Smallapp\SellwineActivityGoodsModel();
        $res_goods = $m_sellwine_activity_goods->getInfo(array('activity_id'=>$res_order['sellwine_activity_id'],'finance_goods_id'=>$goods_id,'status'=>1));
        $red_money = intval($res_goods['money']);

        $activity_redpacket_data = array('openid'=>$openid,'sellwine_activity_id'=>$res_order['sellwine_activity_id'],
            'order_id'=>$order_id,'money'=>$red_money,'type'=>$rtype);
        if($rtype==10){
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
            $total_fee = $red_money;
            $m_exchange = new \Common\Model\Smallapp\ExchangeModel();
            $add_data = array('openid'=>$openid,'goods_id'=>0,'order_id'=>$order_id,'price'=>0,'type'=>6,
                'amount'=>1,'total_fee'=>$total_fee,'status'=>20);
            $order_exchange_id = $m_exchange->add($add_data);

            $trade_info = array('trade_no'=>$order_exchange_id,'money'=>$total_fee,'open_id'=>$openid);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
            if($res['code']==10000){
                $m_exchange->updateData(array('id'=>$order_exchange_id),array('status'=>21));
                $activity_redpacket_data['status'] = 11;
            }else{
                $activity_redpacket_data['status'] = 12;
            }
            $m_sellwine_redpacket->add($activity_redpacket_data);

            $m_paylog = new \Common\Model\Smallapp\PaylogModel();
            $pay_data = array('order_id'=>$order_id,'openid'=>$openid,'idcode'=>$res_order['idcode'],
                'wxorder_id'=>$order_exchange_id,'pay_result'=>json_encode($res));
            $m_paylog->add($pay_data);
        }else{
            $now_num = intval($red_money/0.3);
            if($num>$now_num){
                $num = $now_num;
            }
            $activity_redpacket_data['num'] = $num;
            $activity_redpacket_data['status'] = 21;

            $op_userid = 42996;
            $redpacket = array('user_id'=>$op_userid,'total_fee'=>$red_money,'amount'=>$num,'surname'=>'小热点',
                'sex'=>1,'bless_id'=>163,'scope'=>3,'mac'=>$box_mac,'pay_fee'=>$red_money,'order_id'=>$order_id,
                'pay_time'=>date('Y-m-d H:i:s'),'pay_type'=>10,'status'=>4,'operate_type'=>3);
            $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
            $trade_no = $m_redpacket->addData($redpacket);

            //根据红包总金额和人数进行分配红包
            $money = $redpacket['total_fee'];
            $num = $redpacket['amount'];
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
            //end
            $user_info = array('nickName'=>'小热点','avatarUrl'=>get_oss_host().'media/resource/btCfRRhHkn.jpg');
            $m_user->updateInfo(array('id'=>$op_userid),array('nickName'=>$user_info['nickName'],'avatarUrl'=>$user_info['avatarUrl']));

            $qrinfo =  $trade_no.'_'.$box_mac;
            $mpcode = http_host().'/h5/qrcode/mpQrcode?qrinfo='.$qrinfo;
            $message = array('action'=>121,'nickName'=>$user_info['nickName'],'avatarUrl'=>$user_info['avatarUrl'],
                'codeUrl'=>$mpcode);
            $m_netty = new \Common\Model\NettyModel();
            $res_push = $m_netty->pushBox($box_mac,json_encode($message));
            if($res_push['error_code']){
                $activity_redpacket_data['status'] = 22;
                $m_sellwine_redpacket->add($activity_redpacket_data);
                $this->to_back($res_push['error_code']);
            }else{
                $m_sellwine_redpacket->add($activity_redpacket_data);
            }
        }

        $message = "您的红包将以【{$rtype_map[$rtype]}】的形式发放请注意查收";
        $this->to_back(array('message'=>$message));
    }

}