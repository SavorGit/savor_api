<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
class StoreController extends CommonController{

    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'dataList':
                $this->is_verify =1;
                $this->valid_fields = array('page'=>1001,'area_id'=>1001,'country_id'=>1002,
                    'latitude'=>1002,'longitude'=>1002,'cate_id'=>1001,
                    'food_style_id'=>1002,'avg_exp_id'=>1002
                );
                break;

        }
        parent::_init_();
    }
    /**
     * @desc 店铺列表
     */
    public function dataList(){
        $page     = $this->params['page'] ? $this->params['page'] :1;
        $area_id  = $this->params['area_id'] ? $this->params['area_id'] :1;
        $county_id = $this->params['county_id'];
        $cate_id = intval($this->params['cate_id']);
        $food_style_id = $this->params['food_style_id'];
        $avg_id   = $this->params['avg_exp_id'];
        $latitude = $this->params['latitude'];
        $longitude = $this->params['longitude'];
        $pagesize = 10;

        $m_store = new \Common\Model\Smallapp\StoreModel();
        if($cate_id==0){
            $res_store = $m_store->getAllStores($area_id);
        }elseif($cate_id==120){
            $res_store = $m_store->getHotelStore($area_id,$county_id,$food_style_id,$avg_id);
        }else{
            $res_store = $m_store->getLifeStore($area_id,$county_id,$cate_id,$avg_id);
        }
        if($longitude>0 && $latitude>0){
            $bd_lnglat = getgeoByTc($latitude, $longitude);
            foreach($res_store as $key=>$v){
                $res_store[$key]['dis'] = '';
                if($v['gps']!='' && $longitude>0 && $latitude>0){
                    $latitude = $bd_lnglat[0]['y'];
                    $longitude = $bd_lnglat[0]['x'];

                    $gps_arr = explode(',',$v['gps']);
                    $dis = geo_distance($latitude,$longitude,$gps_arr[1],$gps_arr[0]);
                    $res_store[$key]['dis_com'] = $dis;
                    if($dis>1000){
                        $tmp_dis = $dis/1000;
                        $dis = sprintf('%0.2f',$tmp_dis);
                        $dis = $dis.'km';
                    }else{
                        $dis = intval($dis);
                        $dis = $dis.'m';
                    }
                    $res_store[$key]['dis'] = $dis;
                }else {
                    $res_store[$key]['dis'] = '';
                }
            }
            sortArrByOneField($res_store,'dis_com');
        }

        $offset = $page * $pagesize;
        $hotel_list = array_slice($res_store,0,$offset);
        $m_meida = new \Common\Model\MediaModel();
        $datalist = array();
        foreach ($hotel_list as $k=>$v){
            $tag_name = $v['tag_name'];
            if(empty($tag_name)){
                $tag_name = '';
            }
            if($v['media_id']){
                $res_media = $m_meida->getMediaInfoById($v['media_id']);
                $img_url = $res_media['oss_addr'].'?x-oss-process=image/resize,p_20';
                $ori_img_url = $res_media['oss_addr'];
            }else{
                $img_url = 'http://oss.littlehotspot.com/media/resource/kS3MPQBs7Y.png';
                $ori_img_url = $img_url;
            }
            $dis = $v['dis'];
            if(empty($dis)){
                $dis = '';
            }
            $tel = $v['tel'];
            if(empty($tel)){
                $tel = $v['mobile'];
            }
            $datalist[]=array('hotel_id'=>$v['hotel_id'],'name'=>$v['name'],'addr'=>$v['addr'],'tel'=>$tel,'avg_expense'=>$v['avg_expense'],
                'dis'=>$dis,'tag_name'=>$tag_name,'img_url'=>$img_url,'ori_img_url'=>$ori_img_url
            );

        }
        $this->to_back(array('datalist'=>$datalist));
    }


}