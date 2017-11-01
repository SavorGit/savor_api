<?php
namespace Small\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;
class ProgramController extends CommonController{
    /**
     * 构造函数
     */
    var $menu_type;
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
        }
        $this->menu_type = array('pro'=>1,'adv'=>2,'all'=>3);
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
        if(!in_array($hotel_info['hotel_box_type'], array(2,3))){//该酒楼为非网络版酒楼 
            $this->to_back(16203);   
        }
        $m_box = new \Common\Model\BoxModel();
        $list = $m_box->getBoxListByHotelid('a.id boxId,a.mac', $hotelid);   //获取该酒楼下正常包间下正常机顶盒
        
        if(empty($list)){//该酒楼下没有正常的机顶盒
            $this->to_back(16204);
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
        
        $data = array();
        foreach($list as $key=>$v){
            $data[$key]['box_id']    = $v['boxId'];              //机顶盒id 
            $data[$key]['box_mac']   = $v['mac'];
            $data[$key]['pub_time'] = $menu_info['pub_time'];
            $data[$key]['menu_num'] = $menu_num;
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
         if(!in_array($hotel_info['hotel_box_type'], array(2,3))){//该酒楼为非网络版酒楼
             $this->to_back(16203);
         }
         $m_box = new \Common\Model\BoxModel();
         $list = $m_box->getBoxListByHotelid('a.id boxId,a.mac', $hotelid);   //获取该酒楼下正常包间下正常机顶盒
         
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
         
         $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
         $adv_arr = $m_program_menu_item->getadvInfo($hotelid, $menu_id);
         //if(!empty($adv_arr)){
             
             foreach($adv_arr as $key=>$v){
                 $redis_arr[] = $v['id'];
             }
             
             
             
             $data = array();
             $adv_arr = $this->changeadvList($adv_arr,2);
             
             if(empty($redis_arr)){
                 $adv_num = md5($menu_num);
             }else {
                 $adv_num = md5(json_encode($redis_arr).$menu_num);
             }
             foreach($list as $key=>$v){
                 $data[$key]['box_id']    = $v['boxId'];              //机顶盒id
                 $data[$key]['box_mac']   = $v['mac'];
                 $data[$key]['menu_num'] = $adv_num;                 //宣传片号
                 $data[$key]['program_num'] = $menu_num;              //节目单号
                 $data[$key]['media_list'] = $adv_arr;
             }
             $this->to_back($data);
         //}else {
             $this->to_back(10000);
         //}
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
         if(!in_array($hotel_info['hotel_box_type'], array(2,3))){//该酒楼为非网络版酒楼
             $this->to_back(16203);
         }
         $m_box = new \Common\Model\BoxModel();
         $list = $m_box->getBoxListByHotelid('a.id box_id,a.mac as box_mac', $hotelid);   //获取该酒楼下正常包间下正常机顶盒
          
         if(empty($list)){//该酒楼下没有正常的机顶盒
             $this->to_back(16204);
         }
         
         $m_pub_ads_box = new \Common\Model\PubAdsBoxModel(); 
         $redis = new SavorRedis();
         $redis->select(12);
        
         $max_adv_location = 10;
         $now_date = date('Y-m-d H:i:s');
         $ttmp = $data =  array();
         foreach($list as $key=>$v){
             $ads_num_arr = array();
             for($i=1;$i<=10;$i++){
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
                         $ads_num_arr[] = $av['pab_id'];
                         unset($av['pab_id']);
                         //$ttmp = $this->changeadvList($av);
                         $ttmp[$key]['media_list'][] = $av;
                         //$list[$key]['media_list'][] = $av;
                     }
                     //$adv_arr = $this->changeadvList($adv_arr);
                     //$list[$key]['adv_list'][$i] = $adv_arr;
                 } 
             }
             
             if(!empty($ads_num_arr)){
                 $ttmp[$key]['box_id'] = $v['box_id'];
                 $ttmp[$key]['box_mac'] = $v['box_mac'];
                 $ads_num = md5(json_encode($ads_num_arr));
                 $ttmp[$key]['menu_num'] = $ads_num;

                 $redis_arr =array();
                 $redis_arr['box_id'] = $v['box_id'];
                 $redis_arr['ads_list'] = $ads_num_arr;
                 $cache_key = $ads_num;
                 $redis_value = $redis->get($cache_key);
                 if(empty($redis_value)){
                     $redis_value = json_encode($redis_arr);
                     $redis->set($cache_key, $redis_value,2592000);
                 }
                
             }else {
                 unset($ttmp[$key]);
             }
             
         }
         foreach($ttmp as $key=>$v){
             $data[] = $v;
         }
         //$data = $ttmp;
         
         $this->to_back($data);
     }
     /**
      * @desc  机顶盒广告下载成功回执
      */
     public function updateAdsDownState(){
         $ads_num = $this->params['menu_num'];
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
         }
         
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