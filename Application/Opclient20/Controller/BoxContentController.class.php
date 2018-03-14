<?php
/**
 * @desc 版位信息2.1.1
 * @author zhang.yingtao
 * @since  2018-03-12
 */
namespace Opclient20\Controller;
use Think\Controller;
use \Common\Lib\SavorRedis;
use \Common\Controller\BaseController as BaseController;
class BoxContentController extends BaseController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'contentDetail':
                $this->is_verify = 1;
                $this->valid_fields = array('box_id'=>1001);
                break;
            
            
        }
        parent::_init_();
    }
/**
     * @desc 内容详情
     */
    public function contentDetail(){
        $box_id = $this->params['box_id'];
        $m_box = new \Common\Model\BoxModel();
        $where = array();
        $where['a.id'] = $box_id;
        $where['a.flag'] = 0;
        //$where['a.state'] = 1;
        $box_info = $m_box->getBoxInfo('a.id box_id,a.name,a.mac,c.name room_name,d.id hotel_id',$where);
        $box_info = $box_info[0];
        if(empty($box_info)){
            $this->to_back(70001);
        }
        
        $data = array();
        $data['room_name']= $box_info['room_name'];
        $data['box_name'] = $box_info['name'];
        $data['box_mac']  = $box_info['mac'];
        
        $m_oss_box_log = new \Common\Model\OSS\OssBoxModel();
        $rets = $m_oss_box_log->getLastTime($data['box_mac']);
        if(!empty($rets)){
           $data['log_upload_time'] =  $rets[0]['lastma'];
        }else {
            $data['log_upload_time'] = '无';
        }
        
        //获取机顶盒维修记录
        $redMo = new \Common\Model\RepairBoxUserModel();
        $field = 'sys.remark nickname, date_format(sru.create_time,"%m-%d  %H:%i") ctime ';
        $co['mac'] = $box_info['mac'];
        $rinfo = $redMo->getRepairUserInfo($field, $co);
        if (empty($rinfo)) {
            $data['repair_record'] = array();
        } else {
            $data['repair_record'] = $rinfo;
        }
       
        $redis = SavorRedis::getInstance();
        $redis->select(13);
        $key = "heartbeat:"."2:".$box_info['mac']; 
        $box_heart_info = $redis->get($key);
        $box_heart_info = json_decode($box_heart_info,true);
        //print_r($box_heart_info);exit;
        $box_heart_info['last_heart_time'] = date('Y-m-d H:i:s',strtotime($box_heart_info['date'])); 
        
        if(empty($box_heart_info)){
            $data['loss_hours'] = '失联30天以上';
        }else {
            $data['last_heart_time'] = $box_heart_info['last_heart_time'];
            $diff_time = time() - strtotime($box_heart_info['last_heart_time']);
            
            $diff_hours = $diff_time/3600;
            $heart_loss_hours = C('HEART_LOSS_HOURS');
            
            if($diff_hours>$heart_loss_hours){
                $data['loss_hours'] = '失联'.floor($diff_hours).'小时';
            }else {
                $data['loss_hours'] = '正常';
            }
        }
        
        
        $hotel_id = $box_info['hotel_id'];
        //节目状态
        $pro_same_flag = 0;
        $m_new_menu_hotel = new \Common\Model\ProgramMenuHotelModel();
        $ads = new \Common\Model\AdsModel();
        //获取最新节目单
        $menu_info = $m_new_menu_hotel->getLatestMenuid($hotel_id);   //获取最新的一期节目单
        if(!empty($menu_info)){
            $program_menu_num = $menu_info['menu_num'];
            
            if($program_menu_num == $box_heart_info['pro_period']){//节目单号与上报一致
                $data['pro_period_state'] = '已更新到最新';
                $pro_same_flag = 1;
            }else {//节目单号与上报不一致
                $data['pro_period_state'] = '版本不是最新';
                $pro_same_flag = 0;
            }  
            if($box_heart_info['pro_download_period'] && $box_heart_info['pro_download_period'] !=$box_heart_info['pro_period'] ){
                $data['pro_download_period'] = $box_heart_info['pro_download_period'];
            }else {
                $data['pro_download_period'] = '';
            }
            
        }
        //广告状态
        //获取该机顶盒最新广告
        $ads_same_flag = 0;
        $box_ads_arr = $this->getBoxAdsList($box_id);
        if($box_ads_arr){
            $pub_ads_peroid = $box_ads_arr['box_ads_num'];  //发布的广告期号
            if($pub_ads_peroid == $box_heart_info['period']){
                $data['ads_period_state'] = '已更新到最新';
                $ads_same_flag = 1;
            }else {
                $data['ads_period_state'] = '版本不是最新';
                $ads_same_flag = 0;
            }
            
        }
        if($box_heart_info['ads_download_period'] && ($box_heart_info['ads_download_period'] !=$box_heart_info['period'])){
            $data['ads_download_period'] = $box_heart_info['ads_download_period'];
        }else {
            $data['ads_download_period'] = '';
        }
        //当前播放列表
        $data['pro_period'] = $box_heart_info['pro_period'] ? $box_heart_info['pro_period'] : '';  //当前节目期号
        $data['ads_period'] = $box_heart_info['period'] ? $box_heart_info['period'] : '';  //当前广告期号
        
        $program_list = array();
        $redis->select(14);
        $cache_key = 'box:'.'play:'.$box_info['mac'];
        
        $box_program = $redis->get($cache_key);
        $box_program = json_decode($box_program,true);
        
        $box_program_list  = $box_program['list'];
        /*$box_media_arr = array();
         foreach($box_program_list as $key=>$v){
            $box_media_arr[] = $v['media_id'];
        } */
        
        //获取后台最新节目单
        $menu_info = $m_new_menu_hotel->getLatestMenuid($box_info['hotel_id']);   //获取最新的一期节目单

        $menu_id = $menu_info['menu_id'];
        $menu_num= $menu_info['menu_num'];
        $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
        $menu_item_arr = $m_program_menu_item->getMenuInfo($menu_id); //获取节目单的节目列表
        $menu_item_arr = $this->changeadvList($menu_item_arr);
        
        //获取后台最新宣传片
        $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
        $adv_arr = $m_program_menu_item->getadvInfo($box_info['hotel_id'], $menu_id);
        
        //获取后台最新广告
        $m_pub_ads_box = new \Common\Model\PubAdsBoxModel();
        $fields = 'd.id';
        $ads_item_arr = $m_pub_ads_box->getBoxAdsList($fields,$box_id,'','a.pub_ads_id');
        //print_r($menu_item_arr);exit;
        //print_r($ads_item_arr);exit;
        //print_r($adv_arr);exit;
        //echo $m_pub_ads_box->getLastSql();exit;
        $bag_media_arr = array();
        foreach($menu_item_arr as $v){
            $bag_media_arr[] = $v['id'];
        }
        foreach($adv_arr as $v){
            $bag_media_arr[] = $v['id'];
        }
        foreach($ads_item_arr as $v){
            $bag_media_arr[] = $v['id'];
        }
        $program_diff_arr = array();
        $m_media = new \Common\Model\MediaModel();
        foreach($box_program_list as $key=>$v){
            switch ($v['type']){
                case 'pro':
                    $type ='节目';
                    break;
                case 'adv':
                    $type ='宣传片';
                    break;
                case 'ads':
                    $type ='广告';
                    break;
            }
            $dts = $m_media->getWhere(array('id'=>$v['media_id']),'name');
            $program_diff_arr[$key]['name'] = $dts[0]['name'];
            $program_diff_arr[$key]['type'] =$type;
            if(!in_array($v['media_id'], $bag_media_arr)){
                $program_diff_arr[$key]['flag'] = 0;
            }else {
                $program_diff_arr[$key]['flag'] = 1;
            }
            
        }
        $data['program_list'] = $program_diff_arr;
        
        
        
        
        
        
        
        
        $this->to_back($data);
    }
    /**
     * @desc 获取机顶盒最新广告列表
     */
    private function getBoxAdsList($box_id){
        $m_pub_ads_box = new \Common\Model\PubAdsBoxModel();
        $max_adv_location = C('MAX_ADS_LOCATION_NUMS');
        $now_date = date('Y-m-d H:i:s');
        $data =  array();
        //$v_keys = 0;
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
                     
                    $ads_arr['pub_ads_id']  = $av['pub_ads_id'];
                    $ads_arr['create_time'] = $av['create_time'];
                    $ads_arr['location_id'] = $av['location_id'];
                    $ads_num_arr[] = $ads_arr;
                    $ads_time_arr[] = $av['create_time'];
    
                    $tmp = array();
                    $tmp['name'] = $av['chinese_name'];
                    $tmp['type'] = 1;
                    $tmp['location_id'] = $av['location_id'];
                    $data['media_list'][$av['location_id']] = $tmp;
                     
                }
            }
        }
        if(!empty($ads_num_arr)){//如果该机顶盒下广告位不为空
             
            $ads_time_str = max($ads_time_arr);
            $box_ads_num = date('YmdHis',strtotime($ads_time_str));
            $data['box_ads_num'] = $box_ads_num;
        }
        return $data;
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