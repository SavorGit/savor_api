<?php
namespace Smallsale21\Controller;
use \Common\Controller\CommonController as CommonController;
class CouponController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'scancode':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'qrcontent'=>1001);
                break;
            case 'writeoff':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                break;
            case 'getWriteoffList':
                $this->params = array('openid'=>1001,'page'=>1001);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function scancode(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $qrcontent = $this->params['qrcontent'];

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $param_coupon = decrypt_data($qrcontent);
        if(!is_array($param_coupon) || $param_coupon['type']!='coupon'){
            $this->to_back(93203);
        }

        $coupon_user_id = intval($param_coupon['id']);
        $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
        $res_usercoupon = $m_user_coupon->getInfo(array('id'=>$coupon_user_id));
        if($res_usercoupon['ustatus']!=1 || $res_usercoupon['hotel_id']!=$hotel_id){
            $this->to_back(93204);
        }
        $now_time = date('Y-m-d H:i:s');
        if($now_time>=$res_usercoupon['start_time'] && $now_time<=$res_usercoupon['end_time']){
            $m_coupon = new \Common\Model\Smallapp\CouponModel();
            $res_coupon = $m_coupon->getInfo(array('id'=>$res_usercoupon['coupon_id']));
            $data = array('name'=>$res_coupon['name'],'qrcode'=>$qrcontent,'add_time'=>date('Y-m-d H:i:s'));
            $this->to_back($data);
        }else{
            $this->to_back(93205);
        }
    }

    public function writeoff(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $qrcontent = $this->params['qrcontent'];

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $param_coupon = decrypt_data($qrcontent);
        if(!is_array($param_coupon) || $param_coupon['type']!='coupon'){
            $this->to_back(93203);
        }

        $coupon_user_id = intval($param_coupon['id']);
        $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
        $res_usercoupon = $m_user_coupon->getInfo(array('id'=>$coupon_user_id));
        if($res_usercoupon['ustatus']!=1 || $res_usercoupon['hotel_id']!=$hotel_id){
            $this->to_back(93204);
        }

        $now_time = date('Y-m-d H:i:s');
        if($now_time>=$res_usercoupon['start_time'] && $now_time<=$res_usercoupon['end_time']){
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
            $total_fee = $res_usercoupon['money'];
            $m_order = new \Common\Model\Smallapp\ExchangeModel();
            $add_data = array('openid'=>$openid,'goods_id'=>0,'price'=>0,'type'=>5,
                'amount'=>1,'total_fee'=>$total_fee,'status'=>20);
            $order_id = $m_order->add($add_data);

            $trade_info = array('trade_no'=>$order_id,'money'=>$total_fee,'open_id'=>$res_usercoupon['openid']);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
            if($res['code']==10000){
                $m_order->updateData(array('id'=>$order_id),array('status'=>21));
                $up_data = array('ustatus'=>2,'use_time'=>date('Y-m-d H:i:s'),'op_openid'=>$openid);
                $m_user_coupon->updateData(array('id'=>$coupon_user_id),$up_data);
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
                        $res_sms = $alisms::sendSms($vp,$params,$template_code);
                        $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                            'url'=>join(',',$params),'tel'=>$vp,'resp_code'=>$res_sms->Code,'msg_type'=>3
                        );
                        $m_account_sms_log->addData($data);
                    }
                }
            }
            $this->to_back(array('message'=>'成功使用优惠券'));
        }else{
            $this->to_back(93205);
        }
    }

    public function getWriteoffList(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $pagesize = 10;

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $offset = ($page-1)*$pagesize;
        $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
        $where = array('op_openid'=>$openid,'ustatus'=>2);
        $res_records = $m_user_coupon->getDataList('*',$where,'id desc',$offset,$pagesize);
        $data_list = array();
        if($res_records['total']>0){
            $m_hotel = new \Common\Model\HotelModel();
            $m_activity = new \Common\Model\Smallapp\ActivityModel();
            $m_stock_record = new \Common\Model\Finance\StockRecordModel();
            foreach ($res_records['list'] as $v){
                $res_hotel = $m_hotel->getInfoById($v['hotel_id'],'name');
                $expire_time = date('Y.m.d H:i',strtotime($v['end_time']));
                if($v['min_price']>0){
                    $min_price = "满{$v['min_price']}可用";
                }else{
                    $min_price = '无门槛立减券';
                }
                if($v['use_range']==1){
                    $range_str = '全部活动酒水';
                }else{
                    $range_str = '部分活动酒水';
                }
                $res_activity = $m_activity->getInfo(array('id'=>$v['activity_id']));
                $type_str = '幸运抽奖';
                if($res_activity['type']==14){
                    $type_str = '售酒抽奖';
                }
                $fileds = 'a.idcode,goods.id as goods_id,goods.name as goods_name,cate.name as cate_name,
                spec.name as spec_name,unit.name as unit_name,a.add_time';
                $where = array('a.idcode'=>$res_activity['idcode'],'a.dstatus'=>1);
                $res_srecord = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
                $info = array();
                if(!empty($res_srecord)){
                    $info = $res_srecord[0];
                }
                $info['money'] = $v['money'];
                $info['min_price'] = $min_price;
                $info['expire_time'] = "有效期至{$expire_time}";
                $info['hotel_name'] = $res_hotel['name'];
                $info['range_str'] = $range_str;
                $info['type_str'] = $type_str;
                $info['status_str'] = '已核销';
                $data_list[]=$info;
            }
        }
        $this->to_back($data_list);
    }
}