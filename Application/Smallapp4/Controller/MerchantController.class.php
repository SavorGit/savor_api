<?php
namespace Smallapp4\Controller;
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
            case 'merchantList':
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001,'area_id'=>1001,
                    'county_id'=>1002,'food_style_id'=>1002,'avg_exp_id'=>1002);
                break;
        }
        parent::_init_();
    }

    public function merchantList(){
        $page = $this->params['page'] ? $this->params['page'] :1;
        $area_id  = $this->params['area_id'] ? $this->params['area_id'] :1;
        $county_id = $this->params['county_id'];
        $food_style_id = $this->params['food_style_id'];
        $avg_id = $this->params['avg_exp_id'];
        $pagesize = 10;
        $size = $page * $pagesize;

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $fields = "m.id as merchant_id,hotel.id hotel_id,hotel.name,hotel.addr,hotel.tel,ext.food_style_id,
                   ext.avg_expense,ext.hotel_cover_media_id,food.name as food_name";
        $where = array('m.status'=>1,'m.is_takeout'=>1);
        $where['m.id'] = array('not in','89');
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
        $res_merchant = $m_merchant->getMerchantList($fields,$where,'m.id desc',0,$size);
        $datalist = array();
        if($res_merchant['total']){
            $datalist = $res_merchant['list'];
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
                $datalist[$k]['is_takeout'] = 1;
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
        $field = 'hotel.name,hotel.mobile,hotel.tel,hotel.addr,ext.hotel_cover_media_id,ext.avg_expense,ext.food_style_id';
        $where = array('hotel.id'=>$res_merchant['hotel_id']);
        $res_hotel = $m_hotel->getHotelById($field,$where);

        $merchant = array('name'=>$res_hotel['name'],'mobile'=>$res_hotel['mobile'],
            'tel'=>$res_hotel['tel'],'addr'=>$res_hotel['addr'],
            'avg_expense'=>$res_hotel['avg_expense']);
        $merchant['img'] = '';
        if(!empty($res_hotel['hotel_cover_media_id'])){
            $m_media = new \Common\Model\MediaModel();
            $res_media = $m_media->getMediaInfoById($res_hotel['hotel_cover_media_id'],'https');
            $merchant['img'] = $res_media['oss_addr'];
        }
        $m_foodstyle = new \Common\Model\FoodStyleModel();
        $res_foodstyle = $m_foodstyle->getOne('name',array('id'=>$res_hotel['food_style_id']),'');
        $merchant['food_style'] = $res_foodstyle['name'];
        $host_name = 'https://'.$_SERVER['HTTP_HOST'];
        $merchant['qrcode_url'] = $host_name."/smallsale18/qrcode/dishQrcode?data_id=$merchant_id&type=24";

        $m_dishplatform = new \Common\Model\Smallapp\DishplatformModel();
        $res_platform = $m_dishplatform->getDataList('img1,img2,img3',array('merchant_id'=>$merchant_id),'id desc');
        $platform_img = array();
        if(!empty($res_platform)){
            $oss_host = get_oss_host();
            $img1 = $oss_host.'/'.$res_platform[0]['img1'];
            $img2 = $oss_host.'/'.$res_platform[0]['img2'];
            $img3 = $oss_host.'/'.$res_platform[0]['img3'];
            $platform_img = array('img1'=>$img1,'img2'=>$img2,'img3'=>$img3);
        }
        $merchant['platform_img'] = $platform_img;
        $this->to_back($merchant);
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