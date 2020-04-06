<?php
namespace Smallapp44\Controller;
use \Common\Controller\CommonController as CommonController;

class CartController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'cartlist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'merchant_id'=>1001,'page'=>1001,'pagesize'=>1002);
                break;
            case 'addCart':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'goods_id'=>1002,'order_id'=>1002);
                break;
            case 'editCart':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'amount'=>1001,'cart_id'=>1001);
                break;
            case 'delCart':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'merchant_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function cartlist(){
        $openid = $this->params['openid'];
        $merchant_id = intval($this->params['merchant_id']);
        $page = intval($this->params['page']);
        $pagesize = $this->params['pagesize'];
        if(empty($pagesize)){
            $pagesize = 10;
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $all_nums = $page * $pagesize;
        $m_cart = new \Common\Model\Smallapp\CartModel();
        $fields = 'cart.id as cart_id,goods.id as goods_id,goods.name as goods_name,goods.price,goods.cover_imgs,cart.amount';
        $where = array('cart.openid'=>$openid,'cart.merchant_id'=>$merchant_id,'goods.status'=>1);
        $res_address = $m_cart->getList($fields,$where,'cart.id desc',0,$all_nums);
        $datalist = array();
        $amount = 0;
        if($res_address['total']){
            $res_amount = $m_cart->getCartAmount($merchant_id);
            $amount = intval($res_amount['total_amount']);
            $oss_host = "http://".C('OSS_HOST').'/';
            $datalist = $res_address['list'];
            foreach ($datalist as $k=>$v){
                $cover_imgs_info = explode(',',$v['cover_imgs']);
                $datalist[$k]['goods_img'] = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                unset($datalist[$k]['cover_imgs']);
            }
        }
        $res = array('datalist'=>$datalist,'amount'=>$amount);
        $this->to_back($res);
    }

    public function addCart(){
        $openid = $this->params['openid'];
        $goods_id = intval($this->params['goods_id']);
        $order_id = intval($this->params['order_id']);
        if(empty($goods_id) && empty($order_id)){
            $this->to_back(1001);
        }

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $all_goods = array();
        if($goods_id){
            $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
            if(empty($res_goods) || $res_goods['status']==2){
                $this->to_back(92020);
            }
            $all_goods[] = array('goods_id'=>$goods_id,'merchant_id'=>$res_goods['merchant_id']);
        }else{
            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
            $gfields = 'goods.id as goods_id,goods.merchant_id';
            $owhere = array('og.order_id'=>$order_id,'goods.status'=>1);
            $all_goods = $m_ordergoods->getOrdergoodsList($gfields,$owhere,'og.id asc');
        }
        $amount = 0;
        if(!empty($all_goods)){
            $m_cart = new \Common\Model\Smallapp\CartModel();
            foreach ($all_goods as $v){
                $data = array('openid'=>$openid,'merchant_id'=>$v['merchant_id'],'goods_id'=>$v['goods_id']);
                $res_cart = $m_cart->getInfo(array($data));
                if(!empty($res_cart)){
                    $amount = $res_cart['amount']+1;
                    $m_cart->updateData(array('id'=>$res_cart['id']),array('amount'=>$amount));
                }else{
                    $amount = 1;
                    $data['amount'] = $amount;
                    $m_cart->add($data);
                }
            }
            $res_amount = $m_cart->getCartAmount($all_goods[0]['merchant_id']);
            $amount = intval($res_amount['total_amount']);
        }
        $res = array('amount'=>$amount);
        $this->to_back($res);
    }

    public function editCart(){
        $openid = $this->params['openid'];
        $cart_id = intval($this->params['cart_id']);
        $amount = intval($this->params['amount']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_cart = new \Common\Model\Smallapp\CartModel();
        $res_cart = $m_cart->getInfo(array('id'=>$cart_id));
        if(empty($res_cart) || $res_cart['openid']!=$openid){
            $this->to_back(90133);
        }
        if($amount>0){
            $m_cart->updateData(array('id'=>$cart_id),array('amount'=>$amount));
        }else{
            $m_cart->delData(array('id'=>$cart_id));
        }
        $res_amount = $m_cart->getCartAmount($res_cart['merchant_id']);
        $amount = intval($res_amount['total_amount']);
        $this->to_back(array('amount'=>$amount));
    }

    public function delCart(){
        $merchant_id = intval($this->params['merchant_id']);
        $openid = $this->params['openid'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_cart = new \Common\Model\Smallapp\CartModel();
        $res_cart = $m_cart->getInfo(array('merchant_id'=>$merchant_id));
        if(!empty($res_cart)){
            $m_cart->delData(array('merchant_id'=>$merchant_id));
        }
        $this->to_back(array('amount'=>0));
    }


}