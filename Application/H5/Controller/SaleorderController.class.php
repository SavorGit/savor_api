<?php
/*
 * 销售订单支付
 */
namespace H5\Controller;
use Think\Controller;

class SaleorderController extends Controller {

    public function index(){
        $oid = I('oid','');
        if(empty($oid)){
            die('Parameter error');
        }
        $hash_ids_key = C('HASH_IDS_KEY');
        $hashids = new \Common\Lib\Hashids($hash_ids_key);
        $decode_info = $hashids->decode($oid);
        if(empty($decode_info)){
            die('decode error');
        }
        $order_id = intval($decode_info[0]);

        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            die('order error');
        }
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$res_order['goods_id']));

        $host_name = http_host();
        $url = $host_name.'/h5/saleorder/info/oid/'.$order_id;
        $qrcode_url = $host_name.'/h5/qrcode?url='.$url;
        $oinfo = array('name'=>$res_goods['name'],'amount'=>$res_order['amount'],'total_fee'=>$res_order['total_fee'],'qrcode'=>$qrcode_url);
        $this->assign('oinfo',$oinfo);
        $this->display();
    }


    public function info(){
        $code = I('code', '');
        $order_id = I('oid',0,'intval');
        if(empty($order_id)){
            die('Parameter error');
        }

        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            die('order error');
        }
        if($code){
            $m_weixin_api = new \Common\Lib\Weixin_api();
            $result = $m_weixin_api->getWxOpenid($code);
            $mpopenid = $result['openid'];

            $order_status = $res_order['status'];//10已下单 11支付失败 12支付成功
            switch ($order_status){
                case 10:
                case 11:
                    $m_goods = new \Common\Model\Smallapp\GoodsModel();
                    $res_goods = $m_goods->getInfo(array('id'=>$res_order['goods_id']));
                    $media_id = $res_goods['media_id'];
                    $imgmedia_id = $res_goods['imgmedia_id'];
                    $m_media = new \Common\Model\MediaModel();
                    $media_info = $m_media->getMediaInfoById($media_id);
                    if($media_info['type']==2){
                        $img_url = $media_info['oss_addr'];
                    }else{
                        if($imgmedia_id){
                            $media_info = $m_media->getMediaInfoById($imgmedia_id);
                            $img_url = $media_info['oss_addr'];
                        }else{
                            $img_url = $media_info['oss_addr'].'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450';
                        }
                    }
                    $oinfo = array('name'=>$res_goods['name'],'img_url'=>$img_url,
                        'amount'=>$res_order['amount'],'total_fee'=>$res_order['total_fee']);
                    $host_name = http_host();
                    $params = array('oid'=>$order_id,'openid'=>$mpopenid);
                    $encode_params = encrypt_data(json_encode($params));
                    $pay_url = $host_name.'/h5/saleorder/pay?params='.$encode_params;
                    $this->assign('pay_url',$pay_url);
                    $this->assign('oinfo',$oinfo);
                    $display_html = 'info';
                    break;
                case 12:
                    $m_orderinvoice = new \Common\Model\Smallapp\OrderinvoiceModel();
                    $res = $m_orderinvoice->getInfo(array('order_id'=>$order_id));
                    if(empty($res) || $res['status']==1){
                        $hash_ids_key = C('HASH_IDS_KEY');
                        $hashids = new \Common\Lib\Hashids($hash_ids_key);
                        $encode_oid = $hashids->encode($order_id);
                        $host_name = http_host();
                        $invoice_url = $host_name.'/h5/saleorder/invoice/oid/'.$encode_oid;
                        header('Location: '.$invoice_url);
                        exit;
                    }else{
                        $display_html = 'hasinvoice';
                    }
                    break;
                default:
                    die('order status error');
            }
            $this->assign('oid',$order_id);
            $this->display($display_html);
        }else{
            $host_name = http_host();
            $url = $host_name.'/h5/saleorder/info/oid/'.$order_id;
            $this->wx_oauth($url);
        }
    }

    public function pay(){
        $params = I('get.params');
        $params_info = decrypt_data($params);
        if(empty($params_info) || !is_array($params_info)){
            die('Parameter error');
        }
        $openid = $params_info['openid'];
        $order_id = $params_info['oid'];
        if(empty($openid) || !$order_id){
            die('Parameter error');
        }
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            die('order error');
        }
        if(!in_array($res_order['status'],array(10,11))){//10已下单 11支付失败 12支付成功
            die('order status error');
        }
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$res_order['goods_id']));
        $m_ordermap = new \Common\Model\Smallapp\OrdermapModel();
        $trade_no = $m_ordermap->add(array('order_id'=>$res_order['id'],'pay_type'=>10));

        $notify_url = C('HOST_NAME').'/payment/wxNotify/html5';;
        $trade_info = array('trade_no'=>$trade_no,'total_fee'=>$res_order['total_fee'],'trade_name'=>$res_goods['name'],
            'buy_time'=>$res_order['add_time'],'wx_openid'=>$openid,'redirect_url'=>'','attach'=>10,'notify_url'=>$notify_url);

        $fwh_config = C('WX_FWH_CONFIG');
        $appid = $fwh_config['appid'];
        $wx_config = C('PAY_WEIXIN_CONFIG');
        $payconfig = array(
            'appid'=>$appid,
            'partner'=>$wx_config['partner'],
            'key'=>$wx_config['key']
        );
        $m_payment = new \Payment\Model\WxpayModel(3);
        $wxpay = $m_payment->pay($trade_info,$payconfig);

        $hash_ids_key = C('HASH_IDS_KEY');
        $hashids = new \Common\Lib\Hashids($hash_ids_key);
        $encode_oid = $hashids->encode($order_id);

        $host_name = http_host();
        $result_url = $host_name.'/h5/saleorder/invoice/oid/'.$encode_oid;
        $prepay_url = $host_name.'/h5/saleorder/info/oid/'.$order_id;

        $this->assign('result_url',$result_url);
        $this->assign('prepay_url',$prepay_url);
        $this->assign('wxpay',$wxpay);
        $this->display('wxpay');
    }

    public function invoice(){
        $oid = I('oid','');
        if(empty($oid)){
            die('Parameter error');
        }
        $hash_ids_key = C('HASH_IDS_KEY');
        $hashids = new \Common\Lib\Hashids($hash_ids_key);
        $decode_info = $hashids->decode($oid);
        if(empty($decode_info)){
            die('decode error');
        }
        $order_id = intval($decode_info[0]);
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            die('order error');
        }
        $m_orderinvoice = new \Common\Model\Smallapp\OrderinvoiceModel();
        $res = $m_orderinvoice->getInfo(array('order_id'=>$order_id));
        if(!empty($res) && $res['status']!=1){
            $this->display('hasinvoice');
        }

        $oinfo = array('oid'=>$order_id,'total_fee'=>$res_order['total_fee']);

        $host_name = http_host();
        $url = $host_name.'/h5/saleorder/invoice/oid/'.$oid;
        $m_weixin_api = new \Common\Lib\Weixin_api();
        $res_config =$m_weixin_api->showShareConfig($url);
        $this->assign('appid',$res_config['appid']);
        $this->assign('timestamp',$res_config['timestamp']);
        $this->assign('noncestr',$res_config['noncestr']);
        $this->assign('signature',$res_config['signature']);

        $this->assign('version',time());

        $this->assign('oinfo',$oinfo);
        $this->display();
    }

    public function addinvoice(){
        $order_id = I('oid',0,'intval');
        $company = I('company','','trim');
        $credit_code = I('credit_code','','trim');
        $invoice_type = I('invoice_type',1,'intval');//发票类型 1纸质发票 2电子发票
        $contact = I('contact','','trim');
        $phone = I('phone','','trim');
        $address = I('address','','trim');

        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'订单信息有误'),'JSONP');
        }
        $order_status = $res_order['status'];//10已下单 11支付失败 12支付成功
        if($order_status!=12){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'订单状态错误'),'JSONP');
        }
        if(empty($company)){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'请输入企业名称'),'JSONP');
        }
        if(empty($credit_code)){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'请输入企业税号'),'JSONP');
        }
        if(empty($invoice_type)){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'请选择发票类型'),'JSONP');
        }
        if(empty($contact)){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'请输入联系人'),'JSONP');
        }
        if(empty($phone)){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'请输入手机号码'),'JSONP');
        }
        if(!isMobile($phone)){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'请输入正确的手机号码'),'JSONP');
        }
        if($invoice_type==2 && !isEmail($address)){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'请输入正确的邮箱地址'),'JSONP');
        }

        if(empty($address)){
            if($invoice_type==1){
                $msg = '请输入邮寄地址';
            }else{
                $msg = '请输入邮箱地址';
            }
            $this->ajaxReturn(array('code'=>10001,'msg'=>$msg),'JSONP');
        }
        $m_orderinvoice = new \Common\Model\Smallapp\OrderinvoiceModel();
        $res = $m_orderinvoice->getInfo(array('order_id'=>$order_id));
        $data = array('order_id'=>$order_id,'company'=>$company,'credit_code'=>$credit_code,'contact'=>$contact,'phone'=>$phone,
            'type'=>$invoice_type,'status'=>2);
        if($invoice_type==1){
            $data['address']=$address;
        }else{
            $data['email']=$address;
        }
        if(!empty($res)){
            switch ($res['status']){//1暂不开票 2已提交开具发票申请 3发票已开
                case 1:
                    $m_orderinvoice->updateData(array('id'=>$res['id']),$data);
                    $msg = '已提交发票申请';
                    break;
                case 2:
                    $msg = '已提交发票申请,请勿重复提交';
                    break;
                case 3:
                    $msg = '发票已开,请注意查收';
                    break;
                default:
                    $msg = '';
            }
        }else{
            $m_orderinvoice->add($data);
            $msg = '已提交发票申请';
        }
        $res = array('code'=>10000,'msg'=>$msg);
        $this->ajaxReturn($res,'JSONP');
    }

    public function sendaddr(){
        $order_id = I('oid',0,'intval');
        $phone = I('phone','','trim');
        if(empty($phone)){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'请输入手机号码'),'JSONP');
        }
        if(!isMobile($phone)){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'请输入正确的手机号码'),'JSONP');
        }

        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            $this->ajaxReturn(array('code'=>10001,'msg'=>$order_id),'JSONP');
        }
        $order_status = $res_order['status'];//10已下单 11支付失败 12支付成功
        if($order_status!=12){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'订单状态错误'),'JSONP');
        }

        $m_orderinvoice = new \Common\Model\Smallapp\OrderinvoiceModel();
        $res = $m_orderinvoice->getInfo(array('order_id'=>$order_id));
        $data = array('order_id'=>$order_id,'phone'=>$phone,'status'=>1);
        $is_send = 0;
        if(!empty($res)){
            switch ($res['status']){//1暂不开票 2已提交开具发票申请 3发票已开
                case 1:
                    $m_orderinvoice->updateData(array('id'=>$res['id']),$data);
                    $is_send = 1;
                    $msg = '短信已发送,请注意查收';
                    break;
                case 2:
                    $msg = '已提交发票申请,请勿重复提交';
                    break;
                case 3:
                    $msg = '发票已开,请注意查收';
                    break;
                default:
                    $msg = '';
            }
        }else{
            $m_orderinvoice->add($data);
            $is_send = 1;
            $msg = '短信已发送,请注意查收';
        }
        if($is_send && !empty($res_order['box_mac'])){
            $ucconfig = C('SMS_CONFIG');
            $options = array('accountsid'=>$ucconfig['accountsid'],'token'=>$ucconfig['token']);
            $ucpass= new \Common\Lib\Ucpaas($options);
            $appId = $ucconfig['appid'];

            $m_box = new \Common\Model\BoxModel();
            $map = array();
            $map['a.mac'] = $res_order['box_mac'];
            $map['a.state'] = 1;
            $map['a.flag']  = 0;
            $map['d.state'] = 1;
            $map['d.flag']  = 0;
            $fields = 'a.id as box_id,d.name as hotel_name,c.name as room_name';
            $box_info = $m_box->getBoxInfo($fields, $map);
            $box_info = $box_info[0];

            $m_goods = new \Common\Model\Smallapp\GoodsModel();
            $res_goods = $m_goods->getInfo(array('id'=>$res_order['goods_id']));

            $hash_ids_key = C('HASH_IDS_KEY');
            $hashids = new \Common\Lib\Hashids($hash_ids_key);
            $encode_oid = $hashids->encode($order_id);

            $param = "{$box_info['hotel_name']},{$res_goods['name']},$encode_oid";
            $res_json = $ucpass->templateSMS($appId,$phone,$ucconfig['send_invoice_addr_templateid'],$param);
            $res_data = json_decode($res_json,true);
            if(isset($res_data['resp']['respCode'])) {
                $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                    'url'=>$param,'tel'=>$box_info['activity_phone'],'resp_code'=>$res_data['resp']['respCode'],'msg_type'=>3
                );
                $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
                $m_account_sms_log->addData($data);
            }
        }
        $res = array('code'=>10000,'msg'=>$msg);
        $this->ajaxReturn($res,'JSONP');

    }

    private function wx_oauth($url){
        $fwh_config = C('WX_FWH_CONFIG');
        $appid = $fwh_config['appid'];
        $uri = urlencode($url);
        $state = 'wxag001';
        $url_oauth = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        $wx_url = $url_oauth."?appid=$appid&redirect_uri=$uri&response_type=code&scope=snsapi_base&state=$state#wechat_redirect";
        header("Location:".$wx_url);
    }

}