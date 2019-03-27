<?php
namespace H5\Controller;
use Think\Controller;

class ScanqrcodeController extends Controller {

    public function sendtv(){
        $trade_no = I('oid',0,'intval');
        $http_host = http_host();
        $m_user = new \Common\Model\Smallapp\UserModel();
        $m_netty = new \Common\Model\NettyModel();
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $key = C('SAPP_REDPACKET').'smallprogramcode';
        $res = $redis->get($key);
        if(!empty($res)){
            $bonus = json_decode($res,true);
            if(isset($bonus['order_id'])){
                die('Grabbing red packets ID:'.$bonus['order_id']);
            }
        }
        $result_order = $m_user->query('select * from savor_smallapp_redpacket where id='.$trade_no);
        if(!in_array($result_order[0]['status'],array(4,6))){
            die('bonus over');
        }
        $where = array('id'=>$result_order[0]['user_id']);
        $user_info = $m_user->getOne('*',$where,'');

        $box_mac = $result_order[0]['mac'];
        $scope = $result_order[0]['scope'];
        if(in_array($scope,array(1,2))){
            $all_box = $m_netty->getPushBox(2,$box_mac);
            if(!empty($all_box)){
                foreach ($all_box as $v){
                    $qrinfo =  $trade_no.'_'.$v;
                    $mpcode = $http_host.'/h5/qrcode/mpQrcode?qrinfo='.$qrinfo;
                    $message = array('action'=>121,'nickName'=>$user_info['nickName'],
                        'avatarUrl'=>$user_info['avatarUrl'],'codeUrl'=>$mpcode);
                    $m_netty->pushBox($v,json_encode($message));
                }
            }
            if($scope == 1){
                $key = C('SAPP_REDPACKET').'smallprogramcode';
                $res_data = array('order_id'=>$trade_no,'add_time'=>$result_order[0]['add_time'],'box_list'=>$all_box,
                    'nickName'=>$user_info['nickName'],'avatarUrl'=>$user_info['avatarUrl']);
                $redis->set($key,json_encode($res_data));
            }
//            //北京发红包只能发当前包间
//            $m_box = new \Common\Model\BoxModel();
//            $res = $m_box->getHotelInfoByBoxMacNew($box_mac);
//            if($res['area_id']==1){
//                if($scope == 1){
//                    $all_box = $m_netty->getPushBox(2,$box_mac);
//                    $key = C('SAPP_REDPACKET').'smallprogramcode';
//                    $res_data = array('order_id'=>$trade_no,'add_time'=>$result_order[0]['add_time'],'box_list'=>$all_box,
//                        'nickName'=>$user_info['nickName'],'avatarUrl'=>$user_info['avatarUrl']);
//                    $redis->set($key,json_encode($res_data));
//                }
//            }else{
//                $all_box = $m_netty->getPushBox(2,$box_mac);
//                if(!empty($all_box)){
//                    foreach ($all_box as $v){
//                        $qrinfo =  $trade_no.'_'.$v;
//                        $mpcode = $http_host.'/h5/qrcode/mpQrcode?qrinfo='.$qrinfo;
//                        $message = array('action'=>121,'nickName'=>$user_info['nickName'],
//                            'avatarUrl'=>$user_info['avatarUrl'],'codeUrl'=>$mpcode);
//                        $m_netty->pushBox($v,json_encode($message));
//                    }
//                }
//                if($scope == 1){
//                    $key = C('SAPP_REDPACKET').'smallprogramcode';
//                    $res_data = array('order_id'=>$trade_no,'add_time'=>$result_order[0]['add_time'],'box_list'=>$all_box,
//                        'nickName'=>$user_info['nickName'],'avatarUrl'=>$user_info['avatarUrl']);
//                    $redis->set($key,json_encode($res_data));
//                }
//            }
        }
        $key = C('SAPP_REDPACKET').'smallprogramcode';
        $res = $redis->get($key);
        if(!empty($res)){
            $bonus = json_decode($res,true);
            if(isset($bonus['order_id'])){
                die('red packets ready to grab ID:'.$bonus['order_id']);
            }
        }
    }

    public function bjmac(){
        $m_user = new \Common\Model\Smallapp\UserModel();
        $sql = "SELECT b.id AS box_id,b.NAME AS box_name,b.room_id,r.NAME AS room_name,h.id AS hotel_id,h.NAME AS hotel_name,a.id AS area_id,a.region_name AS area_name,b.mac FROM savor_box AS b LEFT JOIN savor_room AS r ON b.room_id=r.id LEFT JOIN savor_hotel AS h ON r.hotel_id=h.id LEFT JOIN savor_area_info AS a ON h.area_id=a.id WHERE h.area_id=1 AND h.state=1 AND h.flag=0 AND b.state=1 AND b.flag=0";
        $res = $m_user->query($sql);
        $all_mac = array();
        $key = C('SAPP_REDPACKET').'bjboxmac';
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        if(!empty($res)){
            foreach ($res as $v){
                $all_mac[] = $v['mac'];
            }
            $redis->select(5);
            $redis->set($key,json_encode($all_mac));
        }
        print_r($redis->get($key));
    }

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
            if(empty($resdata) || empty($resdata['unused'])){
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

        $log_content = date("Y-m-d H:i:s").'[order_id]'.$order['id'].'[pay_url]'.$qrcode."\n";
        $log_file_name = APP_PATH.'Runtime/Logs/'.'paycode_'.date("Ymd").".log";
        @file_put_contents($log_file_name, $log_content, FILE_APPEND);


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