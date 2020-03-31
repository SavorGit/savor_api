<?php
namespace Smallapp43\Controller;
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
            case 'hotelList':
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001,'area_id'=>1001,
                    'county_id'=>1002,'food_style_id'=>1002,'avg_exp_id'=>1002);
                break;
        }
        parent::_init_();
    }

    public function hotelList(){
        $page = $this->params['page'] ? $this->params['page'] :1;
        $area_id  = $this->params['area_id'] ? $this->params['area_id'] :1;
        $county_id = $this->params['county_id'];
        $food_style_id = $this->params['food_style_id'];
        $avg_id = $this->params['avg_exp_id'];
        $pagesize = 10;
        $size = $page * $pagesize;

        $where = array('m.status'=>1);
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
        $fields = "hotel.id hotel_id,hotel.name,hotel.addr,hotel.tel,ext.food_style_id,
                   ext.avg_expense,ext.hotel_cover_media_id,food.name as food_name,m.id as merchant_id,m.is_takeout";
        $m_hotel = new \Common\Model\HotelModel();
        $res_merchant = $m_hotel->getMerchantHotelList($fields,$where,'m.is_takeout desc,hotel.pinyin asc',0,$size);
        $datalist = array();
        if($res_merchant['total']){
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
                $is_takeout = 0;
                if(!empty($v['is_takeout'])){
                    $is_takeout = intval($v['is_takeout']);
                }
                $goods = array();
                if($is_takeout && $merchant_id){
                    $oss_host = "https://".C('OSS_HOST').'/';
                    $dgfields = 'id,name,price,cover_imgs';
                    $dgwhere = array('merchant_id'=>$merchant_id,'status'=>1);
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
                }
                $datalist[$k]['goods'] = $goods;
                $datalist[$k]['merchant_id'] = $merchant_id;
                $datalist[$k]['is_takeout'] = $is_takeout;
                $datalist[$k]['img_url'] = $img_url;
            }
        }
        $this->to_back($datalist);
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
        $field = 'hotel.name,hotel.mobile,hotel.tel,hotel.addr,hotel.media_id,hotel.area_id,
        area.region_name as area_name,ext.business_hours,ext.meal_time,
        ext.hotel_cover_media_id,ext.avg_expense,ext.food_style_id,ext.legal_charter';
        $where = array('hotel.id'=>$res_merchant['hotel_id']);
        $res_hotel = $m_hotel->getHotelById($field,$where);

        $merchant = array('name'=>$res_hotel['name'],'mobile'=>$res_hotel['mobile'],'tel'=>$res_hotel['tel'],'area_id'=>$res_hotel['area_id'],
            'addr'=>$res_hotel['addr'],'area_name'=>$res_hotel['area_name'],'avg_expense'=>$res_hotel['avg_expense'],
        );
        $merchant['img'] = '';
        $m_media = new \Common\Model\MediaModel();
        if(!empty($res_hotel['hotel_cover_media_id'])){
            $res_media = $m_media->getMediaInfoById($res_hotel['hotel_cover_media_id'],'https');
            $merchant['img'] = $res_media['oss_addr'].'?x-oss-process=image/resize,p_50/quality,q_80';
        }

        $m_foodstyle = new \Common\Model\FoodStyleModel();
        $res_foodstyle = $m_foodstyle->getOne('name',array('id'=>$res_hotel['food_style_id']),'');
        $merchant['food_style'] = $res_foodstyle['name'];

        $business_lunchhours = $business_dinnerhours = '';
        if(!empty($res_hotel['business_hours'])){
            $business_hours_arr = explode(',',$res_hotel['business_hours']);
            $business_lunchhours = $business_hours_arr[0];
            $business_dinnerhours = $business_hours_arr[1];
        }
        $merchant['business_lunchhours'] = $business_lunchhours;
        $merchant['business_dinnerhours'] = $business_dinnerhours;
        $merchant['meal_time'] = intval($business_dinnerhours);
        $merchant['notice'] = $res_merchant['notice'];

        $charter = array();
        $oss_host = "https://".C('OSS_HOST').'/';
        if(!empty($res_hotel['legal_charter'])){
            $legal_charter_imgs = explode(',',$res_hotel['legal_charter']);
            foreach ($legal_charter_imgs as $v){
                if(!empty($v)){
                    $img_url = $oss_host.$v;
                    $charter[] = $img_url;
                }
            }
        }
        $merchant['charter'] = $charter;

        $m_dishplatform = new \Common\Model\Smallapp\DishplatformModel();
        $where = array('merchant_id'=>$merchant_id);
        $res_platform = $m_dishplatform->getDataList('*',$where,'id desc');
        $datalist = array();
        if(!empty($res_platform)){
            $oss_host = "http://".C('OSS_HOST').'/';
            foreach ($res_platform as $v){
                $img_url = $oss_host.'/'.$v['img_path'];
                $info = array('id'=>$v['id'],'name'=>$v['name'],'img_url'=>$img_url);
                $datalist[]=$info;
            }
        }
        $merchant['platform_img'] = $datalist;
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