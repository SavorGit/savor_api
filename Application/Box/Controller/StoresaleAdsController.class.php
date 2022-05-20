<?php
namespace Box\Controller;
use \Common\Controller\CommonController as CommonController;
class StoresaleAdsController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getAdsList':
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
        }
        parent::_init_(); 
    }
    public function getAdsList(){
        $box_mac = $this->params['box_mac'];
        $m_box = new \Common\Model\BoxModel();
        $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
        $fields = "box.id as box_id,hotel.id as hotel_id";
        $box_info = $m_box->getBoxByCondition($fields,$where);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $hotel_id = $box_info[0]['hotel_id'];
        $box_id = $box_info[0]['box_id'];

        $cache_key = C('SMALLAPP_STORESALE_ADS').$hotel_id;
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(12);
        $res_cachedata = $redis->get($cache_key);
        if(!empty($res_cachedata)){
            $period = $res_cachedata;
        }else {
            $period = getMillisecond();
            $redis->set($cache_key,$period,86400*14);
        }
        $now_date = date('Y-m-d H:i:s');
        $m_life_adshotel = new \Common\Model\Smallapp\StoresaleAdsHotelModel();
        $fields = "media.id as vid,ads.id as ads_id,media.md5,ads.name as chinese_name,media.oss_addr as oss_path,media.duration as duration,
        media.surfix as suffix,sads.start_date,sads.end_date,sads.is_price,sads.goods_id,ads.resource_type as media_type";
        $where = array('a.hotel_id'=>$hotel_id);
        $where['sads.start_date'] = array('ELT',$now_date);
        $where['sads.end_date'] = array('EGT',$now_date);
        $where['sads.state']= 1;
        $order = "sads.id asc";
        $res_data = $m_life_adshotel->getList($fields, $where, $order);
        if(!empty($res_data)){
            $m_media = new \Common\Model\MediaModel();
            $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
            foreach ($res_data as $k=>$v){
                $res_data[$k]['type'] = 'storesale';
                $res_data[$k]['is_price'] = intval($v['is_price']);
                $name_info = pathinfo($v['oss_path']);
                $res_data[$k]['name'] = $name_info['basename'];

                $goods_info = $m_goods->getInfo(array('id'=>$v['goods_id']));
                $res_media = $m_media->getMediaInfoById($goods_info['model_media_id']);
                $res_data[$k]['wine_type'] = intval($goods_info['wine_type']);
                $res_data[$k]['goods_id'] = $v['goods_id'];
                $res_data[$k]['image'] = $res_media['oss_path'];
                $res_data[$k]['price'] = intval($goods_info['price']).'元/瓶';
            }
        }
        $data = array('period'=>$period,'media_list'=>$res_data);
        $this->to_back($data);
    }
}