<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController;
use Common\Lib\Smallapp_api;
use Common\Lib\SavorRedis;
class PlatformController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getBaseInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
           
        }
        parent::_init_();
    }
    public function getBaseInfo(){
        $hotel_id = $this->params['hotel_id'];
        
        $redis = SavorRedis::getInstance();
        $redis->select(15);
        $cache_key  = 'savor_hotel_ext_'.$hotel_id;
        $cache_info = $redis->get($cache_key);
        $hotel_info = json_decode($cache_info,true);
        if($hotel_info['mac_addr']=='94100'){
            $this->to_back(16204);
        }
        $data = [];
        $m_device_upgrade = new \Common\Model\DeviceUpgradeModel();
        $up_info  = $m_device_upgrade->getNewSmallApkInfo($hotel_id);
        $data['last_version'] = $up_info['version_name'];
        $redis->select(13);
        $cache_key  = 'heartbeat:1:'.$hotel_info['mac_addr'];
        $cache_info = $redis->get($cache_key);
        if(!empty($cache_info)){
            $heart_info = json_decode($cache_info,true);
            $m_device = new \Common\Model\DeviceVersionModel();
            $p_info = $m_device->getOneByVersionAndDevice($heart_info['war'],1);
            
            $data['now_version']= $p_info['version_name'];
            $data['heart_time'] = date('Y-m-d H:i:s',strtotime($heart_info['date']));
            $data['intranet_ip']= $heart_info['intranet_ip'];
            $data['outside_ip'] = $heart_info['outside_ip'];
        }else {
            $data['now_version']= '';
            $data['heart_time'] = '';
            $data['intranet_ip']= '';
            $data['outside_ip'] = '';
        }
        $m_program_menu_hotel = new \Common\Model\ProgramMenuHotelModel();
        $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
        $m_box = new \Common\Model\BoxModel();
        $m_pub_ads = new \Common\Model\PubAdsModel();
        
        $program_ads_cache_pre = C('PROGRAM_ADS_CACHE_PRE');
        
        $redis->select(12);
        //获取该酒楼下的最新节目单
        //获取最新节目单
        $fields = "a.menu_id,pl.menu_num";
        $order  = "pl.id desc ";
        $limit  = " limit 0,1";
        $menu_info = $m_program_menu_hotel->getLatestMenuid($hotel_id);//获取最新的一期节目单
        $newest_num = 0;
        if($menu_info){//节目资源
            $newest_num = $menu_info['menu_num'];
            $menu_id   = $menu_info['menu_id'];
            $map = array();
            $map['a.menu_id'] = $menu_id;
            $map['a.type'] = 2;
            $fields = "media.id media_id,ads.name media_name";
            $order ="a.sort_num asc";
            $pro_list = $m_program_menu_item->getList($fields, $map, $order, '');
            
        }
        //宣传片
        $field = "media.id AS media_id,
				item.ads_name AS media_name,
                'adv' as type";
        $sql = "select ".$field;
        $sql .= " FROM savor_ads ads
        LEFT JOIN savor_programmenu_item item on ads.name like CONCAT('%',item.ads_name,'%')
        LEFT JOIN savor_media media on media.id = ads.media_id
        where item.type=3
        and ads.hotel_id={$hotel_id}
        and (item.ads_id is null or item.ads_id=0)
        and ads.state=1
        and item.menu_id={$menu_id}
        and media.oss_addr is not null order by item.sort_num asc";
        $adv_arr = $m_program_menu_item->query($sql);
        
       
        
        //获取该酒楼下的盒子
        $box_list = $m_box->getInfoByHotelid($hotel_id, 'box.id box_id', " and box.state=1 and box.flag=0");
        
        $ads_arr = array();
        foreach($box_list as $kk=>$vv){
            $cache_key = $program_ads_cache_pre.$vv['box_id'];
            $redis_value = $redis->get($cache_key);
            if($redis_value){
                $redis_value = json_decode($redis_value,true);
                $redis_value = $redis_value['ads_list'];
                $redis_value = $this->assoc_unique($redis_value,'pub_ads_id');
                $pub_ads_id_arr = array_keys($redis_value);
                $whs = array();
                $whs['a.id'] = array('in',$pub_ads_id_arr);
                $whs['a.state']  = array('neq',2);
                $ads_list = $m_pub_ads->getPubAdsList('med.id media_id,ads.name media_name',$whs);
                foreach($ads_list as $ks=>$vs){
                    $ads_arr[] = $vs;
                }
            }
        }
        $ads_arr = $this->assoc_unique($ads_arr, 'media_id');
        
        $media_arr = array_merge($pro_list,$adv_arr,$ads_arr);
        
        
        $z_media_arr = array();
        foreach($media_arr as $zk=>$zv){
            $z_media_arr[] = $zv['media_id'];
        }
        $cache_key = C('SMALL_PROGRAM_LIST_KEY').$hotel_id;
        $redis->select(8);
        $upload_media_list = $redis->get($cache_key);
        $flag = 1;
        if($upload_media_list){
            $upload_media_list = json_decode($upload_media_list,true);
            $upload_media_list = $upload_media_list['media_list'];
            
            //宣传片
            $adv_cache_key = C('PROGRAM_ADV_CACHE_PRE').$hotel_id;
            $redis->select(12);
            $adv_arr = $redis->get($adv_cache_key);
            $redis_advarr = json_decode($adv_arr,true);
            $adv_num = $redis_advarr['adv_num'].$newest_num;
            
            //广告
            $ads_cache_key = C('PROGRAM_ADS_CACHE_PRE').$box_list[0]['box_id'];
            $ads_arr = $redis->get($ads_cache_key);
            $redis_adsarr = json_decode($ads_arr,true);
            $ads_num = $redis_adsarr['menu_num'];
            
            $up_media_arr = array();
            $newest_upmedia_arr = array();
            $data_type = 0;
            foreach($upload_media_list as $mk=>$mv){
                if(isset($mv['version']) && $newest_num){
                    $now_menun_num = $newest_num;
                    
                    if($mv['type']=='adv'){
                        $now_menun_num = $adv_num;
                    }elseif($mv['type']=='ads'){
                        $now_menun_num = $ads_num;
                    }
                    
                    if($mv['version']==$now_menun_num || in_array($mv['id'],$z_media_arr)){
                        $newest_upmedia_arr[]=$mv['id'];
                        if(!isset($mv['flag'])){
                            $flag = 0;
                        }elseif(isset($mv['flag']) && $mv['flag']==0){
                            $flag = 0;
                        }
                    }else{
                        $data_type = 1;
                    }
                }
                $up_media_arr[] = $mv['id'];
            }
            if(isset($upload_media_list[0]['version']) && $newest_num && $data_type==0){
                $data_type = 2;
            }
            if($flag==1){
                //		           $diff_arr = array_diff($z_media_arr, $up_media_arr);
                if(!empty($newest_upmedia_arr)){
                    $diff_arr = array_diff($z_media_arr, $newest_upmedia_arr);
                    if(!empty($diff_arr)){
                        $flag = 0;
                    }
                }
            }
        }else {
            $flag = 0;
        }
        if($flag==0){
            $data['is_download'] = '未下载完';
        }else {
            $data['is_download'] = '已下载完';
        }
        $this->to_back($data);
        
    }
    private function assoc_unique($arr, $key)
    {
        $rAr = array();
        for ($i = 0; $i<count($arr); $i++)
        {
            if (!isset($rAr[$arr[$i][$key]]))
            {
                $rAr[$arr[$i][$key]] = $arr[$i];
            }
        }
        return $rAr;
    }
}