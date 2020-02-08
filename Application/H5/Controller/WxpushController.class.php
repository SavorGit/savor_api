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
}