<?php
/**
 * @desc 餐厅端2.0-包间
 * @author zhang.yingtao
 * @since  20171220
 */
namespace Dinnerapp2\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class LabelController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addLabel':
                $this->is_verify = 1;
                $this->valid_fields = array(
                    'invite_id'     =>1001,
                    'mobile'        =>1001,
                    'customer_id'    =>1001,
                    'label_name'    =>1001,
                );
                break;
            case 'lightLabel':
                $this->is_verify = 1;
                $this->valid_fields = array(
                    'invite_id'     =>1001,
                    'mobile'        =>1001,
                    'customer_id'    =>1001,
                    'type'          =>1001,
                );
                break;
            case 'getCustomerLable':
                $this->is_verify = 1;
                $this->valid_fields = array(
                    'invite_id'     =>1001,
                    'mobile'        =>1001,
                );
        }
        parent::_init_();
    }


    public function getCustomerLable(){
        //获取销售经理标签
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
        $map['id']  = $this->params['customer_id'];
        $map['invite_id'] = $invite_id;
        $map['flag'] = 0;
        $m_dinner_cus = new \Common\Model\DinnerCustomerModel();
        $cus_num = $m_dinner_cus->countNums($map);
        if($cus_num > 0) {
            //获取客户标签
            $m_customer_lab = new \Common\Model\DinnerCustomerLabelModel();
            $customer_id  = $this->params['customer_id'];
            $map = array();
            $map['customer_id'] = $customer_id;
            $map['flag'] = 0;
            $field = 'label_id id';
            $label_info = $m_customer_lab->getData($field, $map);
            $cus_label = array();
            foreach($label_info as $lv) {
                $cus_label[$lv['id']] = 1;
            }

            //获取销售经理标签
            $offer['scl.invite_id'] = $invite_id;
            $offer['scl.flag'] = 0;
            $m_manna_lab = new \Common\Model\DinnerManaLabelModel();
            $field = 'scl.label_id,sdl.NAME label_name';
            $ma_info = $m_manna_lab->getLabelNameByCid($field, $offer);
            if($ma_info) {
                foreach($ma_info as $mk=>$mv) {
                    if(array_key_exists($mv['label_id'], $cus_label)) {
                        $ma_info[$mk]['light'] = 1;
                    } else {
                        $ma_info[$mk]['light'] = 0;
                    }
                }
            } else {
                $ma_info = array();
            }
            $data['list'] = $ma_info;
            $this->to_back($data);
        } else {
            $this->to_back(60116);
        }





    }


    public function lightLabel() {
        //type 2点亮3熄灭
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
        if ( !in_array($ptype, array(2,3)) ) {
            $this->to_back(60115);
        }
        $customer_id  = empty($this->params['customer_id'])?1:$this->params['customer_id'];
        //判断客户信息
        $jud['invite_id'] = $invite_id;
        $jud['flag'] = 0;
        $jud['id'] = $customer_id;
        $field = '*';
        $m_dinner_customer = new \Common\Model\DinnerCustomerModel();
        $cus_info = $m_dinner_customer->getOne($field, $jud);
        if($cus_info) {
            $label_id  = empty($this->params['label_id'])?0:$this->params['label_id'];
            if (empty($label_id) ) {
                $this->to_back(60111);
            }
            $m_dinner_label = new \Common\Model\DinnerLabelModel();
            $map['id'] = $label_id;
            $map['flag'] = 0;
            $field = 'id';
            $label_arr = $m_dinner_label->getData($field, $map);
            if($label_arr) {
                if($ptype == 2) {
                    //点亮图标获取label_id
                    //添加到总标签表
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
                                    $cp['update_time'] = date("Y-m-d H:i:s");
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

                }
                if($ptype == 3) {
                //熄灭图标
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
            } else {
                $this->to_back(60116);
            }

        } else {
            $this->to_back(60108);
        }

    }

    /*
     * @desc 添加，点亮，熄灭标签
     */
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
        $customer_id  = empty($this->params['customer_id'])?1:$this->params['customer_id'];
            $lname  = empty($this->params['label_name'])?'':$this->params['label_name'];
        //判断客户信息
        $jud['invite_id'] = $invite_id;
        $jud['flag'] = 0;
        $jud['id'] = $customer_id;
        $field = '*';
        $m_dinner_customer = new \Common\Model\DinnerCustomerModel();
        $cus_info = $m_dinner_customer->getOne($field, $jud);
        if($cus_info) {
            //空格处理
            //先判断标签库是否存在
            $m_dinner_label = new \Common\Model\DinnerLabelModel();
            $map = array();
            $map['name'] = $lname;
            $map['flag'] = 0;
            $field = 'id';
            $label_arr = $m_dinner_label->getData($field, $map);
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
                            $data = array();
                            $data['list'] = array(
                                'label_name'=>$lname,
                                'label_id'=>$label_id,
                            );
                            $this->to_back($data);
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
                            $data = array();
                            $data['list'] = array(
                                'label_name'=>$lname,
                                'label_id'=>$label_id,
                            );
                            $this->to_back($data);
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
                            $data = array();
                            $data['list'] = array(
                                'label_name'=>$lname,
                                'label_id'=>$label_id,
                            );
                            $this->to_back($data);
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
        } else {
            $this->to_back(60108);
        }
    }

}