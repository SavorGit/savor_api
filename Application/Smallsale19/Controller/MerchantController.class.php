<?php
namespace Smallsale19\Controller;
use \Common\Controller\CommonController as CommonController;

class MerchantController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'info':
                $this->is_verify = 1;
                $this->valid_fields = array('merchant_id'=>1001);
                break;
            case 'getPayee':
                $this->is_verify = 1;
                $this->valid_fields = array('merchant_id'=>1001);
                break;
            case 'register':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'name'=>1001,'food_style_id'=>1001,'avg_exp'=>1001,
                    'tel'=>1001,'area_id'=>1001,'county_id'=>1002,'addr'=>1001,'logoimg'=>1002,
                    'faceimg'=>1001,'envimg'=>1002,'legal_name'=>1001,'legal_idcard'=>1001,
                    'legal_charter'=>1001,'contractor'=>1001,'mobile'=>1001,'verify_code'=>1001,
                    'business_lunchshours'=>1002,'business_lunchehours'=>1002,
                    'business_dinnershours'=>1002,'business_dinnerehours'=>1002,
                    'meal_time'=>1002,
                );
                break;
            case 'purchaseMerchantList':
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001,'area_id'=>1001,
                    'county_id'=>1002,'food_style_id'=>1002,'avg_exp_id'=>1002);
                break;
            case 'setChangeprice':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'is_changeprice'=>1001);
                break;
            case 'setPayee':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'payee_openid'=>1001);
                break;
            case 'setHotelinfo':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'img'=>1001,'tel'=>1001,
                    'business_lunchshours'=>1001,'business_lunchehours'=>1001,
                    'business_dinnershours'=>1001,'business_dinnerehours'=>1001,
                    'meal_time'=>1001,'notice'=>1002,'legal_charter'=>1002,
                );
                break;
            case 'setDeliveryPlatform':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'delivery_platform'=>1001);
                break;
            case 'setShopself':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'is_shopself'=>1001);
                break;
        }
        parent::_init_();
    }

    public function purchaseMerchantList(){
        $page = $this->params['page'] ? $this->params['page'] :1;
        $area_id  = $this->params['area_id'] ? $this->params['area_id'] :1;
        $county_id = $this->params['county_id'];
        $food_style_id = $this->params['food_style_id'];
        $avg_id = $this->params['avg_exp_id'];
        $pagesize = 10;
        $size = $page * $pagesize;

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $fields = "hotel.id hotel_id,hotel.name,hotel.addr,hotel.tel,ext.food_style_id,
                   ext.avg_expense,ext.hotel_cover_media_id,food.name as food_name,m.id as merchant_id,m.is_changeprice,m.is_sale";
        $where = array('m.status'=>1,'m.is_takeout'=>1,'m.is_sale'=>1,'m.is_purchase'=>1);
//        $where['m.id'] = array('not in','89');
        if($area_id){
            $where['hotel.area_id'] = $area_id;
        }
        if($county_id){
            $where['hotel.county_id'] = $county_id;
        }
        if($food_style_id){
            $where['ext.food_style_id'] = $food_style_id;
        }
        if($avg_id){
            $where['ext.avg_expense'] = $this->getAvgWhere($avg_id);
        }
        $res_merchant = $m_merchant->getMerchantList($fields,$where,'hotel.pinyin asc',0,$size);
        $datalist = array();
        if($res_merchant['total']){
            $oss_host = "https://".C('OSS_HOST').'/';
            $datalist = $res_merchant['list'];
            $m_dishgoods = new \Common\Model\Smallapp\DishgoodsModel();
            $m_media = new \Common\Model\MediaModel();
            foreach ($datalist as $k=>$v){
                if(empty($v['food_name'])){
                    $datalist[$k]['food_name'] = '';
                }
                $img_url = '';
                if(!empty($v['hotel_cover_media_id'])){
                    $res_media = $m_media->getMediaInfoById($v['hotel_cover_media_id']);
                    $img_url = $res_media['oss_addr'].'?x-oss-process=image/resize,p_20';
                }
                $merchant_id = 0;
                if(!empty($v['merchant_id'])){
                    $merchant_id = intval($v['merchant_id']);
                }

                $goods = array();
                $dgfields = 'id,name,price,cover_imgs';
                $dgwhere = array('merchant_id'=>$merchant_id,'status'=>1,'is_sale'=>1);
                $dgorderby = 'is_top desc,id desc';
                $res_goods = $m_dishgoods->getDataList($dgfields,$dgwhere,$dgorderby,0,4);
                if($res_goods['total']){
                    foreach ($res_goods['list'] as $gv){
                        $ginfo = array('id'=>$gv['id'],'name'=>$gv['name'],'price'=>$gv['price']);
                        $cover_imgs_info = explode(',',$gv['cover_imgs']);
                        $ginfo['img'] = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                        $goods[]=$ginfo;
                    }
                }
                $datalist[$k]['goods'] = $goods;
                $datalist[$k]['img_url'] = $img_url;
            }
        }
        $this->to_back($datalist);
    }

    public function info(){
        $merchant_id = intval($this->params['merchant_id']);
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('id'=>$merchant_id));
        if(empty($res_merchant) || $res_merchant['status']!=1){
            $this->to_back(93035);
        }

        $m_hotel = new \Common\Model\HotelModel();
        $field = 'hotel.name,hotel.mobile,hotel.tel,hotel.addr,hotel.media_id,
        area.region_name as area_name,ext.business_hours,ext.meal_time,
        ext.hotel_cover_media_id,ext.avg_expense,ext.food_style_id,ext.legal_charter';
        $where = array('hotel.id'=>$res_merchant['hotel_id']);
        $res_hotel = $m_hotel->getHotelById($field,$where);

        $merchant = array('name'=>$res_hotel['name'],'mobile'=>$res_hotel['mobile'],'tel'=>$res_hotel['tel'],
            'addr'=>$res_hotel['addr'],'area_name'=>$res_hotel['area_name'],'avg_expense'=>$res_hotel['avg_expense'],
            'is_changeprice'=>$res_merchant['is_changeprice'],'is_sale'=>$res_merchant['is_sale'],'tips'=>'不出门抗击疫情，线上享超值菜品',
            'is_shopself'=>$res_merchant['is_shopself'],'delivery_platform'=>$res_merchant['delivery_platform'],
            'img'=>'','img_path'=>'','meal_time'=>intval($res_hotel['meal_time']),'notice'=>$res_merchant['notice'],
            'business_lunchshours'=>'','business_lunchehours'=>'','business_dinnershours'=>'','business_dinnerehours'=>''
            );
        $m_media = new \Common\Model\MediaModel();
        if(!empty($res_hotel['hotel_cover_media_id'])){
            $res_media = $m_media->getMediaInfoById($res_hotel['hotel_cover_media_id'],'https');
            $merchant['img'] = $res_media['oss_addr'].'?x-oss-process=image/resize,p_50/quality,q_80';
            $merchant['img_path'] = $res_media['oss_path'];
        }

        $m_foodstyle = new \Common\Model\FoodStyleModel();
        $res_foodstyle = $m_foodstyle->getOne('name',array('id'=>$res_hotel['food_style_id']),'');
        $merchant['food_style'] = $res_foodstyle['name'];
        $host_name = 'https://'.$_SERVER['HTTP_HOST'];
        $merchant['qrfoods_url'] = $host_name."/smallsale19/qrcode/dishQrcode?data_id=$merchant_id&type=27&suid=foods";
        $merchant['qrgoods_url'] = $host_name."/smallsale19/qrcode/dishQrcode?data_id=$merchant_id&type=27&suid=goods";

        if(!empty($res_hotel['business_hours'])){
            $business_hours_arr = explode(',',$res_hotel['business_hours']);
            $business_lunchhours_arr = explode('-',$business_hours_arr[0]);
            $business_dinnerhours_arr = explode('-',$business_hours_arr[1]);
            $merchant['business_lunchshours'] = $business_lunchhours_arr[0];
            $merchant['business_lunchehours'] = $business_lunchhours_arr[1];
            $merchant['business_dinnershours'] = $business_dinnerhours_arr[0];
            $merchant['business_dinnerehours'] = $business_dinnerhours_arr[1];
        }
        $oss_host = "https://".C('OSS_HOST').'/';
        $charter = array();
        $charter_path = array();
        if(!empty($res_hotel['legal_charter'])){
            $legal_charter_imgs = explode(',',$res_hotel['legal_charter']);
            foreach ($legal_charter_imgs as $v){
                if(!empty($v)){
                    $img_url = $oss_host.$v;
                    $charter[] = $img_url;
                    $charter_path[] = $v;
                }
            }
            $merchant['charter'] = $charter;
            $merchant['charter_path'] = $charter_path;
        }
        $merchant['charter'] = $charter;
        $merchant['charter_path'] = $charter_path;
        $this->to_back($merchant);
    }

    public function getPayee(){
        $merchant_id = intval($this->params['merchant_id']);
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('id'=>$merchant_id));
        if(empty($res_merchant) || $res_merchant['status']!=1){
            $this->to_back(93035);
        }
        $m_payfee = new \Common\Model\Smallapp\MerchantPayeeModel();
        $where = array('merchant_id'=>$merchant_id,'status'=>array('in',array(1,2,3)));
        $res_payfee = $m_payfee->getDataList('*',$where,'id desc',0,1);
        $data = array();
        if($res_payfee['total']){
            $openid = $res_payfee['list'][0]['openid'];
            $m_user = new \Common\Model\Smallapp\UserModel();
            $where = array('openid'=>$openid);
            $fields = 'id user_id,openid,mobile,avatarUrl,nickName';
            $data = $m_user->getOne($fields, $where);
            $data['status'] = $res_payfee['list'][0]['status'];
        }
        $this->to_back($data);
    }


    public function register(){
        $name = $this->params['name'];
        $food_style_id = intval($this->params['food_style_id']);
        $avg_exp = intval($this->params['avg_exp']);
        $tel = $this->params['tel'];
        $area_id = intval($this->params['area_id']);
        $county_id = intval($this->params['county_id']);
        $addr = $this->params['addr'];
        $openid = $this->params['openid'];
        $logoimg = $this->params['logoimg'];
        $faceimg = $this->params['faceimg'];
        $envimg = $this->params['envimg'];
        $legal_name = $this->params['legal_name'];
        $legal_idcard = $this->params['legal_idcard'];
        $legal_charter = $this->params['legal_charter'];
        $contractor = $this->params['contractor'];
        $mobile = $this->params['mobile'];
        $verify_code = $this->params['verify_code'];
        $business_lunchshours = $this->params['business_lunchshours'];
        $business_lunchehours = $this->params['business_lunchehours'];
        $business_dinnershours = $this->params['business_dinnershours'];
        $business_dinnerehours = $this->params['business_dinnerehours'];
        $meal_time = intval($this->params['meal_time']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 5;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }

        $is_check = check_mobile($mobile);
        if(!$is_check){
            $this->to_back(93006);
        }
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $sale_key = C('SAPP_SALE');
        $register_key = $sale_key.'register:'.$mobile;
        $register_code = $redis->get($register_key);
        if($register_code!=$verify_code){
            $this->to_back(93040);
        }

        $add_hoteldata = array('name'=>$name,'area_id'=>$area_id,'county_id'=>$county_id,
            'addr'=>$addr,'contractor'=>$contractor,'mobile'=>$mobile,'tel'=>$tel,'state'=>2,'flag'=>2,'type'=>2,'openid'=>$openid);
        $typeinfo = C('RESOURCE_TYPEINFO');
        if(!empty($logoimg)){
            $temp_info = pathinfo($logoimg);
            $surfix = $temp_info['extension'];
            if($surfix){
                $surfix = strtolower($surfix);
            }
            if(isset($typeinfo[$surfix])){
                $media_type = $typeinfo[$surfix];
            }else{
                $media_type = 3;
            }
            $m_media = new \Common\Model\MediaModel();
            $media_data = array('oss_addr'=>$logoimg,'type'=>$media_type,'state'=>1);
            $media_id = $m_media->add($media_data);
            $add_hoteldata['media_id'] = $media_id;
        }

        $m_hotel = new \Common\Model\HotelModel();
        $hotel_id = $m_hotel->add($add_hoteldata);
        if($hotel_id){
            $temp_info = pathinfo($faceimg);
            $surfix = $temp_info['extension'];
            if($surfix){
                $surfix = strtolower($surfix);
            }
            if(isset($typeinfo[$surfix])){
                $media_type = $typeinfo[$surfix];
            }else{
                $media_type = 3;
            }
            $m_media = new \Common\Model\MediaModel();
            $media_data = array('oss_addr'=>$faceimg,'type'=>$media_type,'state'=>1);
            $media_id = $m_media->add($media_data);

            $add_hotelext = array('hotel_id'=>$hotel_id,'food_style_id'=>$food_style_id,'avg_expense'=>$avg_exp,
                'hotel_cover_media_id'=>$media_id,'hotel_envimg'=>$envimg,
                'legal_name'=>$legal_name,'legal_idcard'=>$legal_idcard,'legal_charter'=>$legal_charter
            );
            if(!empty($business_lunchshours) && !empty($business_lunchehours) && !empty($business_dinnershours) && !empty($business_dinnerehours)){
                $business_lunchhours = $business_dinnershours.'-'.$business_lunchehours;
                $business_dinnerhours = $business_dinnershours.'-'.$business_dinnerehours;
                $business_hours = $business_lunchhours.','.$business_dinnerhours;
                $add_hotelext['business_hours'] = $business_hours;
            }
            if(!empty($meal_time)){
                $add_hotelext['meal_time'] = $meal_time;
            }

            $m_hotelext = new \Common\Model\HotelExtModel();
            $m_hotelext->add($add_hotelext);
        }
        $message = '您的申请已经成功提交，稍后会有工作人员与您核实信息。请保持通话畅通。';
        $res_data = array('message'=>$message);
        $this->to_back($res_data);
    }

    public function setChangeprice(){
        $openid = $this->params['openid'];
        $is_changeprice = intval($this->params['is_changeprice']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,merchant.id as merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $data = array('is_changeprice'=>$is_changeprice);
        $m_merchant->updateData(array('id'=>$merchant_id),$data);
        $this->to_back(array());
    }

    public function setDeliveryPlatform(){
        $openid = $this->params['openid'];
        $delivery_platform = intval($this->params['delivery_platform']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,merchant.id as merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $data = array('delivery_platform'=>$delivery_platform);
        $m_merchant->updateData(array('id'=>$merchant_id),$data);
        $this->to_back(array());
    }

    public function setShopself(){
        $openid = $this->params['openid'];
        $is_shopself = intval($this->params['is_shopself']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,merchant.id as merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $data = array('is_shopself'=>$is_shopself);
        $m_merchant->updateData(array('id'=>$merchant_id),$data);
        $this->to_back(array());
    }

    public function setHotelinfo(){
        $openid = $this->params['openid'];
        $img = $this->params['img'];
        $tel = $this->params['tel'];
        $business_lunchshours = $this->params['business_lunchshours'];
        $business_lunchehours = $this->params['business_lunchehours'];
        $business_dinnershours = $this->params['business_dinnershours'];
        $business_dinnerehours = $this->params['business_dinnerehours'];
        $meal_time = $this->params['meal_time'];
        $notice = $this->params['notice'];
        $legal_charter = $this->params['legal_charter'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,merchant.id as merchant_id,merchant.hotel_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];
        $hotel_id = $res_staff[0]['hotel_id'];

        $m_hotel = new \Common\Model\HotelModel();
        $field = 'hotel.name,hotel.tel,hotel.media_id,ext.hotel_cover_media_id';
        $where = array('hotel.id'=>$hotel_id);
        $res_hotel = $m_hotel->getHotelById($field,$where);
        $hotel_data = array();

        $m_media = new \Common\Model\MediaModel();
        $hotel_ext = array('meal_time'=>$meal_time,'legal_charter'=>$legal_charter);
        if(!empty($res_hotel['hotel_cover_media_id'])){
            $res_media = $m_media->getMediaInfoById($res_hotel['hotel_cover_media_id']);
            if($img!=$res_media['oss_path']){
                $typeinfo = C('RESOURCE_TYPEINFO');
                $temp_info = pathinfo($img);
                $surfix = $temp_info['extension'];
                if($surfix){
                    $surfix = strtolower($surfix);
                }
                if(isset($typeinfo[$surfix])){
                    $media_type = $typeinfo[$surfix];
                }else{
                    $media_type = 3;
                }
                $media_data = array('oss_addr'=>$img,'type'=>$media_type,'state'=>1);
                $media_id = $m_media->add($media_data);
                $hotel_ext['hotel_cover_media_id'] = $media_id;
            }
        }
        $hotel_data['tel'] = $tel;
        $m_hotel->saveData($hotel_data, array('id'=>$hotel_id));

        if(!empty($business_lunchshours) && !empty($business_lunchehours) && !empty($business_dinnershours) && !empty($business_dinnerehours)){
            $business_lunchhours = $business_lunchshours.'-'.$business_lunchehours;
            $business_dinnerhours = $business_dinnershours.'-'.$business_dinnerehours;
            $business_hours = $business_lunchhours.','.$business_dinnerhours;
            $hotel_ext['business_hours'] = $business_hours;
        }
        $m_hotelext = new \Common\Model\HotelExtModel();
        $m_hotelext->saveData($hotel_ext, array('hotel_id'=>$hotel_id));

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $data = array('notice'=>$notice);
        $m_merchant->updateData(array('id'=>$merchant_id),$data);

        $this->to_back(array());
    }

    public function setPayee(){
        $openid = $this->params['openid'];
        $payee_openid = $this->params['payee_openid'];
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,merchant.id as merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];

        $m_payee = new \Common\Model\Smallapp\MerchantPayeeModel();
        $res_payee = $m_payee->getInfo(array('merchant_id'=>$merchant_id,'status'=>2));

        if(!empty($res_payee)){
            if($payee_openid==$res_payee['openid']){
                $this->to_back(93045);
                $is_set = 0;
            }else{
                $is_set = 1;
            }
        }else{
            $is_set = 2;
        }
        switch ($is_set){
            case 1:
                $m_payee->updateData(array('merchant_id'=>$merchant_id),array('status'=>4));
                $m_payee->add(array('merchant_id'=>$merchant_id,'openid'=>$openid,'status'=>1));
                break;
            case 2:
                $res_payee = $m_payee->getInfo(array('merchant_id'=>$merchant_id,'openid'=>$openid,'status'=>1));
                if(!empty($res_payee)){
                    $this->to_back(93043);
                }
                $m_payee->add(array('merchant_id'=>$merchant_id,'openid'=>$openid,'status'=>1));
                break;
        }
        $this->to_back(array());
    }


    private function getAvgWhere($avg_id){
        switch ($avg_id){
            case 1:
                $where = array('LT',100);
                break;
            case 2:
                $where = array(array('EGT',100),array('ELT',200));
                break;
            case 3:
                $where = array('GT',200);
                break;
        }
        return $where;

    }
}