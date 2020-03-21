<?php
namespace Smallapp43\Controller;
use \Common\Controller\CommonController as CommonController;

class AddressController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addresslist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001,'pagesize'=>1002);
                break;
            case 'addAddress':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'consignee'=>1001,'phone'=>1001,
                    'area_id'=>1001,'county_id'=>1001,'address'=>1001,'is_default'=>1002);
                break;
            case 'editAddress':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'address_id'=>1001,'consignee'=>1001,'phone'=>1001,
                    'area_id'=>1001,'county_id'=>1001,'address'=>1001,'is_default'=>1002);
                break;
            case 'delAddress':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'address_id'=>1001);
                break;
            case 'getDefaultAddress':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'setDefaultAddress':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'address_id'=>1001,'is_default'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'address_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function addresslist(){
        $openid = $this->params['openid'];
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
        $m_address = new \Common\Model\Smallapp\AddressModel();
        $where = array('openid'=>$openid,'status'=>1);
        $all_nums = $page * $pagesize;
        $res_address = $m_address->getDataList('*',$where,'id desc',0,$all_nums);
        $datalist = array();
        if($res_address['total']){
            $m_area = new \Common\Model\AreaModel();
            foreach ($res_address['list'] as $v){
                $info = array('address_id'=>$v['id'],'consignee'=>$v['consignee'],'phone'=>$v['phone'],
                    'area_id'=>$v['area_id'],'county_id'=>$v['county_id'],'address'=>$v['address'],
                    'is_default'=>intval($v['is_default']));
                $phone_str = substr_replace($v['phone'], '****', 3, 4);
                $res_area = $m_area->find($v['area_id']);
                $res_county = $m_area->find($v['county_id']);
                $detail_address = $res_area['region_name'].$res_county['region_name'].$v['address'];
                $info['phone_str'] = $phone_str;
                $info['detail_address'] = $detail_address;
                $datalist[]=$info;
            }
        }
        $this->to_back($datalist);
    }

    public function detail(){
        $address_id = intval($this->params['address_id']);
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_address = new \Common\Model\Smallapp\AddressModel();
        $res_address = $m_address->getInfo(array('id'=>$address_id));
        if(empty($res_address) || $res_address['openid']!=$openid){
            $this->to_back(90132);
        }

        $m_area = new \Common\Model\AreaModel();
        $info = array('address_id'=>$res_address['id'],'consignee'=>$res_address['consignee'],'phone'=>$res_address['phone'],
            'area_id'=>$res_address['area_id'],'county_id'=>$res_address['county_id'],'address'=>$res_address['address'],
            'is_default'=>intval($res_address['is_default']));
        $phone_str = substr_replace($res_address['phone'], '****', 3, 4);
        $res_area = $m_area->find($res_address['area_id']);
        $res_county = $m_area->find($res_address['county_id']);
        $detail_address = $res_area['region_name'].$res_county['region_name'].$res_address['address'];
        $info['phone_str'] = $phone_str;
        $info['detail_address'] = $detail_address;

        $this->to_back($info);
    }

    public function addAddress(){
        $openid = $this->params['openid'];
        $consignee = $this->params['consignee'];
        $phone = $this->params['phone'];
        $area_id = intval($this->params['area_id']);
        $county_id = intval($this->params['county_id']);
        $address = $this->params['address'];
        $is_default = intval($this->params['is_default']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $is_check = check_mobile($phone);
        if(!$is_check){
            $this->to_back(93006);
        }

        $data = array('openid'=>$openid,'consignee'=>$consignee,'phone'=>$phone,
            'area_id'=>$area_id,'county_id'=>$county_id,'address'=>$address,
            'is_default'=>$is_default,'status'=>1);

        $m_area = new \Common\Model\AreaModel();
        $res_area = $m_area->find($area_id);
        $res_county = $m_area->find($county_id);
        $detail_address = $res_area['region_name'].$res_county['region_name'].$address;
        $res_location = getGDgeocodeByAddress($detail_address);
        if(empty($res_location)){
            $res_location = getGDgeocodeByAddress($detail_address);
        }
        if(!empty($res_location)){
            $data['lng'] = $res_location['lng'];
            $data['lat'] = $res_location['lat'];
        }

        $m_address = new \Common\Model\Smallapp\AddressModel();
        $address_id = $m_address->add($data);
        if($is_default){
            $res_default = $m_address->getInfo(array('openid'=>$openid,'is_default'=>1));
            if(!empty($res_default) && $res_default['id']!=$address_id){
                $m_address->updateData(array('id'=>$res_default['id']),array('is_default'=>0));
            }
        }

        $this->to_back(array());
    }

    public function setDefaultAddress(){
        $address_id = intval($this->params['address_id']);
        $openid = $this->params['openid'];
        $is_default = intval($this->params['is_default']);
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_address = new \Common\Model\Smallapp\AddressModel();
        $res_address = $m_address->getInfo(array('id'=>$address_id));
        if(empty($res_address) || $res_address['openid']!=$openid){
            $this->to_back(90132);
        }
        if($is_default){
            $res_default = $m_address->getInfo(array('openid'=>$openid,'is_default'=>1));
            if(!empty($res_default) && $res_default['id']!=$address_id){
                $m_address->updateData(array('id'=>$res_default['id']),array('is_default'=>0));
            }
        }
        $data = array('openid'=>$openid,'is_default'=>$is_default,'status'=>1,
            'update_time'=>date('Y-m-d H:i:s'));
        $m_address->updateData(array('id'=>$address_id),$data);
        $this->to_back(array());


    }
    public function editAddress(){
        $address_id = intval($this->params['address_id']);
        $openid = $this->params['openid'];
        $consignee = $this->params['consignee'];
        $phone = $this->params['phone'];
        $area_id = intval($this->params['area_id']);
        $county_id = intval($this->params['county_id']);
        $address = $this->params['address'];
        $is_default = intval($this->params['is_default']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_address = new \Common\Model\Smallapp\AddressModel();
        $res_address = $m_address->getInfo(array('id'=>$address_id));
        if(empty($res_address) || $res_address['openid']!=$openid){
            $this->to_back(90132);
        }
        $is_check = check_mobile($phone);
        if(!$is_check){
            $this->to_back(93006);
        }
        if($is_default){
            $res_default = $m_address->getInfo(array('openid'=>$openid,'is_default'=>1));
            if(!empty($res_default) && $res_default['id']!=$address_id){
                $m_address->updateData(array('id'=>$res_default['id']),array('is_default'=>0));
            }
        }

        $data = array('openid'=>$openid,'consignee'=>$consignee,'phone'=>$phone,
            'area_id'=>$area_id,'county_id'=>$county_id,'address'=>$address,
            'is_default'=>$is_default,'status'=>1,'update_time'=>date('Y-m-d H:i:s'));

        $m_area = new \Common\Model\AreaModel();
        $res_area = $m_area->find($res_address['area_id']);
        $res_county = $m_area->find($res_address['county_id']);
        $detail_address = $res_area['region_name'].$res_county['region_name'].$res_address['address'];
        $res_location = getGDgeocodeByAddress($detail_address);
        if(empty($res_location)){
            $res_location = getGDgeocodeByAddress($detail_address);
        }
        if(!empty($res_location)){
            $data['lng'] = $res_location['lng'];
            $data['lat'] = $res_location['lat'];
        }

        $m_address->updateData(array('id'=>$address_id),$data);
        $this->to_back(array());
    }

    public function delAddress(){
        $address_id = intval($this->params['address_id']);
        $openid = $this->params['openid'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_address = new \Common\Model\Smallapp\AddressModel();
        $res_address = $m_address->getInfo(array('id'=>$address_id));
        if(empty($res_address) || $res_address['openid']!=$openid){
            $this->to_back(90132);
        }
        $m_address->updateData(array('id'=>$address_id),array('status'=>2));
        $this->to_back(array());
    }

    public function getDefaultAddress(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_address = new \Common\Model\Smallapp\AddressModel();
        $where = array('openid'=>$openid,'status'=>1,'is_default'=>1);
        $res_address = $m_address->getDataList('*',$where,'id desc');
        $data = array();
        if(!empty($res_address)){
            $res_address = $res_address[0];
            $data = array('address_id'=>$res_address['id'],'consignee'=>$res_address['consignee'],
                'phone'=>$res_address['phone']);
            $m_area = new \Common\Model\AreaModel();
            $res_area = $m_area->find($res_address['area_id']);
            $res_county = $m_area->find($res_address['county_id']);
            $detail_address = $res_area['region_name'].$res_county['region_name'].$res_address['address'];
            $data['address'] = $detail_address;
        }
        $this->to_back($data);
    }


}