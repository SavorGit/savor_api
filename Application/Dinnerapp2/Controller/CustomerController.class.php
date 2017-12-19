<?php
/**
 * @desc 客户信息管理
 * @author baiyutao
 * @date  20171219
 */
namespace Dinnerapp2\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class CustomerController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addCustom':
                $this->is_verify = 1;
                $this->valid_fields = array(
                    'invite_id'     =>1001,
                    'mobile'        =>1001,
                    'usermobile'    =>1001,
                    'name'          =>1001,
                );
                break;
            case 'importInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('invite_id'=>1001,'mobile'=>1001,'book_info'=>1001);
                break;
            default:
                 break;
        }
        parent::_init_();
        $this->vcode_valid_time =  600;
    }
    /**
     * @desc 导入通讯录
     */
    public function  importInfo(){
        $invite_id = $this->params['invite_id'];
        $mobile   = $this->params['mobile'];    //用户手机号
        //$hotel_id = $this->params['hotel_id'];  //酒楼id
        $book_info= $this->params['book_info']; //通讯录列表
    
        if(!check_mobile($mobile)){
            $this->to_back('60002');
        }
        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $where = array();
        $where['id'] = $invite_id;
        $where['state'] = 1;
        $where['flag'] = '0';
        $invite_info = $m_hotel_invite_code->getOne('bind_mobile', $where);
        if(empty($invite_id)){
            $this->to_back(60018);
        }
        if($invite_info['bind_mobile'] != $mobile){
            $this->to_back(60019);
        }
        $m_dinner_customer = new \Common\Model\DinnerCustomerModel();
        $where = array();
        $where['invite_id'] = $invite_id;
        $where['flag']      =0;
        $customer_nums = $m_dinner_customer->countNums($where);
        if(!empty($customer_nums)){
            $this->to_back(60020);
        }
        
        $book_info = str_replace('\\', '', $book_info);
        $book_info =  json_decode($book_info,true);
        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $fields = 'id';
        $where = array();
        $where['bind_mobile'] = $mobile;
        $where['state'] = 1;
        $where['flag']  = 0;
        $info = $m_hotel_invite_code->getOne($fields, $where);
        if(empty($info)){
            $this->to_back(60015);
        }
        if(!empty($book_info)){
            foreach($book_info as $key=>$v){
                $book_info[$key]['invite_id'] = $info['id'];
            }
            
            $ret = $m_dinner_customer->addList($book_info);
            if($ret){
                $this->to_back(10000);
            }else {
                $this->to_back(60016);
            }
        }else {
            $this->to_back(60017);
        }
    
    }
    public function addCustom() {
        $mobile = $this->params['mobile'];
        //验证手机格式
        if(!check_mobile($mobile)){
            $this->to_back(60002);
        }
        $invite_id = $this->params['invite_id'];
        if(!is_numeric($invite_id)) {
            $this->to_back(60100);
        }
        //判断用户名是否存在
        //invite_id  查出得数据为空 60018

        $username    = $this->params['name'];
        $username    = $this->params['name'];
        $username    = $this->params['name'];
        $username    = $this->params['name'];
        $username    = $this->params['name'];
        $username    = $this->params['name'];
        $username    = $this->params['name'];
    }


}