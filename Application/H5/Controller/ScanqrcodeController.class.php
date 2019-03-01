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
        $user_id = $res_order['user_id'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('id'=>$user_id);
        $user_info = $m_user->getOne('id,mpopenid',$where,'');
        if(empty($user_info['mpopenid'])){
            $url = http_host().'/h5/scanqrcode/showpage/u/'.$user_id;
            $this->wx_oauth($url);
        }else{
            $res_order['open_id'] = $user_info['mpopenid'];

            $qrcode = $this->wxpay($res_order);

            $this->assign('qrcode',$qrcode);
            $this->display('scanpage');
        }
    }

    public function showpage(){
        $code = I('code', '');
        $user_id = I('u','');
        $m_weixin_api = new \Common\Lib\Weixin_api();
        $result = $m_weixin_api->getWxOpenid($code);
        $open_id = $result['openid'];
        if(empty($open_id)){
            $url = http_host().'/h5/scanqrcode/showpage/u/'.$user_id;
            $this->wx_oauth($url);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('id'=>$user_id);
        $data = array('mpopenid'=>$open_id);
        $m_user->updateInfo($where,$data);

        $res_order['open_id'] = $open_id;
        $qrcode = $this->wxpay($res_order);

        $this->assign('qrcode',$qrcode);
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

    public function grabBonus(){
        $order_id = I('get.oid');
        $grap_userid = I('get.guid');
        $token = I('get.token','');
        $now_token = create_sign($order_id.$grap_userid);
        if(!empty($token) && $token!=$now_token){
            $error = array('msg'=>'token error');
            die(json_encode($error));
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $m_order = new \Common\Model\Smallapp\RedpacketModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));

        if($token){
            $where = array('id'=>$res_order['user_id']);
            $user_info = $m_user->getOne('*',$where,'');
            $info = array('nickName'=>$user_info['nickName'],'avatarUrl'=>$user_info['avatarUrl']);
            $all_bless = C('SMALLAPP_REDPACKET_BLESS');
            $info['bless'] = $all_bless[$res_order['bless_id']];
            $status = 4;
            $sign = create_sign($status.$order_id.$grap_userid);
            $params = array('status'=>$status,'order_id'=>$order_id,'user_id'=>$grap_userid,'sign'=>$sign,'money'=>0);

            $this->assign('params',$params);
            $this->assign('info',$info);
            $this->assign('time',time());
            $this->display('grab');
        }else{
            $ou = $order_id.'o'.$grap_userid;
            $url = http_host().'/h5/scanqrcode/grabpage/ou/'.$ou;
            $this->wx_oauth($url);
        }
    }

    public function grabpage(){
        $code = I('code', '');
        $ou = I('ou','');
        $m_weixin_api = new \Common\Lib\Weixin_api();
        $result = $m_weixin_api->getWxOpenid($code);
        $open_id = $result['openid'];
        if(empty($open_id)){
            $url = http_host().'/h5/scanqrcode/grabBonus/ou/'.$ou;
            $this->wx_oauth($url);
        }
        $ou_arr = explode('o',$ou);
        $order_id = $ou_arr[0];
        $user_id = $ou_arr[1];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('id'=>$user_id);
        $data = array('mpopenid'=>$open_id);
        $m_user->updateInfo($where,$data);

        $m_order = new \Common\Model\Smallapp\RedpacketModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));

        $red_packet_key = C('SAPP_REDPACKET');
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $key_hasget = $red_packet_key.$order_id.':hasget';//已经抢到红包的用户列表
        $res_hasget = $redis->get($key_hasget);
        $get_money = '';
        if($res_hasget){
            $hasget_users = json_decode($res_hasget,true);
            if(array_key_exists($user_id,$hasget_users)){
                $status = 1;//已领取红包
                $get_money = $hasget_users[$user_id];
            }
        }
        if($status!=1){
            $key_bonus = $red_packet_key.$order_id.':bonus';//红包列表
            $res_redpacket = $redis->get($key_bonus);
            $resdata = json_decode($res_redpacket,true);
            if(empty($resdata['unused'])){
                $status = 2;//红包已领完,未领到
            }else{
                $key_getbonus = $red_packet_key.$order_id.':getbonus';//领红包用户队列
                $res_getbonus = $redis->lgetrange($key_getbonus,0,1000);
                if(in_array($user_id,$res_getbonus)){
                    $status = 3;//正在领红包
                }else{
                    $key_grabbonus = $red_packet_key.$order_id.':grabbonus';//抢红包用户队列
                    $res_grabbonus = $redis->lgetrange($key_grabbonus,0,1000);
                    if(empty($res_grabbonus)){
                        $redis->rpush($key_grabbonus,$user_id);
                        $status = 4;//进入抢红包队列,同时生成token
                    }else{
                        if(count($res_grabbonus)>=$res_order['amount']*2){
                            $status = 2;//红包已领完,未领到
                        }else{
                            if(!in_array($user_id,$res_grabbonus)){
                                $redis->rpush($key_grabbonus,$user_id);
                            }
                            $status = 4;//进入抢红包队列,同时生成token
                        }
                    }
                }
            }
        }
        $where = array('id'=>$res_order['user_id']);
        $user_info = $m_user->getOne('*',$where,'');
        $info = array('nickName'=>$user_info['nickName'],'avatarUrl'=>$user_info['avatarUrl']);
        $all_bless = C('SMALLAPP_REDPACKET_BLESS');
        $info['bless'] = $all_bless[$res_order['bless_id']];
        $sign = create_sign($status.$order_id.$user_id);
        $params = array('status'=>$status,'order_id'=>$order_id,'user_id'=>$user_id,'sign'=>$sign,'money'=>$get_money);
        $this->assign('time',time());
        $this->assign('params',$params);
        $this->assign('info',$info);
        $this->display('grab');
    }



    private function wx_oauth($url){
        $fwh_config = C('WX_FWH_CONFIG');
        $appid = $fwh_config['appid'];
        $uri = urlencode($url);
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