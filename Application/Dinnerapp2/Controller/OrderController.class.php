<?php
/**
 * @desc 餐厅端2.0-预订
 * @author zhang.yingtao
 * @since  20171220
 */
namespace Dinnerapp2\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class OrderController extends BaseController{
    private $pagesize = 20;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addRoom':
                $this->is_verify = 1;
                $this->valid_fields = array('invite_id'=>1001,'mobile'=>1001,'order_date'=>1001,'order_id'=>1000);
                break;
            
        }
        parent::_init_();
    }
    public function getOrderList(){
        $invite_id  = $this->params['invite_id'];
        $mobile     = $this->params['mobile'];    //用户手机号
        $order_date = $this->params['order_date'];     //预订时间 
        $order_id   = $this->params['order_id'];
        if(!check_mobile($mobile)){
            $this->to_back('60002');
        }
        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $where = array();
        $where['id'] = $invite_id;
        $where['state'] = 1;
        $where['flag'] = '0';
        $invite_info = $m_hotel_invite_code->getOne('bind_mobile,hotel_id', $where);
        if(empty($invite_id)){
            $this->to_back(60018);
        }
        if($invite_info['bind_mobile'] != $mobile){
            $this->to_back(60019);
        }
        
        echo "aaa";exit;
    }
    public function addOrder(){
        $invite_id  = $this->params['invite_id'];
        $mobile     = $this->params['mobile'];    //用户手机号
        if(!check_mobile($mobile)){
            $this->to_back('60002');
        }
        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $where = array();
        $where['id'] = $invite_id;
        $where['state'] = 1;
        $where['flag'] = '0';
        $invite_info = $m_hotel_invite_code->getOne('bind_mobile,hotel_id', $where);
        if(empty($invite_id)){
            $this->to_back(60018);
        }
        if($invite_info['bind_mobile'] != $mobile){
            $this->to_back(60019);
        }
        $order_name   = $this->params['order_name'];
        $order_mobile = $this->params['order_mobile'];
        $m_dinner_customer = new \Common\Model\DinnerCustomerModel();
        $where = " `mobile`='$order_mobile' or `mobile1`='$order_mobile'";
        $customer_info = $m_dinner_customer->getOne('id',$where);
        if(empty($customer_info)){
            $data = array();
            $data['invite_id'] = $invite_id;
            $data['name']      = $order_name;
            $data['mobile']    = $order_mobile;
            $m_dinner_customer->add($data);
            $customer_id = $m_dinner_customer->getLastInsID();
        }else {
            $customer_id = $customer_info['id'];
        }
        
        $data = array();
        
        $data['invite_id']     = $invite_id;
        $data['customer_id']   = $customer_id; 
        $data['hotel_id']      = $invite_info['hotel_id'];
        $data['order_name']    = $this->params['order_name'];
        $data['order_mobile']  = $this->params['order_mobile'];
        $data['person_nums']   = $this->params['person_nums'];
        $data['room_id']       = $this->params['room_id'];
        $data['room_type']     = $this->params['room_type'];
        $data['order_time']    = $this->params['order_time'];
        $data['remark']        = $this->params['remark'];
        $m_dinner_order = new \Common\Model\DinnerOrderModel();
        $ret = $m_dinner_order->addInfo($data);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(60023);
        }
    }
}