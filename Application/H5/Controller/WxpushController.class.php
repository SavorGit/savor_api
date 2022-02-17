<?php
/*
 * 关注公众号
 */
namespace H5\Controller;
use Think\Controller;

class WxpushController extends Controller {

    public function mp(){
        $code = I('code', '');
        $wechat = new \Common\Lib\Wechat();
        if($code){
            $result = $wechat->getWxOpenid($code);
            $wx_mpopenid = $result['openid'];
            $access_token = $wechat->getWxAccessToken();
            $res = $wechat->getWxUserDetail($access_token ,$wx_mpopenid);
            if(!isset($res['openid'])){
                $access_token = $wechat->getWxAccessToken();
                $res = $wechat->getWxUserDetail($access_token ,$wx_mpopenid);
            }
            if(isset($res['openid'])){
                if($res['subscribe']){
                    $data = array(
                        'touser'=>$res['openid'],
                        'template_id'=>"8HdJeBWn7ZmpKWYQgH17A5ZaD75CxL8zrFcNoTzmDqg",
                        'url'=>"",
                        'miniprogram'=>array(
                            'appid'=>'wxfdf0346934bb672f',
                            'pagepath'=>'pages/index/index',
                        ),
                        'data'=>array(
                            'first'=>array('value'=>'您好，您的会员积分信息有了新的变更。') ,
                            'keyword1'=>array('value'=>$res['nickname']),
                            'keyword2'=>array('value'=>6009891111),
                            'keyword3'=>array('value'=>300,),
                            'keyword4'=>array('value'=>1200),
                            'remark'=>array('value'=>'如有疑问，请拨打123456789.','color'=>"#FF1C2E"),
                        )
                    );
                    $data = json_encode($data);
                    $res = $wechat->templatesend($data);
                    echo $res;
                    exit;
                }else{
                    echo $res['openid'].' Please focus on redian fuwuhao';
                    exit;
                }
            }
        }else{
            $url = 'http://admin.littlehotspot.com/h5/subscribe/mp';
            $wechat->wx_oauth($url);
        }
    }

    public function readfileconfig(){
        $redis = new \Common\Lib\SavorRedis();
        $key = 'readfile_config';
        $redis->select(1);
        $res = $redis->get($key);
        $dinfo = array();
        if(!empty($res)){
            $dinfo = json_decode($res,true);
        }
        $res = $dinfo;
        echo json_encode($res);
    }

    public function failtaskmoney(){
        exit;
        $task_id = 169;
        $openid='o9GS-4i-E_HGfMd2JVxRgfwWqiL0';

        $m_usertask = new \Common\Model\Smallapp\UserTaskModel();
        $where = array('id'=>$task_id,'openid'=>$openid);
        $res_usertask = $m_usertask->getInfo($where);
        if($res_usertask['status']==5 && $res_usertask['money']==$res_usertask['get_money']){
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
            $res_fail_order = $m_order->getInfo(array('openid'=>$openid,'type'=>3,'status'=>20));
            if(!empty($res_fail_order) && $res_fail_order['total_fee']==$total_fee){
                $order_id = $res_fail_order['id'];
                $trade_info = array('trade_no'=>$order_id,'money'=>$total_fee,'open_id'=>$openid);
                $m_wxpay = new \Payment\Model\WxpayModel();
                $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
                if($res['code']==10000){
                    $m_order->updateData(array('id'=>$order_id),array('status'=>21));
                    $m_usertask->updateData(array('id'=>$task_id),array('status'=>4,'withdraw_time'=>date('Y-m-d H:i:s')));
                }else{
                    $m_usertask->updateData(array('id'=>$task_id),array('status'=>5,'withdraw_time'=>date('Y-m-d H:i:s')));
                }
                print_r($res);
            }else{
                echo "{$res_fail_order['total_fee']} not eq $total_fee";
            }
        }else{
            echo 'Have withdrawal';
        }
    }
}