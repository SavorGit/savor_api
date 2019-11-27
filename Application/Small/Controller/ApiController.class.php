<?php
/**
 * Project savor_api
 *
 * @author baiyutao <------@gmail.com> 2017-5-16
 */
namespace Small\Controller;

use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;

/**
 * Class ApiController
 * 云平台PHP接口
 * @package Small\Controller
 */
class ApiController extends CommonController{
    var $upgrade_type_arr ;
 	/**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {

            case 'getDownloadList':
                $this->is_verify = 1;
                $this->valid_fields=array('hotelid'=>'1001','type'=>'1001',);
                
                break;
            case 'getHotel':
                $this->is_verify = 1;
                $this->valid_fields=array('hotelid'=>'1001');

                break;
            case 'getHotelvb':
                $this->is_verify = 1;
                $this->valid_fields=array('hotelid'=>'1001');

                break;
            case 'getHotelRoom':
                $this->is_verify = 1;
                $this->valid_fields=array('hotelid'=>'1001');

                break;
            case 'getHotelBox':
                $this->is_verify = 1;
                $this->valid_fields=array('hotelid'=>'1001');

                break;
            case 'getHotelTv':
                $this->is_verify = 1;
                $this->valid_fields=array('hotelid'=>'1001');

                break;
            case 'smallPlatform':
                $this->is_verify = 1;
                $this->valid_fields = array('areaId'=>'1001','hotelId'=>'1001','remark'=>'1001','smallIp'=>'1001');
                break;
            case 'getUpgradeVersion':
                $this->is_verify = 1;
                $this->valid_fields = array('hotelId'=>'1001','type'=>'1001');
                break;
            case 'getDeviceSql':
                $this->is_verify = 0;
                $this->valid_fields = array('curVersion'=>'1000','downloadVersion'=>'1000');
                break;
            case 'reportStaTime':
                $this->is_verify = 1;
                $this->valid_fields = array('statistics_time'=>'1001');
                break;
            case 'getAllBoxList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>'1001');
                break;
        }
        $this->upgrade_type_arr = array('wwar'=>1,'apk'=>2);
        parent::_init_();
    }

    public function reportStaTime(){
        $time = $this->params['statistics_time']; //hotelid
        if(  ctype_digit($time)
            && strlen($time) >= 10
            && $time <= 2147483647
        ) {

        } else {
            $this->to_back(16209);
        }
        $redis = SavorRedis::getInstance();
        $redis->select(13);
        $key = 'statistics_hotel_time';
        $bool = $redis->set($key, $time);
        if($bool) {
            $this->to_back(10000);
        } else {
            $this->to_back(16210);
        }

    }

    public function getHotelTv(){
        $hotelModel = new \Common\Model\HotelModel();
        $boxModel = new \Common\Model\BoxModel();
        $sysconfigModel = new \Common\Model\SysConfigModel();
        $tvModel = new \Common\Model\TvModel();
        $hotelid = $this->params['hotelid']; //hotelid
        $where = array();
        $result = array();
        $res = array();
        if(!is_numeric($hotelid)){
            $this->to_back(10007);
        }
        $count = $hotelModel->where(array('id'=>$hotelid))->count();
        if($count == 0){
            $this->to_back(10007);
        }
        $boxs = $hotelModel->getStatisticalNumByHotelId($hotelid,'box');
        $redis = SavorRedis::getInstance();
        $field = " id as tv_id,tv_brand as tv_Brand,tv_size,tv_source,box_id, flag,state";
        if($boxs['box_num']){
            $redis->select(12);
            $cache_key = C('SMALL_TV_LIST').$hotelid;
            $redis_value = $redis->get($cache_key);
            
            if(!empty($redis_value)){
                $res = json_decode($redis_value,true);
            }else {
                $box_str = join(',', $boxs['box']);
                $where['box_id'] = array('IN',"$box_str");
                $res = $tvModel->getList($where, $field);
                $redis->set($cache_key, json_encode($res),7200);
            }
            
        }
        
        if($res){
            foreach ($res as $vk=>$val) {
                foreach($val as $rk=>$rv){
                    if(is_numeric($rv)){
                        $res[$vk][$rk] = intval($rv);
                    }
                }
            }
        }
        $this->to_back($res);
    }

    public function getHotelBox(){
        $hotelModel = new \Common\Model\HotelModel();
        $boxModel = new \Common\Model\BoxModel();
        $sysconfigModel = new \Common\Model\SysConfigModel();
        $hotelid = $this->params['hotelid']; //hotelid
        $data = array();
        if(!is_numeric($hotelid)){
            $this->to_back(10007);
        }
        $count = $hotelModel->where(array('id'=>$hotelid))->count();
        if($count == 0){
            $this->to_back(10007);
        }
        $redis = SavorRedis::getInstance();
        $redis->select(12);
        $cache_key = C('SMALL_BOX_LIST').$hotelid;
        $redis_box_list = $redis->get($cache_key);
        if(!empty($redis_box_list)){
            $box_arr = json_decode($redis_box_list,true);    
        }else {
            $field = "  box.id AS box_id,box.room_id,box.name as box_name,
                        room.hotel_id,box.mac as box_mac,box.state,box.flag,
                        box.switch_time,box.volum as volume ";
            $box_arr = $boxModel->getInfoByHotelid($hotelid, $field);
            
            $redis->set($cache_key, json_encode($box_arr),7200);
        }
        $cache_key = C('SYSTEM_CONFIG');
        $redis_sys_config = $redis->get($cache_key);
        if(!empty($redis_sys_config)){
            $redis_sys_config = json_decode($redis_sys_config,true);
            foreach($redis_sys_config as $key=>$v){
                if(in_array($v['config_key'], array('system_ad_volume','system_switch_time'))){
                    $sys_arr[] = $v;
                }
            }
        }else {
            $where = " 'system_ad_volume','system_switch_time'";
            $sys_arr = $sysconfigModel->getInfo($where);
        }
        if(!empty($box_arr)){
            $data = $this->changeBoxList($box_arr, $sys_arr);
        }
        $this->to_back($data);
    }

    public function getHotelRoom(){
        $hotelModel = new \Common\Model\HotelModel();
        $romModel = new \Common\Model\RoomModel();
        $hotelid = $this->params['hotelid']; //hotelid
        if(!is_numeric($hotelid)){
            $this->to_back(10007);
        }
        $count = $hotelModel->where(array('id'=>$hotelid))->count();
        if($count == 0){
            $this->to_back(10007);
        }
        $redis = SavorRedis::getInstance();
        $redis->select(12);
        
        $cache_key = C('SMALL_ROOM_LIST').$hotelid;
        $redis_value = $redis->get($cache_key);
        if(empty($redis_value)){
            $field = "  id AS room_id,name as room_name,hotel_id,type as room_type,
                    state,flag,remark,create_time,update_time,probe ";
            $map['hotel_id'] = $hotelid;
            $room_arr = $romModel->getWhere($map, $field);
            if(!empty($room_arr)){
                $room_arr =  $this->changeroomList($room_arr);
                $redis->set($cache_key, json_encode($room_arr),7200);
            }
        }else {
            $room_arr = json_decode($redis_value,true);
        }
        $this->to_back($room_arr);
    }


    public function getHotelvb(){
        $hotelModel = new \Common\Model\HotelModel();
        $sysconfigModel = new \Common\Model\SysConfigModel();
        $hotelid = $this->params['hotelid']; //hotelid
        if(!is_numeric($hotelid)){
            $this->to_back(10007);
        }
        $count = $hotelModel->where(array('id'=>$hotelid))->count();
        if($count == 0){
            $this->to_back(10007);
        }
        $redis = SavorRedis::getInstance();
        $redis->select(12);
        $cache_key = C('SMALL_HOTEL_INFO').$hotelid;
        $hotel_info = $redis->get($cache_key);
        if(empty($hotel_info)){
            $ho_arr = $hotelModel->getHotelMacInfo($hotelid);
            $ho_arr = $ho_arr[0];
        }else {
            $ho_arr = json_decode($hotel_info,true);
        }
        $cache_key = C('SYSTEM_CONFIG');
        $redis_sys_config = $redis->get($cache_key);
        
        if(!empty($redis_sys_config)){
            $redis_sys_config = json_decode($redis_sys_config,true);
            foreach($redis_sys_config as $key=>$v){
                if(in_array($v['config_key'], array('system_ad_volume','system_pro_screen_volume','system_demand_video_volume','system_tv_volume'))){
                    $sys_vol_arr [] = $v;
                }
            }
        }else {
            $where = " 'system_ad_volume','system_pro_screen_volume','system_demand_video_volume','system_tv_volume' ";
            $sys_vol_arr = $sysconfigModel->getInfo($where);
        }
        $sys_vol_arr = $this->changesysconfigList($sys_vol_arr);
        $bootvideo[0]['label'] = '机顶盒开机视频地址';
        $bootvideo[0]['configKey'] = 'boot_video_url_for_set_top_box';
        $bootvideo[0]['configValue'] = 'http://oss.littlehotspot.com/media/resource/ntfrQRRH2M.mp4';
        //$bootvideo[0]['configValue'] = 'af6066f8b89b3290276b4ff87a93d265';
        $bootvideo[1]['label'] ='机顶盒开机视频地址MD5';
        $bootvideo[1]['configKey'] = 'boot_video_md5_for_set_top_box';
        $bootvideo[1]['configValue'] = 'af6066f8b89b3290276b4ff87a93d265';
        $sys_vol_arr = array_merge($sys_vol_arr,$bootvideo);
        $data = array();
        $data= $ho_arr;
        //print_r($data);exit;
        $data['install_date'] = $data['install_date'].' 00:00:00';
        $data['hotel_id'] = intval($data['hotel_id']);
        $data['area_id'] = intval($data['area_id']);
        $data['key_point'] = intval($data['key_point']);
        $data['state'] = intval($data['state']);
        $data['state_reason'] = intval($data['state_reason']);
        $data['flag'] = intval($data['flag']);
        $data['hotel_box_type'] = intval($data['hotel_box_type']);
        
        $tmp = json_encode($sys_vol_arr,JSON_UNESCAPED_UNICODE);
        $data['sys_config_json']= $tmp;
        $this->to_back($data);
    }



    public function getHotel(){
        $hotelModel = new \Common\Model\HotelModel();
        $hotelid = $this->params['hotelid']; //hotelid
        if(!is_numeric($hotelid)){
            $this->to_back(10007);
        }
        $count = $hotelModel->where(array('id'=>$hotelid))->count();
        if($count == 0){
            $this->to_back(10007);
        }
        $redis = SavorRedis::getInstance();
        $redis->select(12);
        $cache_key = C('SMALL_HOTEL_INFO').$hotelid;
        $redis_value = $redis->get($cache_key);
        if(empty($redis_value)){
            $ho_arr = $hotelModel->getHotelMacInfo($hotelid);
            $data = array();
            $data= $ho_arr[0];
            $redis->set($cache_key, json_encode($data),86400);   
        }else {
            $data = json_decode($redis_value,true);
        }
        $data['hotel_id'] = intval($data['hotel_id']);
        $data['area_id'] = intval($data['area_id']);
        $data['key_point'] = intval($data['key_point']);
        $data['state'] = intval($data['state']);
        $data['state_reason'] = intval($data['state_reason']);
        $data['flag'] = intval($data['flag']);
        $data['hotel_box_type'] = intval($data['hotel_box_type']);
        $this->to_back($data);
    }

    /**
     * getDownloadList 获取文件下载来源
     * @access public
     */
    public function getDownloadList(){
        //'DOWNLOAD_HOTEL_INFO_TYPE'=>array('ads'=>1,'adv'=>2,'pro'=>3,'vod'=>4,'logo'=>5,'load'=>6)
        $hotelModel = new \Common\Model\HotelModel();
        $hotelid = $this->params['hotelid']; //hotelid
        $type = $this->params['type'];    //类型：
        $hotel_info_type_arr = C('DOWNLOAD_HOTEL_INFO_TYPE');  //下载来源数组
        if(!is_numeric($hotelid)){
            $this->to_back(10007);
        }
        $count = $hotelModel->getHotelCount(array('id'=>$hotelid));
        if($count == 0){
            $this->to_back(10007);
        }
        //判断酒店id是否存在

        if(!array_key_exists($type, $hotel_info_type_arr)){
            $this->to_back(16001);
        }
        $d_type = $hotel_info_type_arr[$type];

        switch ($d_type) {
            case 1:
                //广告
               $dap = $this->getadsData($hotelid);
                break;
                //宣传片
            case 2:
                $dap = $this->getadvData($hotelid);
                break;
                //节目
            case 3:
                $dap = $this->getproData($hotelid);
                break;
                //手机点播
            case 4:
                $dap =  $this->getvodData($hotelid);
                break;
                //logo数据
            case 5:
                $dap =  $this->getlogoData($hotelid);
                break;
                //loading图l
            case 6:
                $dap =  $this->getloadData($hotelid);
                break;
            default:
                break;

        }
        if(!empty($dap) && is_array($dap)){
            foreach($dap as $dk=>$dv){
                if(is_string($dv)) {
                   if (isset($dap['menu_hotel_id'])) {
                       $dap['menu_hotel_id'] = intval($dap['menu_hotel_id']);
                   }
                } else if(is_array($dv)) {
                    foreach($dv as $rk=>$rv){
                        if (isset($rv['id'])) {
                            $dap[$dk][$rk]['id'] = intval($rv['id']);
                        }
                        if (isset($rv['duration'])) {
                            $dap[$dk][$rk]['duration'] = intval($rv['duration']);
                        }
                        if (isset($rv['order'])) {
                            $dap[$dk][$rk]['order'] = intval($rv['order']);
                        }
                    }
                }
            }
        }
        $this->to_back($dap);
    }


    /**
     * getadsData 获取酒楼广告类型数据
     * @access public
     * @param $hotelid 酒楼id
     * @return array
     */
    private function getadsData($hotelid){
        $menuhotelModel = new \Common\Model\MenuHotelModel();
        $adsModel = new \Common\Model\AdsModel();
        $per_arr = $menuhotelModel->getadsPeriod($hotelid);
        if(empty($per_arr)){
            $this->to_back(16205);
        }
        $menuid = $per_arr[0]['menuId'];
        $ads_arr = $adsModel->getadsInfo($menuid);
        $ads_arr = $this->changeadsList($ads_arr);
        $data = array();
        $data['period'] = $per_arr[0]['period'];
        $data['pub_time'] = $per_arr[0]['pubTime'];
        $data['menu_hotel_id'] = $per_arr[0]['menuHotelId'];
        $data['media_list'] = $ads_arr;
        return $data;

    }

    /**
     * getadvData 获取酒楼宣传片类型数据
     * @access public
     * @param $hotelid 酒楼id
     * @return array
     */
    private function getadvData($hotelid){
        $menuhotelModel = new \Common\Model\MenuHotelModel();
        $adsModel = new \Common\Model\AdsModel();
        //获取广告期号
        $per_arr = $menuhotelModel->getadsPeriod($hotelid);
        if(empty($per_arr)){
            $this->to_back(16205);
        }
        $menuid = $per_arr[0]['menuId'];
        $adv_arr = $adsModel->getadvInfo($hotelid, $menuid);
        $adv_arr = $this->changeadvList($adv_arr);
        $data = array();
        $data['period'] = $per_arr[0]['period'];
        $data['pub_time'] = $per_arr[0]['pubTime'];
        $data['menu_hotel_id'] = $per_arr[0]['menuHotelId'];
        $data['media_list'] = $adv_arr;
        return $data;

    }


    /**
     * getproData 获取酒楼节目类型数据
     * @access public
     * @param $hotelid 酒楼id
     * @return array
     */
    private function getproData($hotelid){
        $menuhotelModel = new \Common\Model\MenuHotelModel();
        $adsModel = new \Common\Model\AdsModel();
        //获取广告期号
        $per_arr = $menuhotelModel->getadsPeriod($hotelid);
        if(empty($per_arr)){
            $this->to_back(16205);
        }
        $menuid = $per_arr[0]['menuId'];
        $pro_arr = $adsModel->getproInfo($menuid);
        $pro_arr = $this->changeadvList($pro_arr);
        $data = array();
        $data['period'] = $per_arr[0]['period'];
        $data['pub_time'] = $per_arr[0]['pubTime'];
        $data['menu_hotel_id'] = $per_arr[0]['menuHotelId'];
        $data['media_list'] = $pro_arr;
        return $data;

    }


    /**
     * getvodData 获取酒楼手机点播
     * @access public
     * @param $hotelid 酒楼id
     * @return array
     */
    private function getvodData($hotelid){
        $mbperModel = new \Common\Model\MbPeriodModel();
        $mbhomeModel = new \Common\Model\HomeModel();
        //获取广告期号
        $field = " period ";
        $order = 'update_time desc';
        $where = ' 1=1 ';
        $start = 1;
        $vod_per_arr = $mbperModel->getOneInfo($field, $where,$order,  $start);
        $version = $vod_per_arr[0]['period'];
        $ver_arr = $mbhomeModel->getvodInfo();
        $ver_arr = $this->changevodList($ver_arr, $version);
        $data = array();
        $data['period'] = $version;
        $data['media_list'] = $ver_arr;
        return $data;

    }



    /**
     * getloadData 获取酒楼手机loading图
     * @access public
     * @param $hotelid 酒楼id
     * @return array
     */
    private function getloadData($hotelid){
        $sysconfigModel = new \Common\Model\SysConfigModel();
        $load_arr = $sysconfigModel->getloadInfo($hotelid);
        $load_arr = $this->changeadvList($load_arr);
        $data = array();
        $data['period'] = $load_arr[0]['version'];
        $data['media_list'] = $load_arr;
        return $data;
    }



    /**
     * getlogoData 获取酒楼手机logo
     * @access public
     * @param $hotelid 酒楼id
     * @return array
     */
    private function getlogoData($hotelid){
        $hotelModel = new \Common\Model\HotelModel();
        $logo_arr = $hotelModel->gethotellogoInfo($hotelid);
        $logo_arr = $this->changeadvList($logo_arr);
        $data = array();
        $data['period'] = $logo_arr[0]['version'];
        $data['media_list'] = $logo_arr;
        return $data;
    }







    /**
     * changeroomList  将已经数组修改字段名称
     * @access public
     * @param $res
     * @return array
     */
    private function changeroomList($res){
        $ro_type = C('ROOM_TYPE');

        if($res){
            foreach ($res as $vk=>$val) {
                foreach($ro_type as $k=>$v){
                    if($k == $val['room_type']){
                        $res[$vk]['room_type']  = $v;                                   }
                }
                foreach($val as $rk=>$rv){
                    if($rk!='room_name'){
                        if(is_numeric($res[$vk][$rk])){
                            $res[$vk][$rk] = intval($rv);
                        }
                    }
                    
                    if($res[$vk][$rk] === null){
                        $res[$vk][$rk] = '';
                    }
                }

            }

        }

        return $res;
        //如果是空
    }



    /**
     * changeadvList  将已经数组修改字段名称
     * @access public
     * @param $res
     * @return array
     */
    private function changesysconfigList($res){
        $vol_arr = C('CONFIG_VOLUME');
        $vol_default = C('CONFIG_VOLUME_VAL');
        if($res){
            foreach ($res as $vk=>$val) {
                $rc_key =  $val['config_key'];
                foreach($vol_arr as $k=>$v){
                    if($k == $rc_key){
                        $res[$vk]['label']  = $v;                                   }
                }
                $res[$vk]['configKey'] =  $res[$vk]['config_key'];
                $res[$vk]['configValue'] =  ($res[$vk]['config_value']==='')
                ?$vol_default[$rc_key]:$res[$vk]['config_value'];
                unset($res[$vk]['config_key']);
                unset($res[$vk]['config_value']);
                unset($res[$vk]['status']);
            }

        }
        return $res;
        //如果是空
    }

    /**
     * changeadvList  将已经数组修改字段名称
     * @access public
     * @param $res
     * @return array
     */
    private function changeadvList($res){
        if($res){
            foreach ($res as $vk=>$val) {
                $res[$vk]['order'] =  $res[$vk]['sortNum'];
                unset($res[$vk]['sortNum']);
                if(!empty($val['name'])){
                    $ttp = explode('/', $val['name']);
                    $res[$vk]['name'] = $ttp[2];
                }
            }

        }
        return $res;
        //如果是空
    }

    /**
     * changevodList  将已经数组修改字段名称
     * @access public
     * @param $res
     * @return array
     */
    private function changevodList($res,$version){
        if($res){
            foreach ($res as $vk=>$val) {
                $res[$vk]['order'] =  $res[$vk]['sortNum'];
                $res[$vk]['version'] =  $version;
                unset($res[$vk]['sortNum']);
                if(!empty($val['name'])){
                    $ttp = explode('/', $val['name']);
                    $res[$vk]['name'] = $ttp[2];
                }

            }

        }
        return $res;
        //如果是空
    }



    /**
     * changeBoxList  将已经数组修改字段名称
     * @access public
     * @param $res 机顶盒数组
     * * @param $sys_arr 系统数组
     * @return array
     */
    private function changeBoxList($res, $sys_arr){        $da = array();
        $vol = C('CONFIG_VOLUME_VAL');
        $da = array();

        foreach ($sys_arr as $vk=>$val) {
            if($val['config_key'] == 'system_ad_volume') {
                if( $val['config_value'] === '' ) {
                    $da['volume'] = '';
                } else {
                    $da['volume'] = intval($val['config_value']);
                }
            }
            if($val['config_key'] == 'system_switch_time') {
                if( $val['config_value'] === '' ) {
                    $da['switch_time'] = $vol['system_switch_time'];
                } else {
                    $da['switch_time'] = intval($val['config_value']);
                }
            }
        }
        if(!array_key_exists('switch_time', $da)) {
            $da['switch_time'] = -8;
        }

        if($res){
            foreach ($res as $vk=>$val) {
                $stime = $val['switch_time'];
                $res[$vk]['volume'] = empty($da['volume'])? $val['volume']   : $da['volume'];
                $res[$vk]['switch_time'] = $da['switch_time']<0?(($stime==='')?$vol['system_switch_time']:$stime):$da['switch_time'];

                foreach($val as $rk=>$rv){
                    if($rk != 'switch_time' && $rk != 'volume') {
                        if($rv === null){
                            $res[$vk][$rk] = '';
                        }
                    } else {
                        continue;
                    }
                }
            }
        }


        return $res;
        //如果是空
    }
    public function smallPlatform(){
        $ip_addr = get_client_ipaddr();              //获取外网IP
        $areaId  = intval($this->params['areaId']);  //区域id
        $hotelId = intval($this->params['hotelId']); //酒楼id
        $remark  = $this->params['remark'];          //备注
        $smallIp = $this->params['smallIp'];         //小平台ip
    
        //检测酒楼是否存在且正常
        $m_hotel = new \Common\Model\HotelModel();
        $hotel_info = $m_hotel->getInfoById($hotelId,'id');
        if(empty($hotel_info)){
            $this->to_back('16100');   //该酒楼不存在或被删除
        }
        $m_small_platform = new \Common\Model\SmallPlatformModel();
        $data = array();
        $data['hotel_id'] = $hotelId;
        $data['hotel_ip'] = $ip_addr;
        $data['area_id']  = $areaId;
        $data['small_ip'] = $smallIp;
        $data['state']    =1;
        $data['remark']   = $remark;
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');
        //print_r($data);exit;
        $ret = $m_small_platform->addInfo($data);
        if($ret){
            $this->to_back('10000');
        }else {
            $this->to_back('16101');
        }
    }
    public function getUpgradeVersion(){
        $versionCode = $this->params['versionCode'];
        $hotelid = intval($this->params['hotelId']);
        $type = $this->params['type'];
        if(!key_exists($type, $this->upgrade_type_arr)){
            $this->to_back('16102');
        }
        $device_type = $this->upgrade_type_arr[$type];
        $data =  array();
        $m_device_upgrade = new \Common\Model\DeviceUpgradeModel();
        if(empty($versionCode)){
            //如果versioncode没有，那就返回savor_device_upgrade表里面满足hotelId和type的最新一条数据对应的版本信息
            $upgrade_info = $m_device_upgrade->getLastSmallPtInfo($hotelid,'',$device_type);
        }else {
            //如果versioncode不为空  根据酒楼id检测最新一条 是否在  min  和max之间
            $upgrade_info = $m_device_upgrade->getLastSmallPtInfo($hotelid,$versionCode,$device_type);
        }
        if(!empty($upgrade_info)){
            $m_device_version = new \Common\Model\DeviceVersionModel();
            
            $device_version_info = $m_device_version->getOneByVersionAndDevice($upgrade_info['version'],$device_type);
            //print_r($device_version_info);exit;
            if(!empty($device_version_info)){
                $result['period'] = $device_version_info['version_code'];
                
                $data['id'] = intval($device_version_info['id']);
                $ttp = explode('/', $device_version_info['oss_addr']);
                $data['name']     = $ttp[2];
                $data['md5'] = $device_version_info['md5'];
                $data['md5_type'] = 'fullMd5';
                $data['version']  = $device_version_info['version_code'];
                $upgrade_type_arr = array_flip($this->upgrade_type_arr) ;
    
                $data['type'] = $upgrade_type_arr[1];
                $data['oss_path'] = $device_version_info['oss_addr'];
                $data['duration'] = intval($upgrade_info['update_type']);
                $data['suffix']   = getExt($device_version_info['oss_addr']);
                $data['order']    = 0;   //排序默认值0
                $data['chinese_name']= $device_version_info['version_name'];
                $result['media_list'][] = $data;
                $this->to_back($result);
            }else {
                $this->to_back('16104');
            }
        }else {
            $this->to_back('16103');
        }
    }
    /**
     * @desc 获取升级sql
     */
    public function getDeviceSql(){
        $curVersion = $this->params['curVersion'];              //小平台当前版本号
        $downloadVersion = $this->params['downloadVersion'];    //下载版本号
        if(empty($curVersion) && empty($downloadVersion)){
            $this->to_back(array());
        }
        $m_device_sql = new \Common\Model\DeviceSqlModel();
        //$upgrade_sql_list = $m_device_sql->getUpgradeSql($curVersion, $downloadVersion,$type = 1);
        $upgrade_sql_list = $m_device_sql->getUpgradeSqlFf($curVersion, $downloadVersion,$type = 1);
        
        if(!empty($upgrade_sql_list)){
            $data = array();
            foreach($upgrade_sql_list as $key=>$v){
                $data[$key]['sql_lang'] = $v['sql_lang'];
                $data[$key]['version_name'] = $v['version_name'];
                $data[$key]['version_code'] = $v['version_code'];
            }
            $this->to_back($data);
        }else {
            $this->to_back('16105');
        }
    }
    /**
     * @desc 获取酒楼下所有的机顶盒
     */
    public function getAllBoxList(){
        $hotel_id = $this->params['hotel_id'];
        $redis = SavorRedis::getInstance();
        $redis->select(12);
        $cache_key = C('HOTEL_BOX_STATE_LIST').$hotel_id;
        $data = $redis->get($cache_key);
        $box_list = array();
        if(empty($data)){
            $m_box = new \Common\Model\BoxModel();
            $where = array();
            $where['d.id'] = $hotel_id;
            $box_list = $m_box->getBoxInfo('a.id box_id,a.mac box_mac,a.state,a.flag', $where);
            if(!empty($box_list)){
                $redis->set($cache_key, json_encode($box_list),7200);
            }
        }else {
            $box_list = json_decode($data,true);
        }
        $this->to_back($box_list);
        
    }
    
    
    public function changeadsList($res){
        if($res){
            foreach ($res as $vk=>$val) {
                if(!empty($val['name'])){
                    $ttp = explode('/', $val['name']);
                    $res[$vk]['name'] = $ttp[2];
                }
            }

        }
        return $res;
        //如果是空
    }
}