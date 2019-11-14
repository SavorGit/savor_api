<?php
namespace Smallsale14\Controller;
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
        }
        parent::_init_();
    }

    public function getMoneyList(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id);
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

        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $fields = 'g.id,g.name goods_name,g.rebate_integral as integral,g.is_audit';

        $nowtime = date('Y-m-d H:i:s');
        $where = array('h.hotel_id'=>$hotel_id,'g.type'=>30,'g.status'=>2);
        $where['g.start_time'] = array('elt',$nowtime);
        $where['g.end_time'] = array('egt',$nowtime);
        $orderby = 'g.price asc';
        $res_goods = $m_hotelgoods->getList($fields,$where,$orderby,'','');
        $data = array('integral'=>$integral,'datalist'=>$res_goods);
        $this->to_back($data);
    }

    public function wxchange(){
        $id = $this->params['id'];
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];

        $exchange_num = 1;
        $sale_key = C('SAPP_SALE');
        $cache_key = $sale_key.'exchange:'.'openid'.$openid.date('Ymd');
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

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
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

        $total_fee = sprintf("%.2f",1*$res_goods['price']);
        $m_order = new \Common\Model\Smallapp\ExchangeModel();
        $add_data = array('openid'=>$openid,'goods_id'=>$id,'price'=>$res_goods['price'],'hotel_id'=>$hotel_id,
            'amount'=>1,'total_fee'=>$total_fee,'status'=>20);
        $order_id = $m_order->add($add_data);

        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getHotelInfoById($hotel_id);
        $integralrecord_data = array('openid'=>$openid,'area_id'=>$res_hotel['area_id'],'area_name'=>$res_hotel['area_name'],
            'hotel_id'=>$hotel_id,'hotel_name'=>$res_hotel['hotel_name'],'hotel_box_type'=>$res_hotel['hotel_box_type'],
            'integral'=>-$res_goods['rebate_integral'],'goods_id'=>$id,'jdorder_id'=>$order_id,'content'=>1,'type'=>4,
            'integral_time'=>date('Y-m-d H:i:s'));
        $m_userintegralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $m_userintegralrecord->add($integralrecord_data);

        $userintegral = $res_integral['integral'] - $res_goods['rebate_integral'];
        $m_userintegral->updateData(array('id'=>$res_integral['id']),array('integral'=>$userintegral));

        $order_exchange[] = array($order_id=>date('Y-m-d H:i:s'));
        $redis->set($cache_key,json_encode($order_exchange),86400);

        if($res_goods['is_audit']==0){
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

            $money = $res_goods['price'];
            $trade_info = array('trade_no'=>$order_id,'money'=>$money,'open_id'=>$openid);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
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
}