<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class CrminfoController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getCityMaintainerList':
                $this->valid_fields = array('openid'=>1001);
                $this->is_verify = 1;
                break;
            case 'contactlist':
                $this->valid_fields = array('openid'=>1001,'city_id'=>1002,'maintainer_id'=>1002,
                    'keywords'=>1002,'page'=>1001,'pagesize'=>1002);
                $this->is_verify = 1;
                break;
            case 'addcontact':
                $this->valid_fields = array('openid'=>1001,'name'=>1001,'avatar_url'=>1002,'gender'=>1001,'hotel_id'=>1001,
                    'job'=>1001,'department'=>1001,'province_id'=>1002,'city_id'=>1002,'area_id'=>1002,'birthday'=>1002,'mobile'=>1001,
                    'mobile2'=>1002,'tel'=>1002,'email'=>1002,'address'=>1002,'id'=>1002
                    );
                $this->is_verify = 1;
                break;
            case 'contactinfo':
                $this->valid_fields = array('openid'=>1001,'contact_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'addhotel':
                $this->valid_fields = array('openid'=>1001,'name'=>1001,'hotel_cover_img'=>1001,
                    'area_id'=>1001,'county_id'=>1001,'addr'=>1001,'contractor'=>1002,'mobile'=>1002,
                    'latitude'=>1002,'longitude'=>1002
                );
                $this->is_verify = 1;
                break;
            case 'hotelinfo':
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'stafflist':
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'page'=>1002,'pagesize'=>1002,'version'=>1002);
                $this->is_verify = 1;
                break;
            case 'staffchangelist':
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'hotellist':
                $this->valid_fields = array('openid'=>1001,'city_id'=>1002,'maintainer_id'=>1002,
                    'keywords'=>1002,'page'=>1001,'pagesize'=>1002);
                $this->is_verify = 1;
                break;
            case 'addcard':
                $this->valid_fields = array('openid'=>1001,'contact_id'=>1001,'image'=>1001);
                $this->is_verify = 1;
                break;
            case 'cardlist':
                $this->valid_fields = array('openid'=>1001,'contact_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'addtag':
                $this->valid_fields = array('openid'=>1001,'contact_id'=>1001,'names'=>1002,'del_ids'=>1002);
                $this->is_verify = 1;
                break;
            case 'taglist':
                $this->valid_fields = array('openid'=>1001,'contact_id'=>1001);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function getCityMaintainerList(){
        $openid = $this->params['openid'];
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_area = new \Common\Model\AreaModel();
        $area_fields = "id,region_name as name";
        $m_opuser_role = new \Common\Model\OpuserRoleModel();
        $permission = json_decode($res_staff['permission'],true);
        $maintainers = array();
        switch ($permission['hotel_info']['type']){
            case 1:
            case 5:
                $where = array('is_in_hotel'=>1,'is_valid'=>1);
                $citys = $m_area->field($area_fields)->where($where)->order('id asc')->select();
                break;
            case 2:
            case 4:
            case 6:
                $area_ids = $permission['hotel_info']['area_ids'];
                $where = array('is_in_hotel'=>1,'is_valid'=>1,'id'=>array('in',$area_ids));
                $citys = $m_area->field($area_fields)->where($where)->order('id asc')->select();
                break;
            case 3:
                $maintainer_id = $res_staff['sysuser_id'];
                $fields = 'a.user_id as maintainer_id,a.manage_city,user.remark as name';
                $maintainers = $m_opuser_role->getList($fields,array('a.user_id'=>$maintainer_id,'a.state'=>1,'a.role_id'=>1),'a.id desc','0,1');
                $area_ids = intval($maintainers[0]['manage_city']);
                $where = array('is_in_hotel'=>1,'is_valid'=>1,'id'=>$area_ids);
                $citys = $m_area->field($area_fields)->where($where)->order('id asc')->select();
                break;
            default:
                $citys = array();
        }
        if($permission['hotel_info']['type']==3){
            $citys[0]['maintainers'] = $maintainers;
            $data_list = $citys;
        }else{
            $data_list = array(array('id'=>0,'name'=>'全部城市','maintainers'=>array(array('maintainer_id'=>0,'name'=>'全部维护人'))));
            foreach ($citys as $v){
                $fields = 'a.user_id as maintainer_id,user.remark as name';
                $maintainers = $m_opuser_role->getList($fields,array('a.manage_city'=>$v['id'],'a.state'=>1,'a.role_id'=>1,'user.status'=>1),'a.id desc','');
                $f_maintainer = array(array('maintainer_id'=>0,'name'=>'全部维护人'));
                $v['maintainers'] = array_merge($f_maintainer,$maintainers);
                $data_list[]=$v;
            }
        }
        $this->to_back($data_list);
    }

    public function contactlist(){
        $openid = $this->params['openid'];
        $city_id = intval($this->params['city_id']);
        $maintainer_id = intval($this->params['maintainer_id']);
        $page = intval($this->params['page']);
        $pagesize = intval($this->params['pagesize']);
        $keywords = trim($this->params['keywords']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        if(empty($pagesize)){
            $pagesize = 10;
        }
        $start = ($page-1)*$pagesize;
        $limit = "$start,$pagesize";
        $fields = 'a.id as contact_id,a.name,hotel.name as hotel_name,a.mobile';
        $where = array('a.status'=>1);
        if($city_id){
            $where['hotel.area_id'] = $city_id;
        }else{
            $m_area = new \Common\Model\AreaModel();
            $area_fields = 'id';
            $permission = json_decode($res_staff['permission'],true);
            $citys = array();
            switch ($permission['hotel_info']['type']){
                case 1:
                    $areawhere = array('is_in_hotel'=>1,'is_valid'=>1);
                    $citys = $m_area->field($area_fields)->where($areawhere)->order('id asc')->select();
                    break;
                case 2:
                case 4:
                    $area_ids = $permission['hotel_info']['area_ids'];
                    $areawhere = array('is_in_hotel'=>1,'is_valid'=>1,'id'=>array('in',$area_ids));
                    $citys = $m_area->field($area_fields)->where($areawhere)->order('id asc')->select();
                    break;
            }
            if(!empty($citys)){
                $city_ids = array();
                foreach ($citys as $v){
                    $city_ids[]=$v['id'];
                }
                $where['hotel.area_id'] = array('in',$city_ids);
            }
        }
        if($maintainer_id){
            $where['ext.maintainer_id'] = $maintainer_id;
        }
        if(!empty($keywords)){
            $where['a.name'] = array('like',"%$keywords%");
        }
        $m_crmuser = new \Common\Model\Crm\ContactModel;
        $res_user = $m_crmuser->getUserList($fields,$where,'a.id desc',$limit);

        $res_data = array('datalist'=>$res_user);
        $this->to_back($res_data);
    }

    public function contactinfo(){
        $openid = $this->params['openid'];
        $contact_id = intval($this->params['contact_id']);
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $m_crmuser = new \Common\Model\Crm\ContactModel;
        $res_info = $m_crmuser->getInfo(array('id'=>$contact_id));
        if(!empty($res_info)){
            $oss_host = get_oss_host();
            $img_avatar_url = '';
            if(!empty($res_info['avatar_url'])){
                if(substr($res_info['avatar_url'],0,4)!='http'){
                    $img_avatar_url = $oss_host.$res_info['avatar_url'];
                }else{
                    $img_avatar_url = $res_info['avatar_url'];
                }
            }
            $res_info['img_avatar_url'] = $img_avatar_url;
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getHotelById('hotel.name,ext.maintainer_id',array('hotel.id'=>$res_info['hotel_id']));
            $res_info['hotel_name'] = $res_hotel['name'];
            $maintainer = '';
            if($res_hotel['maintainer_id']){
                $m_sysuser = new \Common\Model\SysUserModel();
                $res_sysuser = $m_sysuser->getUserInfo(array('id'=>$res_hotel['maintainer_id']) ,'remark');
                $maintainer = $res_sysuser['remark'];
            }
            $res_info['maintainer'] = $maintainer;
            $type_str = '普通用户';
            if($res_info['type']==1){
                $type_str = '销售端用户';
            }
            $res_info['type_str'] = $type_str;
            $gender_map = array('1'=>'男','2'=>'女','0'=>'');
            $res_info['gender_str'] = $gender_map[$res_info['gender']];
            $native_place = '';
            $m_area = new \Common\Model\AreaModel();
            $fields = "id,region_name as name";
            $res_area = $m_area->getWhere($fields,array('id'=>$res_info['province_id']),'','');
            if(!empty($res_area) && !in_array($res_info['province_id'],array(1,2,9,22)))   $native_place.=$res_area['name'];
            $res_area = $m_area->getWhere($fields,array('id'=>$res_info['city_id']),'','');
            if(!empty($res_area))   $native_place.=$res_area['name'];
            $res_area = $m_area->getWhere($fields,array('id'=>$res_info['area_id']),'','');
            if(!empty($res_area))   $native_place.=$res_area['name'];
            $res_info['native_place'] = $native_place;
            if($res_info['birthday']=='0000-00-00'){
                $res_info['birthday'] = '';
            }

        }
        $this->to_back($res_info);
    }

    public function addcontact(){
        $id = intval($this->params['id']);
        $openid = $this->params['openid'];
        $name = $this->params['name'];
        $avatar_url = $this->params['avatar_url'];
        $gender = intval($this->params['gender']);
        $hotel_id = intval($this->params['hotel_id']);
        $job = $this->params['job'];
        $department = $this->params['department'];
        $province_id = intval($this->params['province_id']);
        $city_id = intval($this->params['city_id']);
        $area_id = intval($this->params['area_id']);
        $birthday = $this->params['birthday'];
        $mobile = $this->params['mobile'];
        $mobile2 = $this->params['mobile2'];
        $tel = $this->params['tel'];
        $email = $this->params['email'];
        $address = $this->params['address'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $data = array('op_openid'=>$openid,'name'=>$name,'gender'=>$gender,
            'hotel_id'=>$hotel_id,'job'=>$job,'department'=>$department,'province_id'=>$province_id,'city_id'=>$city_id,'area_id'=>$area_id,
            'mobile'=>$mobile,'type'=>2,'status'=>1);
        if(!empty($avatar_url)) $data['avatar_url'] = $avatar_url;
        if(!empty($birthday))   $data['birthday'] = $birthday;
        if(!empty($mobile2))    $data['mobile2'] = $mobile2;
        if(!empty($tel))        $data['tel'] = $tel;
        if(!empty($email))      $data['email'] = $email;
        if(!empty($address))    $data['address'] = $address;
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res_user = $m_user->getOne('*',array('mobile'=>$mobile,'small_app_id'=>5),'id desc');
        if(!empty($res_user)){
            $data['type']=1;
        }elseif(!empty($mobile2)){
            $res_user = $m_user->getOne('*',array('mobile'=>$mobile2,'small_app_id'=>5),'id desc');
            if(!empty($res_user)){
                $data['type']=1;
            }
        }
        $m_crmuser = new \Common\Model\Crm\ContactModel;
        if($id){
            $res_info = $m_crmuser->getInfo(array('id'=>$id));

            $data['update_time'] = date('Y-m-d H:i:s');
            $m_crmuser->updateData(array('id'=>$id),$data);
            $user_id = $id;
            if(!empty($res_info['openid'])){
                $up_user = array();
                if($res_info['name']!=$name){
                    $up_user['nickName'] = $name;
                    $up_user['name'] = $name;
                    $m_user->updateInfo(array('openid'=>$res_info['openid']),$up_user);
                }
            }
        }else{
            $user_id = $m_crmuser->add($data);
        }
        $this->to_back(array('crmuser_id'=>$user_id));
    }

    public function addcard(){
        $openid = $this->params['openid'];
        $contact_id = intval($this->params['contact_id']);
        $image = $this->params['image'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_card = new \Common\Model\Crm\ContactCardModel();
        $card_id = $m_card->add(array('contact_id'=>$contact_id,'image'=>$image));
        $this->to_back(array('card_id'=>$card_id));
    }

    public function cardlist(){
        $openid = $this->params['openid'];
        $contact_id = intval($this->params['contact_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_card = new \Common\Model\Crm\ContactCardModel();
        $res_data = $m_card->getDataList('*',array('contact_id'=>$contact_id),'id desc');
        $datalist = array();
        if(!empty($res_data)){
            $oss_host = get_oss_host();
            foreach ($res_data as $v){
                $v['image'] = $oss_host.$v['image'];
                $datalist[]=$v;
            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function addtag(){
        $openid = $this->params['openid'];
        $contact_id = intval($this->params['contact_id']);
        $del_ids = $this->params['del_ids'];
        $names = $this->params['names'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_tag = new \Common\Model\Crm\ContactTagModel();
        if(!empty($del_ids)){
            $arr_ids = explode(',',$del_ids);
            $dids = array();
            foreach ($arr_ids as $v){
                $del_id = intval($v);
                if($del_id>0){
                    $dids[]=$del_id;
                }
            }
            if(!empty($dids)){
                $m_tag->delData(array('id'=>array('in',$dids)));
            }
        }
        if(!empty($names)){
            $json_names = stripslashes(html_entity_decode($names));
            $arr_names = json_decode($json_names,true);
            if(is_array($arr_names)){
                $name_data = array();
                foreach ($arr_names as $v){
                    $n = trim($v);
                    if(!empty($n)){
                        $data = array('contact_id'=>$contact_id,'name'=>$n);
                        $res_data = $m_tag->getInfo($data);
                        if(empty($res_data)){
                            $name_data[]=$data;
                        }
                    }
                }
                if(!empty($name_data)){
                    $m_tag->addAll($name_data);
                }
            }
        }
        $this->to_back(array());
    }

    public function taglist(){
        $openid = $this->params['openid'];
        $contact_id = intval($this->params['contact_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_tag = new \Common\Model\Crm\ContactTagModel();
        $res_data = $m_tag->getDataList('*',array('contact_id'=>$contact_id),'id desc');
        $datalist = array();
        if(!empty($res_data)){
            foreach ($res_data as $v){
                $datalist[]=array('id'=>$v['id'],'name'=>$v['name']);
            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function hotellist(){
        $openid = $this->params['openid'];
        $city_id = intval($this->params['city_id']);
        $maintainer_id = intval($this->params['maintainer_id']);
        $page = intval($this->params['page']);
        $pagesize = intval($this->params['pagesize']);
        $keywords = trim($this->params['keywords']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        if(empty($pagesize)){
            $pagesize = 10;
        }
        $start = ($page-1)*$pagesize;
        $limit = "$start,$pagesize";
        $fields = 'hotel.id as hotel_id,hotel.name as hotel_name,hotel.addr';
        $where = array('hotel.state'=>array('in','1,4'),'hotel.flag'=>0);
        if($city_id){
            $where['hotel.area_id'] = $city_id;
        }else{
            $m_area = new \Common\Model\AreaModel();
            $area_fields = 'id';
            $permission = json_decode($res_staff['permission'],true);
            $citys = array();
            switch ($permission['hotel_info']['type']){
                case 1:
                    $areawhere = array('is_in_hotel'=>1,'is_valid'=>1);
                    $citys = $m_area->field($area_fields)->where($areawhere)->order('id asc')->select();
                    break;
                case 2:
                case 4:
                    $area_ids = $permission['hotel_info']['area_ids'];
                    $areawhere = array('is_in_hotel'=>1,'is_valid'=>1,'id'=>array('in',$area_ids));
                    $citys = $m_area->field($area_fields)->where($areawhere)->order('id asc')->select();
                    break;
            }
            if(!empty($citys)){
                $city_ids = array();
                foreach ($citys as $v){
                    $city_ids[]=$v['id'];
                }
                $where['hotel.area_id'] = array('in',$city_ids);
            }
        }
        if($maintainer_id){
            $where['ext.maintainer_id'] = $maintainer_id;
        }
        if(!empty($keywords)){
            $where['hotel.name'] = array('like',"%$keywords%");
        }
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotels = $m_hotel->getHotelDataList($fields,$where,'hotel.pinyin asc',$limit);
        $res_data = array('datalist'=>$res_hotels);
        $this->to_back($res_data);
    }

    public function addhotel(){
        $openid = $this->params['openid'];
        $name = trim($this->params['name']);
        $hotel_cover_img = $this->params['hotel_cover_img'];
        $area_id = intval($this->params['area_id']);
        $county_id = intval($this->params['county_id']);
        $addr = trim($this->params['addr']);
        $contractor = trim($this->params['contractor']);
        $mobile = $this->params['mobile'];
        $latitude = $this->params['latitude'];
        $longitude = $this->params['longitude'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $data = array('openid'=>$openid,'name'=>$name,'area_id'=>$area_id,'county_id'=>$county_id,
            'addr'=>$addr,'contractor'=>$contractor,'mobile'=>$mobile,'state'=>4,'type'=>2,'htype'=>20,'no_work_type'=>21
        );
        if($longitude>0 && $latitude>0 ) {
            $bd_lnglat = getgeoByTc($latitude, $longitude,3);
            if(!empty($bd_lnglat[0]['x']) && !empty($bd_lnglat[0]['y'])){
                $data['gps'] = "{$bd_lnglat[0]['x']},{$bd_lnglat[0]['y']}";
            }
        }

        $m_hotel = new \Common\Model\HotelModel();
        $hwhere = array('name'=>$name,'state'=>array('in',array(1,4)),'flag'=>0);
        $res_hotel = $m_hotel->field('id,name')->where($hwhere)->find();
        if(!empty($res_hotel)){
            $this->to_back(94004);
        }
        $hotel_id = $m_hotel->add($data);
        if($hotel_id){
            $m_media = new \Common\Model\MediaModel();
            $temp_info = pathinfo($hotel_cover_img);
            $surfix = strtolower($temp_info['extension']);
            $media_data = array('oss_addr'=>$hotel_cover_img,'surfix'=>$surfix,'type'=>2,'state'=>1);
            $hotel_cover_media_id = $m_media->add($media_data);
            $m_hotelext = new \Common\Model\HotelExtModel();
            $m_hotelext->add(array('hotel_id'=>$hotel_id,'hotel_cover_media_id'=>$hotel_cover_media_id));
        }
        $this->to_back(array('hotel_id'=>$hotel_id));
    }

    public function hotelinfo(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_hotel = new \Common\Model\HotelModel();
        $field = 'hotel.id,hotel.name,hotel.addr,hotel.contractor,hotel.mobile,hotel.type,ext.cooperate_status,ext.maintainer_id,
        ext.is_salehotel,ext.trade_area_type,ext.food_style_id,ext.avg_expense';
        $res_hotel = $m_hotel->getHotelById($field,array('hotel.id'=>$hotel_id));
        $maintainer = '';
        if($res_hotel['maintainer_id']){
            $m_sysuser = new \Common\Model\SysUserModel();
            $res_sysuser = $m_sysuser->getUserInfo(array('id'=>$res_hotel['maintainer_id']) ,'remark');
            $maintainer = $res_sysuser['remark'];
        }
        $res_hotel['maintainer'] = $maintainer;
        $m_foodstyle = new \Common\Model\FoodStyleModel();
        $res_food = $m_foodstyle->getOne('name',array('id'=>$res_hotel['food_style_id']),'');
        $food_style = '';
        if(!empty($res_food)){
            $food_style = $res_food['name'];
        }
        $res_hotel['food_style'] = $food_style;
        $hotel_type_maps = array('1'=>'正常酒楼','2'=>'正常酒楼','3'=>'自主注册酒楼','4'=>'无设备酒楼','5'=>'测试酒楼');
        $cooperate_status_maps = array('1'=>'合作中','2'=>'已中止合作','0'=>'未开始合作');
        $res_hotel['type_str'] = $hotel_type_maps[$res_hotel['type']];
        $res_hotel['cooperate_status_str'] = $cooperate_status_maps[$res_hotel['cooperate_status']];

        $where = array('m.hotel_id'=>$hotel_id,'m.status'=>1);
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getMerchantInfo('m.*',$where);
        $merchant_type_str = '';
        $merchant_is_integral = 0;
        $merchant_is_shareprofit = 0;
        $merchant_name = $merchant_job = $merchant_mobile = '';
        if(!empty($res_merchant)){
            $merchant_type_str = '正常';
            $merchant_is_integral = $res_merchant[0]['is_integral'];
            $merchant_is_shareprofit = $res_merchant[0]['is_shareprofit'];
            $merchant_name = $res_merchant[0]['name'];
            $merchant_job = $res_merchant[0]['job'];
            $merchant_mobile = $res_merchant[0]['mobile'];
        }
        $res_hotel['merchant_id'] = intval($res_merchant[0]['id']);
        $res_hotel['merchant'] = array('type_str'=>$merchant_type_str,'is_integral'=>$merchant_is_integral,
            'is_shareprofit'=>$merchant_is_shareprofit,'name'=>$merchant_name,'job'=>$merchant_job,'mobile'=>$merchant_mobile);

        $m_hotelcontract = new \Common\Model\Finance\ContractHotelModel();
        $contract_fields = 'contract.id,contract.oss_addr,contract.sign_user_id,contract.sign_time,contract.archive_time,contract.contract_stime,
        contract.contract_etime,contract.status,contract.hotel_signer,contract.hotel_signer_phone1';
        $res_contract = $m_hotelcontract->getContractData($contract_fields,array('a.hotel_id'=>$hotel_id,'contract.type'=>20),'a.id desc');
        $proxysale_contract_id = 0;
        $contract = array();
        if(!empty($res_contract)){
            $contract = $res_contract[0];
            $proxysale_contract_id = intval($contract['id']);
            if($contract['contract_stime']=='0000-00-00'){
                $contract['contract_stime'] = '';
            }
            if($contract['contract_etime']=='0000-00-00'){
                $contract['contract_etime'] = '';
            }
            $sign_user = '';
            if($contract['sign_user_id']){
                $m_sign_user = new \Common\Model\Finance\SignuserModel();
                $res_signuser = $m_sign_user->getInfo(array('id'=>$contract['sign_user_id']));
                $sign_user = $res_signuser['uname'];
            }
            $contract['sign_user'] = $sign_user;
            $now_date = date('Y-m-d');
            $status_str = '';
            if($contract['status']==4){
                $status_str =  "已终止";
            }else{
                if($contract['contract_stime']>$now_date){
                    $status_str =  '待生效';
                }elseif($now_date>=$contract['contract_stime'] && $now_date<=$contract['contract_etime']){
                    $status_str =  '进行中';
                }elseif($contract['contract_etime']<$now_date){
                    $status_str =  '已到期';
                }
            }
            $contract['status_str'] = $status_str;
            $contract['type_str'] = '商品代销合同';
            $oss_addr = '';
            if(!empty($res_contract[0]['oss_addr'])){
                $oss_host = get_oss_host();
                $oss_addr = $oss_host.$res_contract[0]['oss_addr'];
            }
            $contract['oss_addr'] = $oss_addr;
        }
        $res_hotel['proxysale_contract_id'] = $proxysale_contract_id;
        $res_hotel['contract'] = $contract;
        $this->to_back($res_hotel);
    }

    public function stafflist(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $page = $this->params['page'];
        $pagesize = $this->params['pagesize'];
        $version = $this->params['version'];

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $where = array('merchant.hotel_id'=>$hotel_id,'merchant.status'=>1,'a.status'=>1);
        if(!empty($version) && $version>='1.0.15'){
            unset($where['a.status']);
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        $fields = 'a.openid,a.level,a.id as staff_id,a.status,user.avatarUrl,user.nickName';
        if(!empty($page) && !empty($pagesize)){
            $start = ($page-1)*$pagesize;
            $limit = "$start,$pagesize";
        }elseif(!empty($pagesize)){
            $limit = "0,$pagesize";
        }else{
            $limit = '';
        }
        $staff_list = $m_staff->getMerchantStaff($fields,$where,'a.level asc',$limit);
        if(!empty($staff_list)){
            $oss_host = C('OSS_HOST');
            $staff_level = C('STAFF_LEVEL');
            $m_contact = new \Common\Model\Crm\ContactModel();
            foreach ($staff_list as $k=>$v){
                if(strpos($v['avatarUrl'],$oss_host)){
                    $staff_list[$k]['avatarUrl'] = $v['avatarUrl']."?x-oss-process=image/resize,m_mfit,h_300,w_300";
                }
                $job = '';
                if(isset($staff_level[$v['level']])){
                    $job = $staff_level[$v['level']];
                }
                $res_contact = $m_contact->getInfo(array('openid'=>$v['openid']));
                $contact_id = intval($res_contact['id']);

                $staff_list[$k]['contact_id'] = $contact_id;
                $staff_list[$k]['job'] = $job;
            }
        }
        if(!empty($version) && $version>='1.0.15'){
            $is_edit_staff = 0;
            if(!empty($staff_list) && $staff_list[0]['level']==1){
                $is_edit_staff = $m_opsstaff->check_edit_salestaff($res_staff,$hotel_id);
            }
            $res_data = array('datalist'=>$staff_list,'is_edit_staff'=>$is_edit_staff);
        }else{
            $res_data = $staff_list;
        }
        $this->to_back($res_data);
    }

    public function staffchangelist(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $where = array('merchant.hotel_id'=>$hotel_id,'merchant.status'=>1,'a.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $fields = 'a.id,a.parent_id,a.openid,a.level,a.add_time,user.avatarUrl,user.nickName,merchant.sysuser_id';
        $staff_list = $m_staff->getMerchantStaff($fields,$where,'a.id desc','');
        $res_data = array();
        if(!empty($staff_list)){
            $oss_host = C('OSS_HOST');
            $staff_level = C('STAFF_LEVEL');
            $all_staff = array();
            foreach ($staff_list as $k=>$v){
                if(strpos($v['avatarUrl'],$oss_host)){
                    $v['avatarUrl'] = $v['avatarUrl']."?x-oss-process=image/resize,m_mfit,h_300,w_300";
                }
                $job = '';
                if(isset($staff_level[$v['level']])){
                    $job = $staff_level[$v['level']];
                }
                $all_staff[$v['id']] = array('openid'=>$v['openid'],'avatarUrl'=>$v['avatarUrl'],'nickName'=>$v['nickName'],'job'=>$job);
            }
            $m_contact = new \Common\Model\Crm\ContactModel();
            $m_sysuser = new \Common\Model\SysUserModel();
            foreach ($staff_list as $k=>$v){
                if($v['level']==1){
                    $res_sysuser = $m_sysuser->getUserInfo(array('id'=>$staff_list[0]['sysuser_id']));
                    $invate_username = $res_sysuser['remark'];
                    $invate_userimg = '';
                    $job_info = '指定为当前餐厅店长';
                }else{
                    $invate_username = $all_staff[$v['parent_id']]['nickName'];
                    $invate_userimg = $all_staff[$v['parent_id']]['avatarUrl'];
                    $job_info = '邀请成为'.$all_staff[$v['id']]['job'];
                }
                $res_contact = $m_contact->getInfo(array('openid'=>$v['openid']));
                $contact_id = intval($res_contact['id']);
                $info = array('id'=>$v['id'],'openid'=>$v['openid'],'contact_id'=>$contact_id,'nickName'=>$v['nickName'],'avatarUrl'=>$all_staff[$v['id']]['avatarUrl'],
                    'invate_username'=>$invate_username,'invate_userimg'=>$invate_userimg,'job_info'=>$job_info,'add_time'=>$v['add_time']);
                $res_data[]=$info;
            }
        }
        $this->to_back($res_data);
    }






}
