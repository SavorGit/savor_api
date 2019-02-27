<?php
namespace H5\Controller;
use Think\Controller;

class ScanqrcodeController extends Controller {
    
    /**
     * @desc 发送电视红包帮助页面
     */
    public function scanpage(){
        $id = I('id','');
        $order_id = substr($id,32);
        $sign = substr($id,0,32);
        $sign_key = create_sign($order_id);
        if($sign_key!=$sign){
            $error = array('msg'=>'sign error');
            die(json_encode($error));
        }
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $res_order = $m_redpacket->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            $error = array('msg'=>'order not exist');
            die(json_encode($error));
        }
        if($res_order['status']>2){
            $error = array('msg'=>'order has pay');
            die(json_encode($error));
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('id'=>$res_order['user_id']);
        $user_info = $m_user->getOne('id,mpopenid',$where,'');
        if(empty($user_info['mpopenid'])){
            $this->wx_oauth($id);
        }else{
            $res_order['open_id'] = $user_info['mpopenid'];

            $qrcode = $this->wxpay($res_order);

            $this->assign('qrcode',$qrcode);
            $this->display('scanpage');
        }
    }

    public function showpage(){
        $code = I('code', '');
        $u = I('u','');
        $order_id = substr($u,32);
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $res_order = $m_redpacket->getInfo(array('id'=>$order_id));
        $user_id = $res_order['user_id'];

        $m_weixin_api = new \Common\Lib\Weixin_api();
        $result = $m_weixin_api->getWxOpenid($code);
        $open_id = $result['openid'];
        if(empty($open_id)){
            $this->wx_oauth($u);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('id'=>$user_id);
        $data = array('mpopenid'=>$open_id);
        $m_user->updateInfo($where,$data);

        $res_order['open_id'] = $open_id;
        $qrcode = $this->wxpay($res_order);

        $this->assign('qrcode',$qrcode);
        $this->assign('order_id',$order_id);
        $this->display('scanpage');
    }

    public function getresult(){
        $order_id = I('get.oid',0,'intval');
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $res_order = $m_redpacket->getInfo(array('id'=>$order_id));
        $all_bless = C('SMALLAPP_REDPACKET_BLESS');
        $status = $res_order['status'];
        $all_status = array(0=>'未付款',1=>'付款码到电视',2=>'付款中',3=>'付款失败',4=>'红包发送成功');
        $tips = '';
        $status_str = '';
        if($status == 4){
            $tips = '红包即将在电视中出现，提醒大家扫码领红包哦~';
        }
        if(isset($all_status[$status])){
            $status_str = $all_status[$status];
        }
        $this->assign('status_str',$status_str);
        $this->assign('tips',$tips);
        $this->assign('bless',$all_bless[$res_order['bless_id']]);
        $this->display('pay_result');

    }

    private function wx_oauth($u){
        $fwh_config = C('WX_FWH_CONFIG');
        $appid = $fwh_config['appid'];
        $uri = http_host().'/h5/scanqrcode/showpage/u/'.$u;
        $uri = urlencode($uri);
        $state = 'wxrs001';
        $url_oauth = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        $wx_url = $url_oauth."?appid=$appid&redirect_uri=$uri&response_type=code&scope=snsapi_base&state=$state#wechat_redirect";
        header("Location:".$wx_url);
    }

    private function wxpay($order){
        $http_host = http_host();
        //调用微信支付
        $trade_info = array('trade_no'=>$order['id'],'total_fee'=> $order['total_fee'],
            'trade_name'=>'小热点红包','body'=>'小热点红包','buy_time'=>$order['add_time'],'wx_openid'=>$order['open_id']
        );
        $fwh_config = C('WX_FWH_CONFIG');
        $appid = $fwh_config['appid'];
        $pay_config = C('PAY_WEIXIN_CONFIG');
        $payconfig = array(
            'appid'=>$appid,
            'partner'=>$pay_config['partner'],
            'key'=>$pay_config['key']
        );
        $m_payment = new \Payment\Model\WxpayModel(1);
        $url = $m_payment->pay($trade_info,$payconfig);
        $qrcode = $http_host.'/h5/qrcode?url='.$url;
        //推送付款二维码到电视
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('id'=>$order['user_id']);
        $user_info = $m_user->getOne('*',$where,'');
        $message = array('action'=>120,'nickName'=>$user_info['nickName'],
            'avatarUrl'=>$user_info['avatarUrl'],'codeUrl'=>$qrcode);
        $m_netty = new \Common\Model\NettyModel();
        $m_netty->pushBox($order['mac'],json_encode($message));
        //end
        return $qrcode;
    }


}