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
                $this->valid_fields = array('hotel_id'=>1001);
                break;
            case 'wxchange':
                $this->is_verify = 1;
                $this->valid_fields = array('id'=>1001,'openid'=>1001,'hotel_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getMoneyList(){
        $hotel_id = $this->params['hotel_id'];
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $fields = 'g.id,g.name goods_name,g.rebate_integral as integral,g.is_audit';

        $nowtime = date('Y-m-d H:i:s');
        $where = array('h.hotel_id'=>$hotel_id,'g.type'=>30,'g.status'=>2);
        $where['g.start_time'] = array('elt',$nowtime);
        $where['g.end_time'] = array('egt',$nowtime);
        $orderby = 'g.price asc';
        $res_goods = $m_hotelgoods->getList($fields,$where,$orderby,'','');
        $this->to_back($res_goods);
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

        $integralrecord_data = array('openid'=>$openid,'integral'=>-$res_goods['rebate_integral'],
            'content'=>$id,'type'=>4,'integral_time'=>date('Y-m-d H:i:s'));
        $m_userintegralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $m_userintegralrecord->add($integralrecord_data);

        $userintegral = $res_integral['integral'] - $res_goods['rebate_integral'];
        $m_userintegral->updateData(array('id'=>$res_integral['id']),array('integral'=>$userintegral));

        $total_fee = sprintf("%.2f",1*$res_goods['price']);
        $m_order = new \Common\Model\Smallapp\ExchangeModel();
        $add_data = array('openid'=>$openid,'goods_id'=>$id,'price'=>$res_goods['price'],
            'amount'=>1,'total_fee'=>$total_fee,'status'=>20);
        $order_id = $m_order->add($add_data);

        $order_exchange[] = array($order_id=>date('Y-m-d H:i:s'));
        $redis->set($cache_key,json_encode($order_exchange),86400);

        if($res_goods['is_audit']==0){
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

            $money = $res_goods['price'];
            $trade_info = array('trade_no'=>$order_id,'money'=>$money,'open_id'=>$openid);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
            if($res['code']==10000){
                $m_order->updateData(array('id'=>$order_id),array('status'=>21));
            }else{
                if($res['code']==10003){
                    //发送短信
                    $phone = '';
                    $ucconfig = C('ALIYUN_SMS_CONFIG');
                    $alisms = new \Common\Lib\AliyunSms();
                    $params = array('merchant_no'=>1554975591);
                    $template_code = $ucconfig['send_invoice_addr_templateid'];
                    $res_data = $alisms::sendSms($phone,$params,$template_code);
                    $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                        'url'=>join(',',$params),'tel'=>$phone,'resp_code'=>$res_data->Code,'msg_type'=>3
                    );
                    $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
                    $m_account_sms_log->addData($data);
                }
            }

            $message = '您已提现成功，请注意查收。';
            $tips = '可能会因为网络问题有延迟到账情况，请耐心等待。';
        }else{
            $message = '您已成功提交申请！';
            $tips = '通过审核后系统会及时进行发放，请注意查收';
        }
        $res = array('message'=>$message,'tips'=>$tips);
        $this->to_back($res);
    }
}