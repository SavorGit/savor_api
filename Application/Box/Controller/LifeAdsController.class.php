<?php
namespace Box\Controller;
use \Common\Controller\CommonController as CommonController;
class LifeAdsController extends CommonController{
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

        $cache_key = C('SMALLAPP_LIFE_ADS').$hotel_id;
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
        $m_life_adshotel = new \Common\Model\Smallapp\LifeAdsHotelModel();
        $fields = "media.id as vid,ads.id as ads_id,media.md5,ads.name as chinese_name,media.oss_addr as oss_path,media.duration as duration,
        media.surfix as suffix,lads.start_date,lads.end_date,ads.resource_type as media_type";
        $where = array('a.hotel_id'=>$hotel_id);
        $where['lads.start_date'] = array('ELT',$now_date);
        $where['lads.end_date'] = array('EGT',$now_date);
        $where['lads.state']= 1;
        $order = "lads.id asc";
        $res_data = $m_life_adshotel->getList($fields, $where, $order);
        if(!empty($res_data)){
            $host_name = C('HOST_NAME');
            $m_store = new \Common\Model\Smallapp\StoreModel();
            foreach ($res_data as $k=>$v){
                $res_data[$k]['type'] = 'life';
                $name_info = pathinfo($v['oss_path']);
                $res_data[$k]['name'] = $name_info['basename'];
                $qrcode_url = '';
                if($v['ads_id']){
                    $res_store = $m_store->getInfo(array('ads_id'=>$v['ads_id']));
                    if(!empty($res_store)){
                        $data_id = $res_store['id'];
                        $qrcode_url = $host_name."/Smallapp46/qrcode/getBoxQrcode?box_mac={$box_mac}&box_id={$box_id}&data_id={$data_id}&type=28";
                    }
                }
                $res_data[$k]['qrcode_url'] = $qrcode_url;
            }
        }
        $data = array('period'=>$period,'media_list'=>$res_data);
        $this->to_back($data);
    }
}