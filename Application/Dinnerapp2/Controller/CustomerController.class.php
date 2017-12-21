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
        $flag = 0;
        if(!empty($book_info)){
            //print_r($book_info);exit;
            foreach($book_info as $key=>$v){
                $where = '';
                if(!empty($v['mobile'])){//第一个手机号不为空
                    $where .= " (mobile='".$v['mobile']."'";
                }
                if(!empty($v['mobile1'])){//第二个手机号不为空
                    if(empty($where)){
                        $where .=" (mobile1='".$v['mobile']."'";
                    }else{
                        $where .=" or  mobile1='".$v['mobile']."'";
                    }
                }
                if(!empty($where)){
                    $where .=") and invite_id=$invite_id";
                    $nums = $m_dinner_customer->countNums($where);
                    
                    if(!empty($nums)){
                        continue;
                    }
                }else {
                     $v['invite_id'];
                     $m_dinner_customer->add($v); 
                }
                $flag ++;  
            }
            if($flag){
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

    public function addLabel() {
        //type 1新增2点亮
        $invite_id = $this->params['invite_id'];
        $mobile   = $this->params['mobile'];    //销售手机号
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
        $ptype  = empty($this->params['type'])?1:$this->params['type'];
        $customer_id  = empty($this->params['customerid'])?1:$this->params['customerid'];
        if($ptype == 1) {
            $lname  = empty($this->params['label_name'])?1:$this->params['label_name'];

            //空格处理
            //先判断标签库是否存在
            $m_dinner_label = new \Common\Model\DinnerLabelModel();
            $map['name'] = $lname;
            $map['flag'] = 0;
            $field = 'id';
            $label_arr = $m_dinner_label->getData($field, $where);
            if($label_arr) {
                $label_id = $label_arr[0]['id'];
                //添加到销售标签表
                $m_dinner_mana_lab = new \Common\Model\DinnerManaLabelModel();
                $ma_ar['invite_id'] = $invite_id;
                $ma_ar['label_id'] = $label_id;
                $ma_ar['flag'] = 0;
                $ma_num = $m_dinner_mana_lab->countNums($ma_ar);
                if($ma_num > 0) {
                    //添加到客户端
                    $m_customer_lab = new \Common\Model\DinnerCustomerLabelModel();
                    $cus['customer_id'] = $customer_id;
                    $cus['label_id'] = $label_id;
                    $cus['flag'] = 0;
                    $cus_num = $m_customer_lab->countNums($cus);
                    if($cus_num) {
                        $this->to_back(10000);
                    } else {
                        $bool = $m_customer_lab->addData($cus);
                        if($bool) {
                            $this->to_back(10000);
                        } else {
                            $this->to_back(60109);
                        }
                    }
                } else {
                    $bool = $m_dinner_mana_lab->addData($ma_ar);
                    if($bool) {
                        //添加到客户标签表
                        $m_customer_lab = new \Common\Model\DinnerCustomerLabelModel();
                        $cus['customer_id'] = $customer_id;
                        $cus['label_id'] = $label_id;
                        $bool = $m_customer_lab->add($cus);
                        if($bool) {
                            $this->to_back(10000);
                        } else {
                            $this->to_back(60109);
                        }
                    } else {
                        $this->to_back(60109);
                    }

                }

            } else {
                //添加到总标签表
                $m_dinner_lab = new \Common\Model\DinnerLabelModel();
                $dl['name'] = $lname;
                $bool = $m_dinner_label->addData($dl);
                if($bool) {
                    $label_id = $m_dinner_label->getLastInsID();
                    //添加到销售标签表
                    $m_dinner_mana_lab = new \Common\Model\DinnerManaLabelModel();
                    $ma_ar['invite_id'] = $invite_id;
                    $ma_ar['label_id'] = $label_id;
                    $bool = $m_dinner_mana_lab->add($ma_ar);
                    if($bool) {
                        //添加到客户标签表
                        $m_customer_lab = new \Common\Model\DinnerCustomerLabelModel();
                        $cus['customer_id'] = $customer_id;
                        $cus['label_id'] = $label_id;
                        $bool = $m_customer_lab->add($cus);
                        if($bool) {
                            $this->to_back(10000);
                        } else {
                            $this->to_back(60109);
                        }
                    } else {
                        $this->to_back(60109);
                    }

                }else{
                    $this->to_back(60109);
                }

            }

        }
        if($ptype == 2) {
            //点亮图标获取label_id
            $label_id  = empty($this->params['label_id'])?0:$this->params['label_id'];
            if (empty($label_id) ) {
                $this->to_back(60111);
            }
            //添加到总标签表
            $m_dinner_label = new \Common\Model\DinnerLabelModel();
            $map['label_id'] = $label_id;
            $map['flag'] = 0;
            $field = 'id';
            $label_arr = $m_dinner_label->getData($field, $where);
            if($label_arr) {
                //添加到销售标签表
                $m_dinner_mana_lab = new \Common\Model\DinnerManaLabelModel();
                $ma_ar['invite_id'] = $invite_id;
                $ma_ar['label_id'] = $label_id;
                $ma_ar['flag'] = 0;
                $ma_num = $m_dinner_mana_lab->countNums($ma_ar);
                if($ma_num > 0) {
                    //添加到客户端
                    $m_customer_lab = new \Common\Model\DinnerCustomerLabelModel();
                    $cus['customer_id'] = $customer_id;
                    $cus['label_id'] = $label_id;
                    $fields = 'id,flag';
                    $cus_info = $m_customer_lab->getOne($fields, $cus);
                    if($cus_info) {
                        $cflag = $cus_info['flag'];
                        if($cflag == 0) {
                            $this->to_back(10000);
                        } else {
                            $cp['id'] = $cus_info['id'];
                            $cuso['flag'] = 0;
                            $bool = $m_customer_lab->saveData($cuso, $cp);
                            if($bool) {
                                $this->to_back(10000);
                            } else {
                                $this->to_back(60109);
                            }
                        }


                    } else {
                        $bool = $m_customer_lab->addData($cus);
                        if($bool) {
                            $this->to_back(10000);
                        } else {
                            $this->to_back(60109);
                        }
                    }
                } else {
                    $bool = $m_dinner_mana_lab->addData($ma_ar);
                    if($bool) {
                        //添加到客户标签表
                        $m_customer_lab = new \Common\Model\DinnerCustomerLabelModel();
                        $cus['customer_id'] = $customer_id;
                        $cus['label_id'] = $label_id;
                        $bool = $m_customer_lab->add($cus);
                        if($bool) {
                            $this->to_back(10000);
                        } else {
                            $this->to_back(60109);
                        }
                    } else {
                        $this->to_back(60109);
                    }

                }
            } else {
                $this->to_back(60111);
            }
        }
        if($ptype == 3) {
            //熄灭图标
            $label_id  = empty($this->params['label_id'])?0:$this->params['label_id'];
            if (empty($label_id) ) {
                $this->to_back(60111);
            } else {
                $m_customer_lab = new \Common\Model\DinnerCustomerLabelModel();
                $cus['customer_id'] = $customer_id;
                $cus['label_id'] = $label_id;
                $mp['flag'] = 1;
                $bool = $m_customer_lab->saveData($mp, $cus);
                if($bool) {
                    $this->to_back(10000);
                }else {
                    $this->to_back(60112);
                }
            }

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
                $m_dinner_customer_log = new \Common\Model\DinnerActionLogModel();
                $log_arr['action_id'] = $insid;
                $log_arr['type'] = 1;
                $m_dinner_customer_log->addData($log_arr);
                $this->to_back(10000);
            } else {
                $this->to_back(60101);
            }
        } else {
           $c_id  = empty($this->params['customerid'])?0:$this->params['customerid'];
            $map = array();
            $map['invite_id'] = $invite_id;
            $map['flag'] = 0;
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
                        $m_dinner_customer_log = new \Common\Model\DinnerActionLogModel();
                        $log_arr['action_id'] = $c_id;
                        $log_arr['type'] = 2;
                        $m_dinner_customer_log->addData($log_arr);
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

    }


}