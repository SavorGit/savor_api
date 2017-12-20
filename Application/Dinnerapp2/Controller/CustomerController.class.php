<?php
/**
 * @desc 餐厅端2.0-客户信息管理
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
                    'type'          =>1001,
                );
                break;
            case 'upLabel':
                $this->is_verify = 1;
                $this->valid_fields = array(
                    'invite_id'     =>1001,
                    'mobile'        =>1001,
                    'customerid'    =>1001,
                );
            case 'addConsumeRecord':
                $this->is_verify = 1;
                $this->valid_fields = array(
                    'invite_id'     =>1001,
                    'mobile'        =>1001,
                    'customerid'    =>1001,
                );
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

    public function addConsumeRecord() {

    }

    public function upLabel() {
        //type 1批量新增2单个
        $ptype  = empty($this->params['type'])?1:$this->params['type'];
        $mobile = $this->params['mobile'];
        //验证管理人手机格式
        if(!check_mobile($mobile)){
            $this->to_back(60002);
        }
        $invite_id = $this->params['invite_id'];
        if(!is_numeric($invite_id)) {
            $this->to_back(60100);
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
        $save['label']   = empty($this->params['label'])?'':$this->params['label'];
        $c_id  = $this->params['customerid'];
        $m_dinner_customer = new \Common\Model\DinnerCustomerModel();
        if($c_id) {
            $cus_info = $m_dinner_customer->find($c_id);
            if($cus_info) {
                $map['id'] = $c_id;
                $bool = $m_dinner_customer->saveData($save, $map);
                if ($bool) {
                   $this->to_back(10000);
                } else {
                    $this->to_back(60109);
                }
            } else {
                $this->to_back(60108);
            }

        } else {
            $this->to_back(60107);
        }

    }

    public function addCustom() {
        //type 1增加 2修改
        $mobile = $this->params['mobile'];
        //验证管理人手机格式
        if(!check_mobile($mobile)){
            $this->to_back(60002);
        }
        $invite_id = $this->params['invite_id'];
        if(!is_numeric($invite_id)) {
            $this->to_back(60100);
        }
        //客户手机
        $usermobile_str = $this->params['usermobile'];
        $usermobile_str = str_replace('\\','',$usermobile_str);
        $usermobile_arr = json_decode($usermobile_str, true);
        $tel_a = $usermobile_arr[0];
        $tel_b = $usermobile_arr[1];
        if (empty($tel_a) && empty($tel_b)) {
            $this->to_back(60104);
        }
        if ($tel_a == $tel_b) {
            $this->to_back(60103);
        }

        //验证手机格式
        foreach ($usermobile_arr as $uv ) {
            if(!empty($uv) &&!check_mobile($uv)){
                $this->to_back(60002);
            }
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

        //判断用户名是否存在
        $ptype = empty($this->params['type'])?1:$this->params['type'];
        $username    = empty($this->params['name'])?'':$this->params['name'];
        $m_dinner_customer = new \Common\Model\DinnerCustomerModel();
        $save['name']                = $username;
        $save['sex']                = empty($this->params['sex'])?1:$this->params['sex'];
        $save['birthplace']         = empty($this->params['birthplace'])?'':$this->params['birthplace'];
        $save['birthday']           = empty($this->params['birthday'])?0:$this->params['birthday'];
        $save['invite_id']          = $invite_id;
        $save['consume_ability']    = empty($this->params['consume_ability'])?1:$this->params['consume_ability'];
        $save['bill_info']    = empty($this->params['bill_info'])?'':$this->params['bill_info'];
        $save['remark']    = empty($this->params['remark'])?'':$this->params['remark'];
        $save['flag']               = 0;
        $fimg = empty($this->params['face_url'])?0:$this->params['face_url'];
        $face_arr = parse_url($fimg);
        $save['face_url'] = $face_arr['path'];
        if($ptype == 1) {
            $map = array();
            $map['invite_id'] = $invite_id;
            $map['flag'] = 0;
            if(empty($tel_a)) {
                $mp = array();
                $mp['mobile'] = $tel_b;
                $mp['mobile1'] = $tel_b;
                $mp['_logic'] = 'or';
                $map['_complex'] = $mp;
                $d_res = $m_dinner_customer->countNums($map);
                if($d_res) {
                    $this->to_back(60106);
                }
                $save['mobile1'] = $tel_b;
            }
            if(empty($tel_b)) {
                $mp = array();
                $mp['mobile'] = $tel_b;
                $mp['mobile1'] = $tel_b;
                $mp['_logic'] = 'or';
                $map['_complex'] = $mp;
                $d_res = $m_dinner_customer->countNums($map);
                if($d_res) {
                    $this->to_back(60105);
                }
                $save['mobile'] = $tel_a;
            }
            if(!empty($tel_a) && !empty($tel_b)) {
                $field = 'id,mobile,mobile1';
                $map['_string'] = " (mobile like '".$tel_a."') or
            (mobile1 like '".$tel_a."') or (mobile like '".$tel_b."')
             or (mobile1 like '".$tel_b."') ";
                $d_res = $m_dinner_customer->getData($field, $map);
                if($d_res) {
                    $mobile = $d_res[0]['mobile'];
                    $mobil1 = $d_res[0]['mobile1'];
                    if($tel_a == $mobil1 || $tel_a == $mobile) {
                        $this->to_back(60105);
                    }
                    if($tel_b == $mobil1 || $tel_b == $mobile) {
                        $this->to_back(60106);
                    }
                }
                $save['mobile'] = $tel_a;
                $save['mobile1'] = $tel_b;
            }
            $insid = $m_dinner_customer->addData($save);
            if($insid) {
                $m_dinner_customer_log = new \Common\Model\DinnerCustomerHistroyLogModel();
                $log_arr['action_id'] = $insid;
                $log_arr['type'] = $insid;
                $m_dinner_customer_log->addData($log_arr);
                $this->to_back(10000);
            } else {
                $this->to_back(60101);
            }
        } else {
           $c_id  = empty($this->params['customerid'])?0:$this->params['customerid'];
            if($c_id) {
                $cus_info = $m_dinner_customer->find($c_id);
                if($cus_info) {
                    $mobile = $cus_info['mobile'];
                    $mobile1 = $cus_info['mobile1'];
                    //没更新改过
                    $map = array();
                    if($mobile == $tel_a || $mobile1 == $tel_b) {
                        $map['id'] = $c_id;
                        $bool = $m_dinner_customer->saveData($save, $map);

                    }else {
                        $field = 'id,mobile,mobile1';
                        if(empty($tel_a)) {
                            $mp = array();
                            $mp['mobile'] = $tel_b;
                            $mp['mobile1'] = $tel_b;
                            $mp['_logic'] = 'or';
                            $map['_complex'] = $mp;
                            $d_res = $m_dinner_customer->countNums($map);
                            if($d_res) {
                                $this->to_back(60106);
                            }
                            $save['mobile1'] = $tel_b;
                        }
                        if(empty($tel_b)) {
                            $mp = array();
                            $mp['mobile'] = $tel_b;
                            $mp['mobile1'] = $tel_b;
                            $mp['_logic'] = 'or';
                            $map['_complex'] = $mp;
                            $d_res = $m_dinner_customer->countNums($map);
                            if($d_res) {
                                $this->to_back(60105);
                            }
                            $save['mobile'] = $tel_a;
                        }
                        if(!empty($tel_a) && !empty($tel_b)) {
                            $field = 'id,mobile,mobile1';
                            $map['_string'] = " (mobile like '".$tel_a."') or
            (mobile1 like '".$tel_a."') or (mobile like '".$tel_b."')
             or (mobile1 like '".$tel_b."') ";
                            $d_res = $m_dinner_customer->getData($field, $map);
                            if($d_res) {
                                $mobile = $d_res[0]['mobile'];
                                $mobil1 = $d_res[0]['mobile1'];
                                if($tel_a == $mobil1 || $tel_a == $mobile) {
                                    $this->to_back(60105);
                                }
                                if($tel_b == $mobil1 || $tel_b == $mobile) {
                                    $this->to_back(60106);
                                }
                            }
                            $save['mobile'] = $tel_a;
                            $save['mobile1'] = $tel_b;
                        }
                        $bool = $m_dinner_customer->saveData($save, $map);
                    }
                    if($bool) {
                        $this->to_back(10000);
                    } else {
                        $this->to_back(60110);
                    }
                } else {
                    $this->to_back(60108);
                }

            } else {
                $this->to_back(60107);
            }
        }




















        if(empty($d_res)) {
            if(empty($tel_a)) {
                $save['mobile1'] = $tel_b;
            }
            if(empty($tel_b)) {
                $save['mobile'] = $tel_a;
            }
            if(!empty($tel_a) && !empty($tel_b)) {
                $save['mobile'] = $tel_a;
                $save['mobile1'] = $tel_b;
            }
            $bool = $m_dinner_customer->addData($save);
        } else {
            $mo_arr = array_merge($d_res[0]['mobile'], $d_res[0]['mobilea']);

            $m_len = count($mo_arr);
            if(in_array($usermobile_arr, $mo_arr)) {
                $this->to_back(60102);
            } else {
                if($tel_count == 1) {
                    $save['mobile'] = $usermobile_arr[0];
                } else {
                    $save['mobile'] = $usermobile_arr[0];
                    $save['mobilea'] = $usermobile_arr[1];
                }
                $bool = $m_dinner_customer->addData($save);
            }
        }

    }


}