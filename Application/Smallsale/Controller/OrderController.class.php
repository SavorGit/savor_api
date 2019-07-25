<?php
namespace Smallsale\Controller;
use \Common\Controller\CommonController as CommonController;

class OrderController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addOrder':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'amount'=>1001,'buy_type'=>1001,'box_mac'=>1001,'openid'=>1001);
                break;
        }
        parent::_init_();
    }

    public function addOrder(){
        $goods_id= intval($this->params['goods_id']);
        $amount = intval($this->params['amount']);
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $buy_type = intval($this->params['buy_type']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if($res_goods['status']!=2){
            $this->to_back(92020);
        }
        $m_box = new \Common\Model\BoxModel();
        $map = array();
        $map['a.mac'] = $box_mac;
        $map['a.state'] = 1;
        $map['a.flag']  = 0;
        $map['d.state'] = 1;
        $map['d.flag']  = 0;
        $fields = 'a.id as box_id,ext.activity_contact,ext.activity_phone';
        $box_info = $m_box->getBoxInfo($fields, $map);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $total_fee = sprintf("%.2f",$amount*$res_goods['price']);
        $add_data = array('openid'=>$openid,'box_mac'=>$box_mac,'goods_id'=>$goods_id,
            'price'=>$res_goods['price'],'amount'=>$amount,'total_fee'=>$total_fee,
            'status'=>10,'buy_type'=>$buy_type);
        $m_order->add($add_data);
        $res_data = array('message'=>'购买成功');
        $this->to_back($res_data);
    }



}