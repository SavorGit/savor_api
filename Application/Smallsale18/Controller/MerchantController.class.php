<?php
namespace Smallsale18\Controller;
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
            case 'register':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'name'=>1001,'food_style_id'=>1001,'avg_exp'=>1001,
                    'tel'=>1001,'area_id'=>1001,'county_id'=>1002,'addr'=>1001,'logoimg'=>1002,
                    'faceimg'=>1001,'envimg'=>1002,'legal_name'=>1001,'legal_idcard'=>1001,
                    'legal_charter'=>1001,'contractor'=>1001,'mobile'=>1001,'verify_code'=>1001
                );
                break;
        }
        parent::_init_();
    }

    public function info(){
        $merchant_id = intval($this->params['merchant_id']);
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('id'=>$merchant_id));
        if(empty($res_merchant) || $res_merchant['status']!=1){
            $this->to_back(93035);
        }

        $m_hotel = new \Common\Model\HotelModel();
        $field = 'hotel.name,hotel.mobile,hotel.tel,hotel.addr,ext.hotel_cover_media_id,ext.avg_expense,ext.food_style_id';
        $where = array('hotel.id'=>$res_merchant['hotel_id']);
        $res_hotel = $m_hotel->getHotelById($field,$where);

        $merchant = array('name'=>$res_hotel['name'],'mobile'=>$res_hotel['mobile'],
            'tel'=>$res_hotel['tel'],'addr'=>$res_hotel['addr'],
            'avg_expense'=>$res_hotel['avg_expense'],'tips'=>'不出门抗击疫情，线上享超值菜品');
        $merchant['img'] = '';
        if(!empty($res_hotel['hotel_cover_media_id'])){
            $m_media = new \Common\Model\MediaModel();
            $res_media = $m_media->getMediaInfoById($res_hotel['hotel_cover_media_id'],'https');
            $merchant['img'] = $res_media['oss_addr'].'?x-oss-process=image/resize,p_50/quality,q_80';
        }
        $m_foodstyle = new \Common\Model\FoodStyleModel();
        $res_foodstyle = $m_foodstyle->getOne('name',array('id'=>$res_hotel['food_style_id']),'');
        $merchant['food_style'] = $res_foodstyle['name'];
        $host_name = 'https://'.$_SERVER['HTTP_HOST'];
        $merchant['qrcode_url'] = $host_name."/smallsale18/qrcode/dishQrcode?data_id=$merchant_id&type=24";
        $this->to_back($merchant);
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
            'addr'=>$addr,'contractor'=>$contractor,'mobile'=>$mobile,'tel'=>$tel,'flag'=>2,'type'=>2,'openid'=>$openid);
        if(!empty($logoimg)){
            $typeinfo = C('RESOURCE_TYPEINFO');
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
                'legal_name'=>$legal_name,'legal_idcard'=>$legal_idcard,'legal_charter'=>$legal_charter);
            $m_hotelext = new \Common\Model\HotelExtModel();
            $m_hotelext->add($add_hotelext);
        }
        $message = '您的申请已经成功提交，稍后会有工作人员与您核实信息。请保持通话畅通。';
        $res_data = array('message'=>$message);
        $this->to_back($res_data);
    }


}