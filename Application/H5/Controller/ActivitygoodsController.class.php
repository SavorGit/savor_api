<?php
/*
 * 活动商品 积分兑换
 */
namespace H5\Controller;
use Think\Controller;

class ActivitygoodsController extends Controller {

    public function index(){
        $openid = I('get.openid','');
        $code = I('code', '');
        $ou = I('ou','');
        if(empty($openid) && empty($ou)){
            die('Parameter error');
        }
        if($code){
            $openid = $ou;
            $m_weixin_api = new \Common\Lib\Weixin_api();
            $result = $m_weixin_api->getWxOpenid($code);
            $mpopenid = $result['openid'];

            $m_user = new \Common\Model\Smallapp\UserModel();
            $where = array('openid'=>$openid);
            $data = array('mpopenid'=>$mpopenid);
            $m_user->updateInfo($where,$data);
        }else{
//            $m_user = new \Common\Model\Smallapp\UserModel();
//            $where = array('openid'=>$openid);
//            $user_info = $m_user->getOne('id,mpopenid',$where,'');
//            if(empty($user_info['mpopenid'])){
//                $url = http_host().'/h5/activitygoods/index/ou/'.$openid;
//                $this->wx_oauth($url);
//            }
        }
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $fields = 'id as goods_id,name,media_id,price,rebate_integral,start_time,end_time';
        $where = array('type'=>30,'status'=>2);
        $res_data = $m_goods->getDataList($fields,$where);
        $m_media = new \Common\Model\MediaModel();
        $goods = array();
        foreach ($res_data as $k=>$v){
            $minfo = $m_media->getMediaInfoById($v['media_id']);
            $info = array('goods_id'=>$v['goods_id'],'name'=>$v['name'],'price'=>$v['price'],'integral'=>$v['rebate_integral']);
            $info['oss_addr'] = $minfo['oss_addr'];
            $tips = date('Y-m-d',strtotime($v['end_time'])).'截止';
            $info['tips'] = $tips;
            $goods[] = $info;
        }
        $this->assign('openid',$openid);
        $this->assign('goods',$goods);
        $this->display();
    }

    public function integralExchange(){
        $openid = I('openid','');
        $goods_id = I('goods_id',0,'intval');

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $user_info = $m_user->getOne('id,mpopenid',$where,'');
        if(empty($user_info)){
            $res = array('code'=>10001,'msg'=>'用户信息错误');
            $this->ajaxReturn($res,'JSONP');
        }
        if(empty($user_info['mpopenid'])){
            $res = array('code'=>10002,'msg'=>'用户缺失openid,无法提现');
            $this->ajaxReturn($res,'JSONP');
        }
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if($res_goods['status']!=2 || $res_goods['type']!=30){
            $res = array('code'=>10003,'msg'=>'购买商品信息有误');
            $this->ajaxReturn($res,'JSONP');
        }
        $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
        $res_integral = $m_userintegral->getInfo(array('openid'=>$openid));
        if(empty($res_integral) || $res_integral['integral']<$res_goods['rebate_integral']){
            $res = array('code'=>10004,'msg'=>'用户积分低于兑换商品');
            $this->ajaxReturn($res,'JSONP');
        }
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $add_data = array('openid'=>$openid,'goods_id'=>$goods_id,
            'price'=>$res_goods['price'],'amount'=>1,'status'=>20,'otype'=>2);
        $m_order->add($add_data);

        $res = array('code'=>10000,'msg'=>'兑换申请成功,请在1到2个工作日内,注意查收自己的微信零钱');
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