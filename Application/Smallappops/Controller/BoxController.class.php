<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController;
use Common\Lib\Smallapp_api;
use Common\Lib\SavorRedis;
class BoxController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getBaseInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('box_id'=>1001);
                break;
            case 'cleanResource':
                $this->is_verify = 1;
                $this->valid_fields = array('box_id'=>1001);
                break;
            case 'updateApk':
                $this->is_verify = 1;
                $this->valid_fields = array('box_id'=>1001);
                break;
        }
        parent::_init_();
    }
    public function getBaseInfo(){
        $data      = [];
        $baseinfo  = [];
        $stateinfo = [];
        $mediaupdateinfo = [];
        
        //基本信息
        $box_id = $this->params['box_id'];
        $box_type_arr = C('HOTEL_BOX_TYPE');
        $redis = SavorRedis::getInstance();
        $redis->select(15);
        $cache_key   = 'savor_box_'.$box_id;
        $cache_info  =$redis->get($cache_key);
        $box_info = json_decode($cache_info,true);
        
        $cache_key  = 'savor_room_'.$box_info['room_id'];
        $cache_info = $redis->get($cache_key);
        $room_info  = json_decode($cache_info,true);
        
        $baseinfo['box_name']  = $box_info['name'];
        $baseinfo['room_name'] = $room_info['name'];
        $baseinfo['box_mac']   = $box_info['mac'];
        
        $redis->select(13);
        $cache_key = 'heartbeat:2:'.$box_info['mac'];
        $cache_info = $redis->get($cache_key);
        $heart_info = json_decode($cache_info,true);
        if(!empty($heart_info)){
            $baseinfo['intranet_ip'] = $heart_info['intranet_ip'];
            $baseinfo['outside_ip']  = $heart_info['outside_ip'];
            
        }else {
            $baseinfo['intranet_ip'] = '';
            $baseinfo['outside_ip']  = '';
        }
        
        $baseinfo['is_4g']       = $box_info['is_4g']?'4G':'非4G';
        $baseinfo['box_type']    = $box_type_arr[$box_info['box_type']];
        $baseinfo['switch_time'] = $box_info['switch_time'];
        $baseinfo['wifi_name']   = $box_info['wifi_name'];
        $baseinfo['wifi_password']   = !empty($box_info['wifi_password']) ? $box_info['wifi_password'] :'无密码'; 
        $baseinfo['now_apk_version'] = !empty($heart_info['apk']) ?$heart_info['apk']:'';
        
        $m_device_update = new \Common\Model\DeviceUpgradeModel();
        $apk_update_info = $m_device_update->getNewSmallApkInfo($room_info['hotel_id'],'',2);
        $baseinfo['last_apk_version'] = $apk_update_info['version_name'];
        //设备状态
        $stateinfo['heart_time'] = date('Y-m-d H:i:s',strtotime($heart_info['date']));
        $m_oss_box_log = new \Common\Model\OSS\OssBoxModel();
        $rets = $m_oss_box_log->getLastTime($box_info['mac']);
        if(!empty($rets)){
            $stateinfo['log_upload_time'] =  $rets[0]['lastma']; //最后上传日志时间
        }else {
            $stateinfo['log_upload_time'] = '无';                //最后上传日志时间
        }
        //netty是否在线
        $netty_position_stime = getMillisecond();
        $req_id = getMillisecond();
        $netty_data = array('box_mac'=>$box_info['mac'],'req_id'=>$req_id);
        $post_data = http_build_query($netty_data);
        $nettyBalanceURL = C('NETTY_BALANCE_URL');
        
        $result = $this->curlPost($nettyBalanceURL, $post_data);
        $netty_position_etime = getMillisecond();
        $position_result = json_decode($result,true);
        if($position_result['code']==10000){
            $stateinfo['smallplatform'] = '在线';
        }else {
            $stateinfo['smallplatform'] = '异常('.$position_result['msg'].')';
        }
        $memeryinfo = '内存正常';
        $memery_status = 1;
        $m_sdkerror = new \Common\Model\SdkErrorModel();
        $res_sdkerror = $m_sdkerror->getInfo('*',array('box_id'=>$box_id));
        if(!empty($res_sdkerror) && $res_sdkerror['full_report_date']>$res_sdkerror['clean_report_date']){
            $memeryinfo = '内存已满';
            $memery_status = 2;
        }
        $stateinfo['memeryinfo'] = $memeryinfo;
        $stateinfo['memery_status'] = $memery_status;
        //资源更新
        
        //节目状态
        $m_new_menu_hotel = new \Common\Model\ProgramMenuHotelModel();
        //获取最新节目单
        $menu_info = $m_new_menu_hotel->getLatestMenuid($room_info['hotel_id']);   //获取最新的一期节目单
        
        if(!empty($menu_info)){
            $program_menu_num = $menu_info['menu_num'];
        }else {
            $program_menu_num = '';
        }
        $mediaupdateinfo['last_pro_period'] = $program_menu_num;
        $mediaupdateinfo['now_pro_period']  = !empty($heart_info['pro_period']) ? $heart_info['pro_period']:'';
        
        //广告
        $box_ads_arr = $this->getBoxAdsList($box_id);
        if($box_ads_arr){
            $pub_ads_peroid = $box_ads_arr['box_ads_num'];  //发布的广告期号
        }else{
            $pub_ads_peroid = '';
        }
        $mediaupdateinfo['last_ads_period'] = $pub_ads_peroid;
        $mediaupdateinfo['now_ads_period']  = !empty($heart_info['period']) ? $heart_info['period'] :'';
        //宣传片
        //宣传片状态
        //获取当前机顶盒宣传片列表
        $adv_same_flag = 0;
        $box_adv_arr = $this->getBoxAdvList($room_info['hotel_id']);
        if($box_adv_arr){
            $pub_adv_peroid = $box_adv_arr['box_adv_num'].$program_menu_num;
            
        }else {
            $pub_adv_peroid = '';
        }
        $mediaupdateinfo['last_adv_period'] = $pub_adv_peroid;
        $mediaupdateinfo['now_adv_period']  = !empty($heart_info['adv_period']) ? $heart_info['adv_period']:'';
            
         
        $data['baseinfo'] = $baseinfo;
        $data['stateinfo']= $stateinfo;
        $data['mediaupdateinfo'] = $mediaupdateinfo;
        
        $this->to_back($data);
    }

    public function cleanResource(){
        $box_id = $this->params['box_id'];
        $redis = SavorRedis::getInstance();
        $redis->select(15);
        $cache_key   = 'savor_box_'.$box_id;
        $cache_info  =$redis->get($cache_key);
        $box_info = json_decode($cache_info,true);

        $m_netty = new \Common\Model\NettyModel();
        $all_types = array('1'=>'当前正在下载的一期视频内容','2'=>'正在播放的广告数据','3'=>'生日歌');
        $is_error = 0;
        foreach ($all_types as $k=>$v){
            $message = array('action'=>998,'type'=>$k);
            $res_netty = $m_netty->pushBox($box_info['mac'],json_encode($message));
            if(isset($res_netty['error_code'])){
                $is_error=1;
                break;
            }
        }
        if($is_error){
            $this->to_back(94003);
        }
        $m_sdkerror = new \Common\Model\SdkErrorModel();
        $sql ="update `savor_sdk_error` set clean_report_date='".date('Y-m-d H:i:s')."' where box_id=".$box_id.' limit 1';
        $m_sdkerror->execute($sql);
        $this->to_back(array());
    }
    public function updateApk(){
        $box_id = $this->params['box_id'];
        /*$redis = SavorRedis::getInstance();
        $redis->select(15);
        $cache_key   = 'savor_box_'.$box_id;
        $cache_info  =$redis->get($cache_key);
        $box_info = json_decode($cache_info,true); */
        
        $m_box = new \Common\Model\BoxModel();
        $field = "box.id,box.device_token,box.adv_mach,hotel.id hotel_id,room.id room_id";
        $where = " box.id=$box_id";
        $box_info  = $m_box->getBoxByCondition($field,$where);
        $box_info  = $box_info[0];
        $hotel_id = $box_info['hotel_id'];
        
        
        if(empty($box_info['device_token'])){
            $this->to_back(70005); 
        }
        
        if(empty($box_info['adv_mach'])){//非广告机
            //获取当前机顶盒的最新apk
            
            //$m_upgrade = new \Admin\Model\UpgradeModel();
            $m_upgrade = new \Common\Model\DeviceUpgradeModel();
            $field = 'sdv.oss_addr,md5';
            $device_type = 2;
            $data = $m_upgrade->getLastOneByDeviceNew($field, $device_type, $hotel_id);
            
        }else {//广告机
            $m_device_version = new \Common\Model\DeviceVersionModel();
            $data = $m_device_version->field('oss_addr,md5')->where('device_type=21')->order('id desc')->find();
            
        }
        $apk_url = 'http://'.C('OSS_HOST').'/'.$data['oss_addr'];
        $apk_md5 = $data['md5'];
        
        
        $display_type = 'notification';
        $option_name = 'boxclient';
        $after_a = C('AFTER_APP');
        $after_open = $after_a[3];
        $device_token = $box_info['device_token'];
        $ticker = 'apk升级推送';
        $title  = 'apk升级推送';
        $text   = 'apk升级推送';
        $production_mode = C('UMENG_PRODUCTION_MODE');
        $custom = array();
        $custom['type'] = 4;  //1:RTB  2:4G投屏 3:shell命令推送  4：apk升级
        $custom['action'] = 1; //1:投屏  0:结束投屏
        $custom['data'] = array('apkUrl'=>$apk_url,'apkMd5'=>$apk_md5);
        
        $m_pushlog = new \Common\Model\PushLogModel();
        $m_pushlog->uPushData($display_type, 3,'listcast',$option_name, $after_open, $device_token,
            $ticker,$title,$text,$production_mode,$custom);
        $this->to_back(10000);
        
    }

    /**
     * @desc 获取机顶盒最新广告列表
     */
    private function getBoxAdsList($box_id){
        $data =  array();
        $m_box = new \Common\Model\BoxModel();
        $fileds = 'ext.mac_addr  ';
        $box_info = $m_box->alias('a')
        ->join('savor_room room on a.room_id=room.id','left')
        ->join('savor_hotel hotel on room.hotel_id=hotel.id','left')
        ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
        ->where('a.id='.$box_id)
        ->find();
        if($box_info['mac_addr']=='000000000000'){
            $m_pub_ads_box = new \Common\Model\PubAdsBoxModel();
            
            $pub_ads_list = $m_pub_ads_box->getVsmallAdsList($box_id);
            $ads_period_info = $m_pub_ads_box->getVsmallBoxPorid($box_id);
            foreach($pub_ads_list as $key=>$v){
                $tmp = array();
                $tmp['name'] = $v['chinese_name'];
                $tmp['type'] = 1;
                $tmp['location_id'] = $v['location_id'];
                $data['media_list'][$v['location_id']] = $tmp;
            }
            if(!empty($ads_period_info)){//如果该机顶盒下广告位不为空
                
                
                $box_ads_num = date('YmdHis',strtotime($ads_period_info['create_time']));
                $data['box_ads_num'] = $box_ads_num;
            }
        }else {
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
            //return $data;
        }
        
        return $data;
        
    }
    /**
     * @desc 获取当前机顶盒的宣传片列表
     */
    private function getBoxAdvList($hotel_id){
        $m_new_menu_hotel = new \Common\Model\ProgramMenuHotelModel();
        //获取最新节目单
        $menu_info = $m_new_menu_hotel->getLatestMenuid($hotel_id);   //获取最新的一期节目单
        
        $data = array();
        if(empty($menu_info)){//该酒楼未设置节目单
            return $data;
        }
        $menu_id = $menu_info['menu_id'];
        $menu_num= $menu_info['menu_num'];
        $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
        $adv_arr = $m_program_menu_item->getadvInfo($hotel_id, $menu_id);
        
        foreach($adv_arr as $key=>$v){
            $temp =array();
            $tmp['name'] = $v['chinese_name'];
            $tmp['type'] = 3;
            $tmp['sort_num'] = $v['sortNum'];
            $data['media_list'][$v['sortNum']] = $tmp;
        }
        
        $m_ads = new \Common\Model\AdsModel();
        $adv_proid_info = $m_ads->getWhere(array('hotel_id'=>$hotel_id,'type'=>3),'max(update_time) as max_update_time');
        
        if(!empty($adv_proid_info[0]['max_update_time'])){
            $adv_proid = date('YmdHis',strtotime($adv_proid_info[0]['max_update_time']));
        }else {
            $adv_proid = '20190101000000';
        }
        
        $data['box_adv_num'] = $adv_proid;
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
    private function curlPost($url = '',  $post_data = ''){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded",
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return 0;
        } else {
            
            return $response;
        }
    }
}