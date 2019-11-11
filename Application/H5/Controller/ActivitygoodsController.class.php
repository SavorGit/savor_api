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
            $m_user = new \Common\Model\Smallapp\UserModel();
            $where = array('openid'=>$openid);
            $user_info = $m_user->getOne('id,mpopenid',$where,'');
            if(empty($user_info['mpopenid'])){
                $url = http_host().'/h5/activitygoods/index/ou/'.$openid;
                $this->wx_oauth($url);
            }
        }

        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $res_invite = $m_hotel_invite_code->getOne('hotel_id',array('openid'=>$openid));
        $hotel_id = $res_invite['hotel_id'];
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $fields = 'g.id as goods_id,g.name,g.media_id,g.price,g.rebate_integral,g.start_time,g.end_time,g.type as goods_type';
        $where = array('h.hotel_id'=>$hotel_id,'g.status'=>2);
        $where['g.type'] = array('in',array(30,31));
        $nowtime = date('Y-m-d H:i:s');
        $where['g.end_time'] = array('egt',$nowtime);
        $res_data = $m_hotelgoods->getList($fields,$where,'g.id desc','');
        $m_media = new \Common\Model\MediaModel();
        $goods = array();
        foreach ($res_data as $k=>$v){
            $minfo = $m_media->getMediaInfoById($v['media_id']);
            $info = array('goods_id'=>$v['goods_id'],'name'=>$v['name'],'price'=>$v['price'],
                'integral'=>$v['rebate_integral'],'goods_type'=>$v['goods_type']);
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
        $total_fee = sprintf("%.2f",1*$res_goods['price']);
        $m_order = new \Common\Model\Smallapp\ExchangeModel();
        $add_data = array('openid'=>$openid,'goods_id'=>$goods_id,
            'price'=>$res_goods['price'],'amount'=>1,'total_fee'=>$total_fee,'status'=>20);
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