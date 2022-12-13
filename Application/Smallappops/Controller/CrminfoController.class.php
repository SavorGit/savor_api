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
                );
                $this->is_verify = 1;
                break;
            case 'hotelinfo':
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
                $where = array('is_in_hotel'=>1,'is_valid'=>1);
                $citys = $m_area->field($area_fields)->where($where)->order('id asc')->select();
                break;
            case 2:
            case 4:
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
            'mobile'=>$mobile,'type'=>1,'status'=>1);
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
            $data['update_time'] = date('Y-m-d H:i:s');
            $m_crmuser->updateData(array('id'=>$id),$data);
            $user_id = $id;
            $res_info = $m_crmuser->getInfo(array('id'=>$id));
            if(!empty($res_info['openid'])){
                $up_user = array();
                if($res_info['name']!=$name){
                    $up_user['nickName'] = $name;
                    $up_user['name'] = $name;
                    $m_user->updateInfo(array('openid'=>$openid),$up_user);
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
        $where = array('hotel.state'=>1,'hotel.flag'=>0);
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

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $data = array('openid'=>$openid,'name'=>$name,'area_id'=>$area_id,'county_id'=>$county_id,
            'addr'=>$addr,'contractor'=>$contractor,'mobile'=>$mobile,'state'=>4,'type'=>2
        );
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
        $field = 'hotel.id,hotel.name,hotel.addr,hotel.contractor,hotel.mobile,ext.maintainer_id';
        $res_hotel = $m_hotel->getHotelById($field,array('hotel.id'=>$hotel_id));
        $maintainer = '';
        if($res_hotel['maintainer_id']){
            $m_sysuser = new \Common\Model\SysUserModel();
            $res_sysuser = $m_sysuser->getUserInfo(array('id'=>$res_hotel['maintainer_id']) ,'remark');
            $maintainer = $res_sysuser['remark'];
        }
        $res_hotel['maintainer'] = $maintainer;
        $this->to_back($res_hotel);
    }
}
