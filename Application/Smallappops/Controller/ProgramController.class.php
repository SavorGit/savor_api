<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController;
use Common\Lib\Smallapp_api;
use Common\Lib\SavorRedis;
class ProgramController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getBoxPlayList':
                $this->is_verify = 1;
                $this->valid_fields = array('box_id'=>1001);
                break;
            case 'getLastProgramList':
                $this->is_verify = 1;
                $this->valid_fields = array('box_id'=>1001);
                break;
        }
        parent::_init_();
    }
    /**
     * 获取当前机顶盒播放节目单列表
     */
    public function getBoxPlayList(){
        $box_id = $this->params['box_id'];
        $redis = SavorRedis::getInstance();
        $redis->select(15);
        $cache_key  = 'savor_box_'.$box_id;
        $cache_info = $redis->get($cache_key);
        $box_info = json_decode($cache_info,true);
        
        $box_mac = $box_info['mac'];
        $redis->select(14);
        $cache_key  = 'box:play:'.$box_mac;
        $cache_info = $redis->get($cache_key);
        $play_list  = json_decode($cache_info,true);
        
        if(!empty($play_list['list'])){
            $play_list  = $play_list['list'];
        }else{
            $play_list  = [];
        }
        $m_media = new \Common\Model\MediaModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        foreach($play_list as $key=>$v){
            
            $map = [];
            $fields = "concat('".$oss_host."',a.`oss_addr`) oss_path,a.oss_addr,b.name,b.id ads_id";
            $map['a.id'] = $v['media_id'];
            $media_info  = $m_media->alias('a')
                                   ->join('savor_ads b on b.media_id =a.id','left')
                                   ->field($fields)
                                   ->where($map)
                                   ->find();
            $play_list[$key]['oss_path'] = $media_info['oss_path'];
            $play_list[$key]['oss_addr'] = $media_info['oss_addr'];
            $play_list[$key]['name']     = $media_info['name'];
            $play_list[$key]['ads_id']   = $media_info['ads_id'];
            switch ($v['type']){
                case 'pro':
                    $play_list[$key]['media_type'] ='节目';
                    break;
                case 'adv':
                    $play_list[$key]['media_type'] ='宣传片';
                    break;
                case 'ads':
                    $play_list[$key]['media_type'] ='广告';
                    break;
            }
        }
        $this->to_back($play_list);
    }
    /**
     * 获取机顶盒当前最新节目单
     */
    public function getLastProgramList(){
        $box_id = $this->params['box_id'];
        $redis = SavorRedis::getInstance();
        $redis->select(15);
        $cache_key = 'savor_box_'.$box_id;
        $cache_info = $redis->get($cache_key);
        $box_info = json_decode($cache_info,true);
        $box_mac = $box_info['mac'];
        
        $cache_key = 'savor_room_'.$box_info['room_id'];
        $cache_info = $redis->get($cache_key);
        $room_info = json_decode($cache_info,true);
        
        $hotelid = $room_info['hotel_id'];
        
        $cache_key = 'savor_hotel_ext_'.$hotelid;
        $cache_info = $redis->get($cache_key);
        $hotel_ext_info = json_decode($cache_info,true);
        
        if($hotel_ext_info['mac_addr']=='000000000000'){//虚拟小平台
            $redis->select(10);
            $cache_key = 'vsmall:pro:'.$hotelid.":".$box_mac;
            $cache_info = $redis->get($cache_key);
            $menu_list = json_decode($cache_info,true);
            
            if(!empty($menu_list)){
                $pro_list = $menu_list['playbill_list'][0]['media_lib'];
                $adv_list = $menu_list['playbill_list'][1]['media_lib'];
                $ads_list = $menu_list['playbill_list'][2]['media_lib'];
                $program_list = array_merge($pro_list,$adv_list,$ads_list);
            } 
            //获取宣传片
            $cache_key = 'vsmall:adv:'.$hotelid.':'.$box_mac;
            $cache_info = $redis->get($cache_key);
            $adv_list = json_decode($cache_info,true);
            $adv_list = $adv_list['media_lib'];
            
            //获取广告
            $cache_key = 'vsmall:ads:'.$hotelid.":".$box_mac;
            $cache_info = $redis->get($cache_key);
            $ads_list = json_decode($cache_info,true);
            $ads_list = $ads_list['media_lib'];
            
            
            
        }else {//实体小平台
            //获取节目单
            $redis->select(12);
            $cache_key = C('PROGRAM_PRO_CACHE_PRE').$hotelid;
            
            $cache_info = $redis->get($cache_key);
            $menu_list = json_decode($cache_info,true);
            if(!empty($menu_list)){
                $program_list = $menu_list['media_list'];
            }
            
            //获取宣传片
            $cache_key = C('PROGRAM_ADV_CACHE_PRE').$hotelid;
            $cache_info = $redis->get($cache_key);
            $adv_list = json_decode($cache_info,true);
            $adv_list = $adv_list['adv_arr'];
            
            //获取广告
            $cache_key = C('PROGRAM_ADS_CACHE_PRE').$box_id;
            $cache_info = $redis->get($cache_key);
            $ads_list  = json_decode($cache_info,true);
            $ads_list = $ads_list['ads_list'];
            
            
        }
        $m_new_menu_hotel = new \Common\Model\ProgramMenuHotelModel();
        $ads = new \Common\Model\AdsModel();
        //获取最新节目单
        $menu_info = $m_new_menu_hotel->getLatestMenuid($hotelid);   //获取最新的一期节目单
        
        if(empty($menu_info)){//该酒楼未设置节目单
            $this->to_back(16205);
        }
        $menu_id = $menu_info['menu_id'];
        $menu_num= $menu_info['menu_num'];
        $pub_time = $menu_info['pub_time'];
        if(empty($program_list)){
            $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
            $menu_item_arr = $m_program_menu_item->getMenuInfo($menu_id); //获取节目单的节目列表
            $menu_item_arr = $this->changeadvList($menu_item_arr);
            $adv_item_arr = $m_program_menu_item->getMenuAds($menu_id);
            foreach($adv_item_arr as $key=>$v){
                if($v['type']=='adv'){
                    $adv_item_arr[$key]['location_id'] = $v['order'];
                }
            }
            $menu_arr =  array_merge($menu_item_arr,$adv_item_arr);
            foreach($menu_arr as $key=>$v){
                $order_arr[$key] = $v['order'];
            }
            $program_list = $this->array_sort($menu_arr,'order','asc');
        }
        //获取宣传片
        if(empty($adv_list)){
            $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
            $adv_arr = $m_program_menu_item->getadvInfo($hotelid, $menu_id);
            $m_ads= new \Common\Model\AdsModel();
            $redis_arr = $m_ads->getWhere(array('hotel_id'=>$hotelid,'type'=>3),'max(update_time) as max_update_time');
            
            $data = array();
            $adv_list = $this->changeadvList($adv_arr,2);
        }
        //获取广告
        if(empty($ads_list)){
            $max_adv_location = C('MAX_ADS_LOCATION_NUMS');
            $now_date = date('Y-m-d H:i:s');
            $data =  array();
            $v_keys = 0;
            $m_pub_ads_box = new \Common\Model\PubAdsBoxModel(); 
            for($i=1;$i<=$max_adv_location;$i++){
                $adv_arr = $m_pub_ads_box->getAdsList($box_id,$i);  //获取当前机顶盒得某一个位置得广告
                $adv_arr = $this->changeadvList($adv_arr);
                
                if(!empty($adv_arr)){
                    $flag =0;
                    foreach($adv_arr as $ak=>$av){
                        if($av['start_date']>$now_date){
                            $flag ++;
                        }
                        if($flag==2){
                            unset($adv_arr[$ak]);
                            break;
                        }
                        
                        $ads_arr['id']          = $av['id'];
                        $ads_arr['name']        = $av['name'];
                        $ads_arr['md5']         = $av['md5'];
                        $ads_arr['md5_type']    = $av['md5_type'];
                        $ads_arr['chinese_name']= $av['chinese_name'];
                        $ads_arr['type']        = $av['type'];
                        $ads_arr['oss_path']    = $av['oss_path'];
                        $ads_arr['duration']    = $av['duration'];
                        $ads_arr['suffix']      = $av['suffix'];
                        $ads_arr['start_date']  = $av['start_date'];
                        $ads_arr['end_date']    = $av['end_date'];
                        $ads_arr['pub_ads_id']  = $av['pub_ads_id'];
                        $ads_arr['create_time'] = $av['create_time'];
                        $ads_arr['location_id'] = $av['location_id'];
                        $ads_arr['is_sapp_qrcode'] = $av['is_sapp_qrcode'];
                        $ads_arr['media_type']  = $av['media_type'];
                        $ads_list[] = $ads_arr;           
                    }
                }
            }
        }
        $adv_arr = [];
        
        foreach($adv_list as $key=>$v){
            $adv_arr[$v['location_id']] = $v;
        }
        $ads_arr = [];
        foreach($ads_list as $key=>$v){
            $ads_arr[$v['location_id']] = $v;
        }
        foreach($program_list as $key=>$v){
            if($v['type']=='adv'){
                //print_r($adv_arr[$v['location_id']]);exit;
                $adv_arr[$v['location_id']]['order'] = $v['order'];
                $program_list[$key] = $adv_arr[$v['location_id']];
            }else if($v['type']=='ads'){
                $ads_arr[$v['location_id']]['order'] = $v['order'];
                $program_list[$key] = $ads_arr[$v['location_id']];
            }
            if(empty($program_list[$key]['oss_path'])){
                unset($program_list[$key]);
            }
            
        }
        array_multisort($program_list,'order');
        //print_r($program_list);exit;
        
        
        $this->to_back($program_list);
    }
    private function changeadvList($res,$type=1){
        if($res){
            foreach ($res as $vk=>$val) {
                if(!empty($val['sortNum'])){
                    if($type==1){
                        $res[$vk]['order'] =  $res[$vk]['sortNum'];
                    }else {
                        $res[$vk]['location_id'] = $res[$vk]['sortNum'];
                    }
                    
                    unset($res[$vk]['sortNum']);
                }
                
                if(!empty($val['name'])){
                    $ttp = explode('/', $val['name']);
                    $res[$vk]['name'] = $ttp[2];
                }
                if($val['media_type']==2){
                    $res[$vk]['md5_type'] = 'fullMd5';
                }
                $res[$vk]['is_sapp_qrcode'] = intval($val['is_sapp_qrcode']);
            }
            
        }
        return $res;
        //如果是空
    }
    private function array_sort($array,$keys,$type='asc'){
        //$array为要排序的数组,$keys为要用来排序的键名,$type默认为升序排序
        $keysvalue = $new_array = array();
        foreach ($array as $k=>$v){
            $keysvalue[$k] = $v[$keys];
        }
        if($type == 'asc'){
            asort($keysvalue);
        }else{
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k=>$v){
            $new_array[] = $array[$k];
        }
        return $new_array;
    }
}