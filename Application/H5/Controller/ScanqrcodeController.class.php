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
        $res = $m_redpacket->getInfo(array('id'=>$order_id));
        if(empty($res)){
            $error = array('msg'=>'order not exist');
            die(json_encode($error));
        }
        if($res['status']>2){
            $error = array('msg'=>'order has pay');
            die(json_encode($error));
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('id'=>$res['user_id']);
        $user_info = $m_user->getOne('id,mpopenid',$where,'');
        if(empty($user_info['mpopenid'])){
            $this->wx_oauth($id);
        }else{
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

        //调用微信支付
        $redirect_url = '';
        $trade_info = array('trade_no'=>$res_order['id'],'total_fee'=> $res_order['total_fee'],
            'trade_name'=>'小热点红包','body'=>'小热点红包','buy_time'=>$res_order['add_time'],
            'redirect_url'=>$redirect_url,'wx_openid'=>$open_id
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


        $this->display('scanpage');
    }

    private function wx_oauth($u){
        $dyh_config = C('WX_FWH_CONFIG');
        $appid = $dyh_config['appid'];
        $uri = http_host().'/h5/scanqrcode/showpage/u/'.$u;
        $uri = urlencode($uri);
        $state = 'wxrs001';
        $url_oauth = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        $wx_url = $url_oauth."?appid=$appid&redirect_uri=$uri&response_type=code&scope=snsapi_base&state=$state#wechat_redirect";
        header("Location:".$wx_url);
    }


}