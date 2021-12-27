<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class HotelController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'hotellist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'search':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'keywords'=>1001);
                break;
            case 'baseinfo':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;

        }
        parent::_init_();
    }
    public function hotellist(){
        $openid = $this->params['openid'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $permission = json_decode($res_staff['permission'],true);
        $hotel_types = C('HEART_HOTEL_BOX_TYPE');
        $m_hotel = new \Common\Model\HotelModel();
        $where = array('a.state'=>1,'a.flag'=>0,'a.hotel_box_type'=>array('in',array_keys($hotel_types)));
        if($permission['hotel_info']['type']==2){
            $where['a.area_id'] = array('in',$permission['hotel_info']['area_ids']);
        }
        if($permission['hotel_info']['type']==3){
            $where['b.maintainer_id'] = $res_staff['sysuser_id'];
        }
        $fields = 'a.id,a.name,a.pinyin';
        $res_hotels = $m_hotel->getHotelLists($where,'a.pinyin asc','',$fields);
        $all_hotels = array();
        foreach ($res_hotels as $v){
            $letter = substr($v['pinyin'],0,1);
            $letter = strtoupper($letter);
            $all_hotels[$letter][]=array('hotel_id'=>$v['id'],'hotel_name'=>$v['name']);
        }
        $data = array();
        foreach ($all_hotels as $k=>$v){
            $dinfo = array('id'=>ord("$k")-64,'region'=>$k,'items'=>$v);
            $data[]=$dinfo;
        }
        $this->to_back($data);
    }

    public function search(){
        $openid = $this->params['openid'];
        $keywords = trim($this->params['keywords']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $permission = json_decode($res_staff['permission'],true);
        $hotel_types = C('HEART_HOTEL_BOX_TYPE');
        $m_hotel = new \Common\Model\HotelModel();
        $where = array('a.state'=>1,'a.flag'=>0,'a.hotel_box_type'=>array('in',array_keys($hotel_types)));
        if($permission['hotel_info']['type']==2){
            $where['a.area_id'] = array('in',$permission['hotel_info']['area_ids']);
        }
        if($permission['hotel_info']['type']==3){
            $where['b.maintainer_id'] = $res_staff['sysuser_id'];
        }
        $where['a.name'] = array('like',"%$keywords%");
        $fields = 'a.id as hotel_id,a.name as hotel_name';
        $res_hotels = $m_hotel->getHotelLists($where,'a.pinyin asc','',$fields);
        $this->to_back($res_hotels);
    }

    public function baseinfo(){
        $hotel_id = intval($this->params['hotel_id']);
        $m_hotel = new \Common\Model\HotelModel();
        $where = array('hotel.id'=>$hotel_id);
        $field = 'hotel.*,ext.maintainer_id,ext.food_style_id,ext.avg_expense,ext.mac_addr,ext.contract_expiretime,area.region_name as area_name';
        $res_hotel = $m_hotel->getHotelById($field,$where);
        $data = array();
        if(!empty($res_hotel)){
            $area_name = $res_hotel['area_name'];
            if(!empty($res_hotel['county_id'])){
                $m_area = new \Common\Model\AreaModel();
                $res_area = $m_area->getWhere('id,region_name',array('id'=>$res_hotel['county_id']),'','',1);
                $area_name = $area_name.'，'.$res_area['region_name'];
            }
            $food_style_name = '';
            if(!empty($res_hotel['food_style_id'])){
                $m_foodstyle = new \Common\Model\FoodStyleModel();
                $res_foodstyle = $m_foodstyle->getOne('name',array('id'=>$res_hotel['food_style_id']),'');
                $food_style_name = $res_foodstyle['name'];
            }
            $avg_expense = '';
            if(!empty($res_hotel['avg_expense'])){
                $avg_expense = $res_hotel['avg_expense'].'/人';
            }
            $contract_expiretime = $res_hotel['contract_expiretime'];
            if(empty($contract_expiretime)){
                $contract_expiretime = '';
            }
            $m_opuser = new \Common\Model\OpuserRoleModel();
            $res_user = $m_opuser->getList('a.mobile,user.remark',array('a.user_id'=>$res_hotel['maintainer_id']),'','');
            $maintainer = $maintainer_mobile = '';
            if(!empty($res_user)){
                $maintainer = $res_user[0]['remark'];
                $maintainer_mobile = $res_user[0]['mobile'];
            }
            $install_date = '';
            if(!empty($res_hotel['install_date'])){
                $install_date = $res_hotel['install_date'];
            }
            $small_platform_num = 0;
            if($res_hotel['mac_addr']!='000000000000'){
                $small_platform_num = 1;
            }
            $all_hotel_box_types = C('HOTEL_BOX_TYPE');
            $m_box = new \Common\Model\BoxModel();
            $fields='box.box_type,count(box.id) as num';
            $where = array('hotel.id'=>$hotel_id,'box.state'=>1,'box.flag'=>0);
            $res_box = $m_box->getBoxByCondition($fields,$where,'box.box_type');
            $box_nums = array();
            $box_info = '';
            $tv_num = 0;
            $box_num = 0;
            foreach ($res_box as $v){
                $box_nums[$v['box_type']] = array('num'=>$v['num'],'type_name'=>$all_hotel_box_types[$v['box_type']]);
                $box_info.=$all_hotel_box_types[$v['box_type']].':'.$v['num'].',';
                if($v['box_type']==7){
                    $tv_num = $v['num'];
                }else{
                    $box_num = $box_num+$v['num'];
                }
            }
            if(count($box_nums)>1){
                $box_type = '混合';
                $box_info = rtrim($box_info,',');
            }else{
                $box_type = rtrim($box_info,',');
                $box_info = '';
            }
            $data = array('hotel_id'=>$res_hotel['id'],'hotel_name'=>$res_hotel['name'],'area_name'=>$area_name,'address'=>$res_hotel['addr'],
                'contractor'=>$res_hotel['contractor'],'mobile'=>$res_hotel['mobile'],'food_style_name'=>$food_style_name,'avg_expense'=>$avg_expense,
                'contract_expiretime'=>$contract_expiretime,'hotel_wifi'=>$res_hotel['hotel_wifi'],'hotel_wifi_pas'=>$res_hotel['hotel_wifi_pas'],
                'maintainer'=>$maintainer,'maintainer_mobile'=>$maintainer_mobile,'hotel_box_type'=>$all_hotel_box_types[$res_hotel['hotel_box_type']],
                'install_date'=>$install_date,'small_platform_num'=>$small_platform_num,'tv_num'=>$tv_num,'box_num'=>$box_num,'box_type'=>$box_type,'box_info'=>$box_info

            );
        }
        $this->to_back($data);
    }

    public function detail(){
        $hotel_id = intval($this->params['hotel_id']);
        $m_hotel = new \Common\Model\HotelModel();
        $where = array('hotel.id'=>$hotel_id);
        $field = 'hotel.*,ext.maintainer_id,ext.mac_addr';
        $res_hotel = $m_hotel->getHotelById($field,$where);
        $data = array();
        if(!empty($res_hotel)){
            $small_platform_num = 0;
            if($res_hotel['mac_addr']!='000000000000'){
                $small_platform_num = 1;
            }
            $all_hotel_box_types = C('HOTEL_BOX_TYPE');
            $m_box = new \Common\Model\BoxModel();
            $fields='box.box_type,count(box.id) as num';
            $where = array('hotel.id'=>$hotel_id,'box.state'=>1,'box.flag'=>0);
            $res_box = $m_box->getBoxByCondition($fields,$where,'box.box_type');
            $box_nums = array();
            $box_info = '';
            $tv_num = 0;
            $box_num = 0;
            foreach ($res_box as $v){
                $box_nums[$v['box_type']] = array('num'=>$v['num'],'type_name'=>$all_hotel_box_types[$v['box_type']]);
                $box_info.=$all_hotel_box_types[$v['box_type']].':'.$v['num'].',';
                if($v['box_type']==7){
                    $tv_num = $v['num'];
                }else{
                    $box_num = $box_num+$v['num'];
                }
            }
            if(count($box_nums)>1){
                $box_type = '混合';
                $box_info = rtrim($box_info,',');
            }else{
                $box_type = rtrim($box_info,',');
                $box_info = '';
            }

            $m_opuser = new \Common\Model\OpuserRoleModel();
            $res_user = $m_opuser->getList('a.mobile,user.remark',array('a.user_id'=>$res_hotel['maintainer_id']),'','');
            $maintainer = $maintainer_mobile = '';
            if(!empty($res_user)){
                $maintainer = $res_user[0]['remark'];
                $maintainer_mobile = $res_user[0]['mobile'];
            }
            $hotel_network = '';
            if($res_hotel['is_4g']==1){
                $hotel_network='4G';
            }elseif($res_hotel['is_5g']==1){
                $hotel_network='5G';
            }
            $now_time = time();
            $online_time = 900;
            $boot24_time = 86400;
            $day7_time = 7*86400;
            $day30_time = 30*86400;

            $redis = new \Common\Lib\SavorRedis();
            $redis->select(13);
            $small_platform_status=$small_platform_uptips='';
            if($res_hotel['mac_addr']!='000000000000'){
                $cache_key  = 'heartbeat:1:'.$res_hotel['mac_addr'];
                $res_cache = $redis->get($cache_key);
                $small_platform_status='black';
                if(!empty($res_cache)){
                    $platform_info = json_decode($res_cache,true);
                    $report_time = strtotime($platform_info['date']);
                    $diff_time = $now_time - $report_time;
                    if($diff_time<=$online_time){
                        $small_platform_status='green';
                    }elseif($diff_time<=$boot24_time){
                        $small_platform_status='blue';
                    }elseif($diff_time>$boot24_time && $diff_time<=$day7_time){
                        $small_platform_status='yellow';
                    }elseif($diff_time>$day7_time && $diff_time<$day30_time){
                        $small_platform_status='red';
                    }else{
                        $small_platform_status='black';
                    }
                    $m_device_upgrade = new \Common\Model\DeviceUpgradeModel();
                    $up_info  = $m_device_upgrade->getLastSmallPtInfo($hotel_id);
                    if($up_info['version']!=$platform_info['war']){
                        $small_platform_uptips='war待升级';
                    }
                }
            }
            $m_device_update = new \Common\Model\DeviceUpgradeModel();
            $apk_update_info = $m_device_update->getNewSmallApkInfo($hotel_id,'',2);

            $m_ads = new \Common\Model\AdsModel();
            $adv_proid_info = $m_ads->getWhere(array('hotel_id'=>$hotel_id,'type'=>3),'max(update_time) as max_update_time');
            if($adv_proid_info[0]['max_update_time']){
                $adv_proid = date('YmdHis',strtotime($adv_proid_info[0]['max_update_time']));
            }else{
                $adv_proid = '20190101000000';
            }
            //获取最新节目单
            $m_new_menu_hotel = new \Common\Model\ProgramMenuHotelModel();
            $menu_info = $m_new_menu_hotel->getLatestMenuid($hotel_id);   //获取最新的一期节目单
            $menu_num= $menu_info['menu_num'];
            $all_hotel_box_types = C('HOTEL_BOX_TYPE');
            $m_sdkerror = new \Common\Model\SdkErrorModel();
            $fields='box.id as box_id,box.mac,box.name as box_name,box.box_type';
            $where = array('hotel.id'=>$hotel_id,'box.state'=>1,'box.flag'=>0);
            $res_box = $m_box->getBoxByCondition($fields,$where);
            //$ads_proid = '';
            foreach ($res_box as $k=>$v){
                //获取机顶盒的广告期号
                if($res_hotel['mac_addr'] =='000000000000'){//虚拟小平台
                    $redis->select(10);
                    $cache_key = 'vsmall:ads:'.$hotel_id.":".$v['mac'];
                    $cache_info = $redis->get($cache_key);
                    $ads_info = json_decode($cache_info,true);
                    if(!empty($ads_info['media_lib'])){
                        $ads_proid = $ads_info['menu_num'];
                    }else{
                        $ads_proid = '';
                    }
                }else { //实体小平台
                    $redis->select(12);
                    $program_ads_key = C('PROGRAM_ADS_CACHE_PRE');
                    $cache_key = $program_ads_key.$v['box_id'];
                    $cache_value = $redis->get($cache_key);
                    $ads_info = json_decode($cache_value,true);
                    /* if(!empty($ads_info['menu_num'])){
                        $ads_proid = $ads_info['menu_num'];
                    } */
                    $ads_proid = $ads_info['menu_num'];
                    
                }
                if(empty($ads_proid)){
                    $m_pub_ads_box = new \Common\Model\PubAdsBoxModel(); 
                    $max_adv_location = C('MAX_ADS_LOCATION_NUMS');
                    $now_date = date('Y-m-d H:i:s');
                    $ads_num_arr = array();
                    $ads_time_arr = array();
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
                                $ads_arr['create_time'] = $av['create_time'];
                                $ads_num_arr[] = $ads_arr;
                                $ads_time_arr[] = $av['create_time'];
                                unset($av['pub_ads_id']);
                                unset($av['create_time']);
                                
                            }
                        }
                    }
                    if(!empty($ads_num_arr)){//如果该机顶盒下广告位不为空
                        $ads_time_str = max($ads_time_arr);
                        $ads_proid = date('YmdHis',strtotime($ads_time_str));
                    }
                }
                
                $redis->select(13);
                $cache_key  = 'heartbeat:2:'.$v['mac'];
                $res_cache = $redis->get($cache_key);
                $box_status='black';
                $box_uptips='';
                $heart_time = 0;
                if(!empty($res_cache)){
                    $cache_data = json_decode($res_cache,true);
                    $report_time = strtotime($cache_data['date']);
                    $heart_time = $report_time;
                    $diff_time = $now_time - $report_time;
                    if($diff_time<=$online_time){
                        $box_status='green';
                    }elseif($diff_time<=$boot24_time){
                        $box_status='blue';
                    }elseif($diff_time>$boot24_time && $diff_time<=$day7_time){
                        $box_status='yellow';
                    }elseif($diff_time>$day7_time && $diff_time<$day30_time){
                        $box_status='red';
                    }else{
                        $box_status='black';
                    }
                    if($adv_proid.$menu_num!=$cache_data['adv_period'] || $menu_num!=$cache_data['pro_period'] || ( !empty($ads_proid) && $ads_proid!=$cache_data['period']) ){
                        $box_uptips='资源待更新';
                    }
                    if($apk_update_info['version_name']!=$cache_data['apk']){
                        $box_uptips='apk待升级';
                    }
                }else{
                    $box_uptips='资源待更新';
                }
                //机顶盒内存判断
                $ram_status='gray';
                $res_sdkerror = $m_sdkerror->getInfo('*',array('box_id'=>$v['box_id']));
                if(!empty($res_sdkerror) && $res_sdkerror['full_report_date']>$res_sdkerror['clean_report_date']){
                    $ram_status='red';
                }
                $res_box[$k]['ram_status']=$ram_status;
                $res_box[$k]['status']=$box_status;
                $res_box[$k]['uptips']=$box_uptips;
                $res_box[$k]['box_type_str']=$all_hotel_box_types[$v['box_type']];
                $res_box[$k]['heart_time']=$heart_time;
            }
            sortArrByOneField($res_box,'heart_time',true);
            $desc = array('粉色标签为酒楼网络类型，棕色为酒楼设备类型；','底色说明：','1.灰底色代表机顶盒内存正常','2.红底色代表机顶盒内存异常',
                '状态说明：','绿色圆点：在线','蓝色圆点：24小时内在线','黄色圆点：24小时以上，7天以内在线','红色圆点：失联7天以上','黑色圆点：失联30天以上'
            );
            $data = array('hotel_id'=>$res_hotel['id'],'hotel_name'=>$res_hotel['name'],'address'=>$res_hotel['addr'],
                'contractor'=>$res_hotel['contractor'],'mobile'=>$res_hotel['mobile'],'maintainer'=>$maintainer,'maintainer_mobile'=>$maintainer_mobile,
                'hotel_network'=>$hotel_network,'hotel_box_type'=>$all_hotel_box_types[$res_hotel['hotel_box_type']],
                'small_platform_status'=>$small_platform_status,'small_platform_uptips'=>$small_platform_uptips,'box_list'=>$res_box,
                'up_time'=>date('Y-m-d H:i:s'),'desc'=>$desc,'small_platform_num'=>$small_platform_num,'tv_num'=>$tv_num,
                'box_num'=>$box_num,'box_type'=>$box_type,'box_info'=>$box_info
            );
        }
        $this->to_back($data);
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
}