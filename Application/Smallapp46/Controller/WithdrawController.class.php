<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
class WithdrawController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'editIdinfo':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'name'=>1001,'idnumber'=>1001);
                break;
            case 'wxchange':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'money'=>1001);
                break;
        }
        parent::_init_();
    }

    public function editIdinfo(){
        $openid = $this->params['openid'];
        $name = $this->params['name'];
        $idnumber = $this->params['idnumber'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1,'status'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_user->updateInfo(array('id'=>$user_info['id']),array('name'=>$name,'idnumber'=>$idnumber));
        $this->to_back(array());
    }

    public function wxchange(){
        $openid = $this->params['openid'];
        $money = floatval($this->params['money']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1,'status'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }

        $max_money = 500;
        $exchange_num = 5;
        $exchange_key = C('SAPP_EXCHANGE');
        $cache_key = $exchange_key.'openid'.$openid.date('Ymd');
        $space_cache_key = $exchange_key.'spacetime:'.$openid;
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $res_spacecache = $redis->get($space_cache_key);
        if(!empty($res_spacecache)){
            $this->to_back(90201);
        }
        $res_cache = $redis->get($cache_key);
        if(!empty($res_cache)){
            $order_exchange = json_decode($res_cache,true);
            if(count($order_exchange)>=$exchange_num){
                $this->to_back(90202);
            }
        }else{
            $order_exchange = array();
        }

        $m_userpurse = new \Common\Model\Smallapp\UserpurseModel();
        $res_purse = $m_userpurse->getInfo(array('openid'=>$openid));
        if(empty($res_purse) || $money>$res_purse['money'] || $money==0){
            $this->to_back(90203);
        }

        if($money>$max_money){
            $this->to_back(90204);
        }
        $now_money = $res_purse['money']-$money;
        $m_userpurse->updateData(array('id'=>$res_purse['id']),array('money'=>$now_money,'update_time'=>date('Y-m-d H:i:s')));

        $total_fee = $money;
        $m_exchange = new \Common\Model\Smallapp\ExchangeModel();
        $add_data = array('openid'=>$openid,'goods_id'=>0,'price'=>0,'type'=>7,
            'amount'=>1,'total_fee'=>$total_fee,'status'=>20);
        $order_exchange_id = $m_exchange->add($add_data);
        $order_exchange[] = array($order_exchange_id=>date('Y-m-d H:i:s'));
        $redis->select(5);
        $redis->set($cache_key,json_encode($order_exchange),86400);
        $redis->set($space_cache_key,$order_exchange_id,60);

        $m_baseinc = new \Payment\Model\BaseIncModel();
        $payconfig = $m_baseinc->getPayConfig();
        $trade_info = array('trade_no'=>$order_exchange_id,'money'=>$total_fee,'open_id'=>$openid);
        $m_wxpay = new \Payment\Model\WxpayModel();
        $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
        if($res['code']==10000){
            $m_exchange->updateData(array('id'=>$order_exchange_id),array('status'=>21));
        }
        $m_paylog = new \Common\Model\Smallapp\PaylogModel();
        $pay_data = array('order_id'=>$order_exchange_id,'openid'=>$openid,
            'wxorder_id'=>$order_exchange_id,'pay_result'=>json_encode($res));
        $m_paylog->add($pay_data);

        $message = '您已提现成功，请注意查收。';
        $tips = '可能会因为网络问题有延迟到账情况，请耐心等待。';
        $res = array('message'=>$message,'tips'=>$tips);
        $this->to_back($res);
    }

}