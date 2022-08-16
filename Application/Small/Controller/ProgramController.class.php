<?php
namespace Small\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;
class ProgramController extends CommonController{
    /**
     * 构造函数
     */
    public $max_adv_location = 50;
    var $menu_type;
    var $heart_hotel_box_type_arr;
    function _init_() {
        switch(ACTION_NAME) {
            case 'getMenuByHotelid':
                $this->is_verify=1;
                $this->valid_fields = array('hotelid'=>1001);
                break;   
            case 'getAdvByHotelid':
                $this->is_verify = 1;
                $this->valid_fields = array('hotelid'=>1001);
                break; 
            case 'getAdsByHotelid';
                $this->is_verify = 1;
                $this->valid_fields = array('hotelid'=>1001);
                break;    
            case 'updateAdsDownState':
                $this->is_verify = 1;
                $this->valid_fields = array('menu_num'=>1001);
                break;
            case 'updateMenuDwonState':
                $this->is_verify = 1;
                $this->valid_fields = array('menu_num'=>1001,'hotel_id'=>1001,'type'=>1001);
                break;
            case 'rtbAdsList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'program_list'=>1000);
                break;
            case 'uploadSmallProgramList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
        }
        $this->menu_type = array('pro'=>1,'adv'=>2,'all'=>3);
        $hotel_box_type_arr = C('HEART_HOTEL_BOX_TYPE');
        $hotel_box_type_arr = array_keys($hotel_box_type_arr);
        
        $this->heart_hotel_box_type_arr = $hotel_box_type_arr;
        parent::_init_();
    }
    /**
     * @desc 获取当前酒楼节目单列表
     */
    public function getMenuByHotelid(){
        
        $hotelid = $this->params['hotelid'];    //酒楼id
        $hotelid = intval($hotelid);
        $m_hotel = new \Common\Model\HotelModel();
        $hotel_info = $m_hotel->getOneById('hotel_box_type,state,flag',$hotelid);

        if(empty($hotel_info)){//该酒楼不存在
            $this->to_back(16200);   
        }
        if($hotel_info['flag'] !=0){//该酒楼已删除
            $this->to_back(16201);
        }
        if($hotel_info['state'] !=1){ //该酒楼为非正常酒楼
            $this->to_back(16202);
        }
        
        
        if(!in_array($hotel_info['hotel_box_type'], $this->heart_hotel_box_type_arr)){//该酒楼为非网络版酒楼 
            $this->to_back(16203);   
        }
        $m_box = new \Common\Model\BoxModel();
        $list = $m_box->getBoxListByHotelid('a.id boxId,a.mac,a.room_id', $hotelid);   //获取该酒楼下正常机顶盒
        if(empty($list)){//该酒楼下没有正常的机顶盒
            $this->to_back(16204);
        }
        $redis = new SavorRedis();
        $redis->select(12);
        $cache_key = C('PROGRAM_PRO_CACHE_PRE').$hotelid;
        $menu_cache_info = $redis->get($cache_key);
        $menu_cache_info = json_decode($menu_cache_info,true);
        if(empty($menu_cache_info['media_list'])){//如果缓存中没有 ，查询该酒楼对应的节目单
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
            $menu_list = $this->array_sort($menu_arr,'order','asc');
            
            $cache_value = array();
            $cache_value['menu_num'] = $menu_num;
            $cache_value['menu_id']  = $menu_id;
            $cache_value['pub_time'] = $pub_time;
            $cache_value['media_list']= $menu_list;
            $redis->set($cache_key, json_encode($cache_value),86400);
            
        }else {//如果缓存中有该酒楼的节目单信息
            $menu_num  = $menu_cache_info['menu_num'];
            $pub_time  = $menu_cache_info['pub_time'];
            $menu_list = $menu_cache_info['media_list'];
        }
        $data = array();
        foreach($list as $key=>$v){
            $data[$key]['box_id']     = $v['boxId'];
            $data[$key]['room_id']    = $v['room_id']; //机顶盒id
            $data[$key]['box_mac']    = $v['mac'];
            $data[$key]['pub_time']   = $pub_time;
            $data[$key]['menu_num']   = $menu_num;
            $data[$key]['media_list'] = $menu_list; 
        }
        $this->to_back($data);
     }
     /**
      * @desc 获取当前酒楼下所有机顶盒的宣传片
      */
     public function getAdvByHotelid(){
         $hotelid = $this->params['hotelid'];    //酒楼id
         $hotelid = intval($hotelid);
         $m_hotel = new \Common\Model\HotelModel();
         $hotel_info = $m_hotel->getOneById('hotel_box_type,state,flag',$hotelid);
         
         if(empty($hotel_info)){//该酒楼不存在
             $this->to_back(16200);
         }
         if($hotel_info['flag'] !=0){//该酒楼已删除
             $this->to_back(16201);
         }
         if($hotel_info['state'] !=1){ //该酒楼为非正常酒楼
             $this->to_back(16202);
         }
         if(!in_array($hotel_info['hotel_box_type'], $this->heart_hotel_box_type_arr)){//该酒楼为非网络版酒楼
             $this->to_back(16203);
         }
         $m_box = new \Common\Model\BoxModel();
         $list = $m_box->getBoxListByHotelid('a.id boxId,a.mac,a.room_id', $hotelid);   //获取该酒楼下正常机顶盒
         
         if(empty($list)){//该酒楼下没有正常的机顶盒
             $this->to_back(16204);
         }
         
         $m_new_menu_hotel = new \Common\Model\ProgramMenuHotelModel();
         //获取最新节目单
         
         
         $menu_info = $m_new_menu_hotel->getLatestMenuid($hotelid);   //获取最新的一期节目单
         if(empty($menu_info)){//该酒楼未设置节目单
             $this->to_back(16205);
         }   
         $menu_id = $menu_info['menu_id'];
         $menu_num= $menu_info['menu_num'];
         
         $redis = SavorRedis::getInstance();
         $redis->select(12);
         
         $adv_cache_key = C('PROGRAM_ADV_CACHE_PRE').$hotelid;
         $adv_arr = $redis->get($adv_cache_key);
         $redis_arr = json_decode($adv_arr,true);
         
         if(empty($redis_arr['adv_arr'])){
             $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
             $adv_arr = $m_program_menu_item->getadvInfo($hotelid, $menu_id);
             $m_ads= new \Common\Model\AdsModel();
             $redis_arr = $m_ads->getWhere(array('hotel_id'=>$hotelid,'type'=>3),'max(update_time) as max_update_time');
              
             /* foreach($adv_arr as $key=>$v){
              $redis_arr[] = $v['update_time'];
              unset($adv_arr[$key]['update_time']);
             } */
              
             $data = array();
             $adv_arr = $this->changeadvList($adv_arr,2);
              
             if(empty($redis_arr)){
                 $adv_num = date('Ymdhis');
             }else {
                 $adv_num = $redis_arr[0]['max_update_time'];
                 $adv_num = date('YmdHis',strtotime($adv_num));
             }
             $redis_arr = array();
             $redis_arr['adv_num'] = $adv_num;
             $redis_arr['menu_num']= $menu_num;
             $redis_arr['adv_arr'] = $adv_arr;
             
             $redis->set($adv_cache_key, json_encode($redis_arr),86400);
             
         }else {
             
             $adv_arr   = $redis_arr['adv_arr'];
             $adv_num   = $redis_arr['adv_num'];
             $menu_num  = $redis_arr['menu_num'];
         }
         
         
         foreach($list as $key=>$v){
             $data[$key]['box_id']    = $v['boxId'];              //机顶盒id
             $data[$key]['room_id']    = $v['room_id'];
             $data[$key]['box_mac']   = $v['mac'];
             $data[$key]['menu_num'] = $adv_num.$menu_num;        //宣传片号 = 宣传片最后更新时间+节目单号
             $data[$key]['program_num'] = $menu_num;              //节目单号
             $data[$key]['media_list'] = $adv_arr;
         }
         $this->to_back($data);
     }
     
     /**
      * @desc 获取当前酒楼下所有机顶盒的广告
      */
     public function getAdsByHotelid(){
        
         $hotel_id = $this->params['hotelid'];
         $hotelid = intval($hotel_id);
         
         $m_hotel = new \Common\Model\HotelModel();
         $hotel_info = $m_hotel->getOneById('hotel_box_type,state,flag',$hotelid);
          
         if(empty($hotel_info)){//该酒楼不存在
             $this->to_back(16200);
         }
         if($hotel_info['flag'] !=0){//该酒楼已删除
             $this->to_back(16201);
         }
         if($hotel_info['state'] !=1){ //该酒楼为非正常酒楼
             $this->to_back(16202);
         }
         if(!in_array($hotel_info['hotel_box_type'], $this->heart_hotel_box_type_arr)){//该酒楼为非网络版酒楼
             $this->to_back(16203);
         }
         $m_box = new \Common\Model\BoxModel();
         $list = $m_box->getBoxListByHotelid('a.id box_id,a.mac as box_mac,a.room_id', $hotelid);   //获取该酒楼下正常机顶盒
         if(empty($list)){//该酒楼下没有正常的机顶盒
             $this->to_back(16204);
         }

         $m_pub_ads_box = new \Common\Model\PubAdsBoxModel(); 
         $redis = new SavorRedis();
         $redis->select(12);
        
         $max_adv_location = C('MAX_ADS_LOCATION_NUMS');
         $now_date = date('Y-m-d H:i:s');
         $data =  array();
         $v_keys = 0;
         $program_ads_menu_num_key = C('PROGRAM_ADS_MENU_NUM');
         $program_ads_menu_num     = $redis->get($program_ads_menu_num_key);
         $program_ads_key = C('PROGRAM_ADS_CACHE_PRE');
         foreach($list as $key=>$v){
             $cache_key = '';
             $cache_key = $program_ads_key.$v['box_id'];
             $cache_value = $redis->get($cache_key);
             $ads_num_arr = array();
             $ads_time_arr = array();
             
             if(empty($cache_value)){
                 for($i=1;$i<=$max_adv_location;$i++){
                     $adv_arr = $m_pub_ads_box->getAdsList($v['box_id'],$i);  //获取当前机顶盒得某一个位置得广告
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
                              
                             $ads_num_arr[] = $ads_arr;
                             $ads_time_arr[] = $av['create_time'];
                             unset($av['pub_ads_id']);
                             unset($av['create_time']);
                             $data[$v_keys]['media_list'][] = $av;
                              
                         }
                     }
                 }
                 if(!empty($ads_num_arr)){//如果该机顶盒下广告位不为空
                      
                     $ads_time_str = max($ads_time_arr);
                      
                     $data[$v_keys]['box_id'] = $v['box_id'];
                     $data[$v_keys]['box_mac'] = $v['box_mac'];
                     $data[$v_keys]['room_id'] = $v['room_id'];
                     //$ads_num = md5(json_encode($ads_num_arr));
                     $box_ads_num = date('YmdHis',strtotime($ads_time_str));
                     //$data[$v_keys]['menu_num'] = $program_ads_menu_num.$box_ads_num;
                     $data[$v_keys]['menu_num'] = $program_ads_menu_num;
                      
                     //$redis_arr['menu_num'] = $box_ads_num;
                     $redis_arr['menu_num'] = $program_ads_menu_num;
                     $redis_arr['ads_list'] = $ads_num_arr;
                     $program_ads_cache_pre = C('PROGRAM_ADS_CACHE_PRE');
                     $cache_key = $program_ads_cache_pre.$v['box_id'];
                     $redis_value = $redis->get($cache_key);
                     if(empty($redis_value)){
                         $redis_value = json_encode($redis_arr);
                         //$redis->set($cache_key, $redis_value,86400);
                         $redis->set($cache_key, $redis_value,14400);
                     }else {
                         $new_redis_str = md5(json_encode($redis_arr));
                         $old_redis_str = md5($redis_value);
                         if($new_redis_str!= $old_redis_str){
                             $redis_value = json_encode($redis_arr);
                             //$redis->set($cache_key, $redis_value,86400);
                             $redis->set($cache_key, $redis_value,14400);
                         }
                     }
                     $v_keys ++;
                 }else {
                     unset($data[$v_keys]);
                 }
             }else {//如果缓存有广告数据
                 $media_list = $media_arr = array();
                 $cache_value = json_decode($cache_value,true); 
                 $media_arr = $cache_value['ads_list'];  
                 
                 foreach($media_arr as $kk=>$vv){
                     $media_list[$kk]['id']          = $vv['id'];
                     $media_list[$kk]['name']        = $vv['name'];
                     $media_list[$kk]['md5']         = $vv['md5'];
                     $media_list[$kk]['md5_type']    = $vv['md5_type'];
                     $media_list[$kk]['chinese_name']= $vv['chinese_name'];
                     $media_list[$kk]['type']        = $vv['type'];
                     $media_list[$kk]['oss_path']    = $vv['oss_path'];
                     $media_list[$kk]['duration']    = $vv['duration'];
                     $media_list[$kk]['suffix']      = $vv['suffix'];
                     $media_list[$kk]['start_date']  = $vv['start_date'];
                     $media_list[$kk]['end_date']    = $vv['end_date'];
                     $media_list[$kk]['location_id'] = $vv['location_id'];
                     $media_list[$kk]['is_sapp_qrcode'] = $vv['is_sapp_qrcode'];
                     $media_list[$kk]['media_type']  = $vv['media_type'] ? $vv['media_type'] : 1;
                 }
                 
                 $data[$v_keys]['media_list'] = $media_list;
                 $data[$v_keys]['box_id']     = $v['box_id'];
                 $data[$v_keys]['box_mac']    = $v['box_mac'];
                 $data[$v_keys]['room_id']    = $v['room_id'];
                 $data[$v_keys]['menu_num']   = $cache_value['menu_num'];
                 
                 $v_keys++;
             }
             
             
         }
         if(empty($data)){
             foreach($list as $key=>$v){
                 $data[$key]['media_list'] = [];
                 $data[$key]['box_id']     = $v['box_id'];
                 $data[$key]['box_mac']    = $v['box_mac'];
                 $data[$key]['room_id']    = $v['room_id'];
                 $data[$key]['menu_num']   = date('YmdHis');
             }
         }
         $this->to_back($data);
     }
     /**
      * @desc  机顶盒广告下载成功回执
      */
     public function updateAdsDownState(){
         $ads_num = $this->params['menu_num'];
         $box_id  = $this->params['box_id'];
         $cache_key =C('PROGRAM_ADS_CACHE_PRE').$box_id;
         //echo $cache_key;exit;
         $redis = new SavorRedis();
         $redis->select(12);
         $ads_arr = $redis->get($cache_key);
         if(!empty($ads_arr)){
             $ads_arr = json_decode($ads_arr,true);
             if($ads_arr['menu_num'] != $ads_num){
                 $this->to_back(16206);
             }
             
             $pubs_ads_arr = array_column($ads_arr['ads_list'], 'pub_ads_id');
             $pubs_ads_arr = array_unique($pubs_ads_arr);
             $m_pub_ads_box = new \Common\Model\PubAdsBoxModel();
             foreach($pubs_ads_arr as $v){
                 $where = array();
                 $where['box_id'] = $box_id;
                 $where['down_state'] = 0;
                 $where['pub_ads_id'] = $v;
                 $data['down_state'] = 1;
                 $data['update_time'] = date('Y-m-d H:i:s');
                 $m_pub_ads_box->updateInfo($where,$data);
             }
             $this->to_back(10000);
         }else {
             $this->to_back(16206);
         }
         
         
         /* $ads_num = $this->params['menu_num'];
         $redis = new SavorRedis();
         $redis->select(12);
         $ads_arr = $redis->get($ads_num);
         if($ads_arr){
             $m_pub_ads_box = new \Common\Model\PubAdsBoxModel();
             $ads_arr = json_decode($ads_arr,true);
             foreach($ads_arr['ads_list'] as $key=>$v){
                 $where = array();
                 $data = array();
                 $where['id'] = $v;
                 $where['down_state'] = 0;
                 $data['down_state'] = 1;
                 $m_pub_ads_box->updateInfo($where,$data);
             }
             $this->to_back(10000);
         }else {
             $this->to_back(16206);
         } */
         
         
         
     }
     /**
      * @desc 节目、宣传片下载成功回执
      */
     public function updateMenuDwonState(){
         $menu_num = $this->params['menu_num'];  //节目单号
         $hotel_id = $this->params['hotel_id'];  //酒楼id
         $menu_type= $this->params['type'];      //节目:pro  宣传片:adv
         
         $m_programmenu_hotel =  new \Common\Model\ProgramMenuHotelModel();
         
         $m_programmenu_list  =  new \Common\Model\ProgramMenuListModel();
         $where = array();
         $where['menu_num'] = $menu_num;
         $where['state']    = 1;
         $menu_info = $m_programmenu_list->getOne('id',$where); 
         if(empty($menu_info)){
             $this->to_back(30115);
         }
         $menu_id = $menu_info['id']; 
         $menu_hotel_info = $m_programmenu_hotel->getMenuHotelDownState('down_state',$menu_id,$hotel_id);
         
         if($menu_hotel_info['down_state'] == $this->menu_type[$menu_type]){
             $this->to_back(16208);
         }
         $where = $data = array();
         $where['menu_id'] = $menu_id;
         $where['hotel_id']= $hotel_id;
         
         if($menu_hotel_info['down_state']==0){//节目和宣传片都没下载成功
             
             $data['down_state'] =$this->menu_type[$menu_type];
             $ret = $m_programmenu_hotel->updateInfo($where,$data);
         }else if($menu_hotel_info['down_state'] ==1 || $menu_hotel_info['down_state'] ==2){
             $data['down_state'] = $this->menu_type['all'];
             $ret = $m_programmenu_hotel->updateInfo($where,$data);
         }else if($menu_hotel_info['down_state'] ==3){
             $this->to_back(16208);
         }
         if($ret){
             $this->to_back(10000);
         }else {
             $this->to_back(16207);
         }
     }
     /**
      * @desc 获取B类全量广告列表
      */
     public function rtbAdsList(){
         $hotel_id = $this->params['hotel_id'];
         $m_rtb_ads = new \Common\Model\PubRtbAdsModel();
         
         $field = "c.id,a.start_date,a.end_date,c.oss_addr AS name,c.md5, 'easyMd5' as `md5_type`,
                   'rtbads' as `type`,c.oss_addr oss_path,c.duration,c.surfix,c.name as chinese_name,a.create_time,a.admaster_sin";
         $where = array();
         $now_date = date('Y-m-d H:i:s');
         $where['h.hotel_id'] = $hotel_id;
         $where['a.flag'] =0;
         //$where['a.start_date'] = array('elt',$now_date);
         $where['a.end_date']   = array('egt',$now_date);
         $order = 'a.create_time desc';

         $data = $m_rtb_ads->getAdsList($field, $where, $order);
         //echo $m_rtb_ads->getLastSql();exit;
         if(empty($data)){
             $this->to_back(10000);
         }
         
         foreach($data as $key=>$val){
             if(!empty($val['name'])){
                 $ttp = explode('/', $val['name']);
                 $data[$key]['name'] = $ttp[2];
             }
             $adsb_arr[] = $val['create_time'];
             unset($data[$key]['create_time']);
         }
         $period = date('YmdHis',strtotime(max($adsb_arr)));
         $result['period'] = $period;
         $result['media_list']   = $data;
         $this->to_back($result);
     }
     /**
      * @desc 接收小平台上传当前最新节目单下载情况
      */
     public function uploadSmallProgramList(){
         $hotel_id     = $this->params['hotel_id'];
         $program_list = $this->params['program_list'];
         $redis = new SavorRedis();
         $redis->select(8);
         $cache_key = C('SMALL_PROGRAM_LIST_KEY').$hotel_id;
         $date = date('Y-m-d H:i:s');
         $program_list = json_decode($program_list,true);
         $program_list['date'] = $date;
         $redis->set($cache_key, json_encode($program_list));
         $this->to_back(10000);
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