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
                $this->valid_fields = array('openid'=>1001,'type'=>1002);
                break;
            case 'search':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'keywords'=>1001,'source'=>1002);
                break;
            case 'baseinfo':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
            case 'editbaseinfo':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'name'=>1001,'area_id'=>1001,'county_id'=>1001,
                    'business_circle_id'=>1002,'addr'=>1001,'contractor'=>1001,'mobile'=>1002,'food_style_id'=>1001,
                    'avg_expense'=>1002,'contract_expiretime'=>1002,'hotel_wifi'=>1002,'hotel_wifi_pas'=>1002
                    );
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
            case 'nearby':
                $this->is_verify = 1;
                $this->valid_fields = array('latitude'=>1001,'longitude'=>1001,'openid'=>1002);
                break;
            case 'addhoteldrinks':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'is_nosell'=>1001,'images'=>1002);
                break;
            case 'hoteldrinkslist':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
            case 'stockgoodslist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                break;
            case 'stockidcodelist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'goods_id'=>1001,'page'=>1001);
                break;
            case 'getsignprogress':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                break;

        }
        parent::_init_();
    }
    public function hotellist(){
        $openid = $this->params['openid'];
        $type = $this->params['type'];//1全部 2个人

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
        if(!empty($type) && $permission['hotel_info']['type']==4){
            if($type==1){
                $where['a.area_id'] = array('in',$permission['hotel_info']['area_ids']);
            }elseif($type==2){
                $where['b.maintainer_id'] = $res_staff['sysuser_id'];
            }
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
        if($type==2 && in_array($permission['hotel_info']['type'],array('1','2'))){
            $data = array();
        }
        $this->to_back($data);
    }

    public function search(){
        $openid = $this->params['openid'];
        $keywords = trim($this->params['keywords']);
        $source = intval($this->params['source']);//来源1统计数据酒楼搜索 2添加联系人酒楼搜索

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        if($source==2){
            $where = array('a.state'=>array('in','1,4'),'a.flag'=>0);
        }else{
            $permission = json_decode($res_staff['permission'],true);
            $hotel_types = C('HEART_HOTEL_BOX_TYPE');
            $where = array('a.state'=>1,'a.flag'=>0,'a.hotel_box_type'=>array('in',array_keys($hotel_types)));
            if($permission['hotel_info']['type']==2){
                $where['a.area_id'] = array('in',$permission['hotel_info']['area_ids']);
            }
            if($permission['hotel_info']['type']==3){
                $where['b.maintainer_id'] = $res_staff['sysuser_id'];
            }
        }
        $where['a.name'] = array('like',"%$keywords%");
        $fields = 'a.id as hotel_id,a.name as hotel_name';
        $m_hotel = new \Common\Model\HotelModel();
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
            $business_circle_name = '';
            if(!empty($res_hotel['business_circle_id'])){
                $m_business_circle = new \Common\Model\BusinessCircleModel();
                $res_circle = $m_business_circle->getInfo(array('id'=>$res_hotel['business_circle_id']));
                $business_circle_name = $res_circle['name'];
            }
            $avg_expense_num = intval($res_hotel['avg_expense']);
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
            $data = array('hotel_id'=>$res_hotel['id'],'hotel_name'=>$res_hotel['name'],'area_name'=>$area_name,'business_circle_name'=>$business_circle_name,'address'=>$res_hotel['addr'],
                'contractor'=>$res_hotel['contractor'],'mobile'=>$res_hotel['mobile'],'food_style_name'=>$food_style_name,'avg_expense'=>$avg_expense,
                'contract_expiretime'=>$contract_expiretime,'hotel_wifi'=>$res_hotel['hotel_wifi'],'hotel_wifi_pas'=>$res_hotel['hotel_wifi_pas'],
                'maintainer'=>$maintainer,'maintainer_mobile'=>$maintainer_mobile,'hotel_box_type'=>$all_hotel_box_types[$res_hotel['hotel_box_type']],
                'install_date'=>$install_date,'small_platform_num'=>$small_platform_num,'tv_num'=>$tv_num,'box_num'=>$box_num,'box_type'=>$box_type,'box_info'=>$box_info,
                'area_id'=>$res_hotel['area_id'],'county_id'=>$res_hotel['county_id'],'food_style_id'=>$res_hotel['food_style_id'],
                'business_circle_id'=>$res_hotel['business_circle_id'],'avg_expense_num'=>$avg_expense_num,'now_date'=>date('Y-m-d')
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
            if($res_hotel['mac_addr']!='000000000000' && $res_hotel['type']!=4){
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
            $room_num = 0;
            $room_fields='count(room.id) as room_num';
            $m_room = new \Common\Model\RoomModel();
            $res_rooms = $m_room->getRoomByCondition($room_fields,array('hotel.id'=>$hotel_id,'room.flag'=>0));
            if(!empty($res_rooms)){
                $room_num = intval($res_rooms[0]['room_num']);
            }

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
            $adv_proid_info = $m_ads->getWhere(array('hotel_id'=>$hotel_id,'type'=>3,'state'=>1),'max(update_time) as max_update_time');
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
            $where = array('hotel.id'=>$hotel_id,'box.state'=>1,'box.flag'=>0,'room.is_device'=>1);
            $res_box = $m_box->getBoxByCondition($fields,$where);
            $ads_proid = '';
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
                        //$ads_proid = date('YmdHis',strtotime($ads_time_str));
                        $redis->select(12);
                        $program_ads_menu_num_key = C('PROGRAM_ADS_MENU_NUM');
                        $program_ads_menu_num = $redis->get($program_ads_menu_num_key);
                        $ads_proid = $program_ads_menu_num;
                        
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
                $res_box[$k]['server_adv_period']=$adv_proid.$menu_num;
                $res_box[$k]['server_pro_period']=$menu_num;
                $res_box[$k]['server_ads_proid']=$ads_proid;
            }
            sortArrByOneField($res_box,'heart_time',true);
            $desc = array('粉色标签为酒楼网络类型，棕色为酒楼设备类型；','底色说明：','1.灰底色代表机顶盒内存正常','2.红底色代表机顶盒内存异常',
                '状态说明：','绿色圆点：在线','蓝色圆点：24小时内在线','黄色圆点：24小时以上，7天以内在线','红色圆点：失联7天以上','黑色圆点：失联30天以上'
            );
            $m_hotel_drinks = new \Common\Model\HotelDrinksModel();
            $hwhere = array('hotel_id'=>$hotel_id,'type'=>2);
            $res_drinks = $m_hotel_drinks->getALLDataList('image,add_time',$hwhere,'id desc','0,1','');
            $hotel_drinks_content = '无';
            $hotel_drinks_num = 0;
            if(!empty($res_drinks)){
                if(!empty($res_drinks[0]['image'])){
                    $hwhere['image'] = array('neq','');
                    $res_num = $m_hotel_drinks->getALLDataList('count(*) as num',$hwhere,'id desc','0,1','');
                    $hotel_drinks_num = intval($res_num[0]['num']);
                    $hotel_drinks_content = '';
                }
            }
            $stock_num = 0;
            $redis->select(9);
            $key = C('FINANCE_HOTELSTOCK').':'.$hotel_id;
            $res_cache = $redis->get($key);
            if(!empty($res_cache)) {
                $hotel_stock = json_decode($res_cache, true);
                $stock_goods = array();
                foreach ($hotel_stock['goods_list'] as $v){
                    $stock_goods[$v['id']] = $v;
                }
                if(!empty($hotel_stock['goods_ids'])){
                    $fields = 'g.id,g.name,g.price,g.advright_media_id,g.cover_imgs,g.line_price,g.type,g.finance_goods_id';
                    $where = array('h.hotel_id'=>$hotel_id,'g.type'=>43,'g.status'=>1);
                    $where['g.finance_goods_id'] = array('in',$hotel_stock['goods_ids']);
                    $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
                    $res_data = $m_hotelgoods->getGoodsList($fields,$where,'g.id desc','','');
                    foreach ($res_data as $v){
                        $gstock_num = 0;
                        if(isset($stock_goods[$v['finance_goods_id']])){
                            $gstock_num = $stock_goods[$v['finance_goods_id']]['stock_num'];
                        }
                        $stock_num+=$gstock_num;
                    }
                }
            }
            $data = array('hotel_id'=>$res_hotel['id'],'hotel_name'=>$res_hotel['name'],'address'=>$res_hotel['addr'],
                'contractor'=>$res_hotel['contractor'],'mobile'=>$res_hotel['mobile'],'maintainer'=>$maintainer,'maintainer_mobile'=>$maintainer_mobile,
                'hotel_network'=>$hotel_network,'hotel_box_type'=>$all_hotel_box_types[$res_hotel['hotel_box_type']],
                'small_platform_status'=>$small_platform_status,'small_platform_uptips'=>$small_platform_uptips,'box_list'=>$res_box,
                'up_time'=>date('Y-m-d H:i:s'),'desc'=>$desc,'small_platform_num'=>$small_platform_num,'tv_num'=>$tv_num,
                'box_num'=>$box_num,'room_num'=>$room_num,'box_type'=>$box_type,'box_info'=>$box_info,'stock_num'=>$stock_num,
                'hotel_drinks_num'=>$hotel_drinks_num,'hotel_drinks_content'=>$hotel_drinks_content,
            );
        }
        $this->to_back($data);
    }

    public function editbaseinfo(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $name = $this->params['name'];
        $area_id = $this->params['area_id'];
        $county_id = $this->params['county_id'];
        $business_circle_id = intval($this->params['business_circle_id']);
        $addr = $this->params['addr'];
        $contractor = $this->params['contractor'];
        $mobile = $this->params['mobile'];
        $food_style_id = $this->params['food_style_id'];
        $avg_expense = $this->params['avg_expense'];
        $contract_expiretime = $this->params['contract_expiretime'];
        $hotel_wifi = trim($this->params['hotel_wifi']);
        $hotel_wifi_pas = trim($this->params['hotel_wifi_pas']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $hotel_data = array('name'=>$name,'area_id'=>$area_id,'county_id'=>$county_id,'business_circle_id'=>$business_circle_id,
            'addr'=>$addr,'contractor'=>$contractor,'mobile'=>$mobile,'hotel_wifi'=>$hotel_wifi,'hotel_wifi_pas'=>$hotel_wifi_pas
        );
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->saveData($hotel_data,array('id'=>$hotel_id));

        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(15);
        if($res_hotel){
            $cache_key = "savor_hotel_$hotel_id";
            $data = $m_hotel->getInfoById($hotel_id);
            if(!empty($data)){
                $redis->set($cache_key, json_encode($data));
            }
        }
        $hotel_ext_data = array('food_style_id'=>$food_style_id,'avg_expense'=>$avg_expense,'contract_expiretime'=>$contract_expiretime);
        $m_hotel_ext = new \Common\Model\HotelExtModel();
        $res_ext = $m_hotel_ext->saveData($hotel_ext_data, array('hotel_id'=>$hotel_id));
        if($res_ext){
            $cache_key = "savor_hotel_ext_$hotel_id";
            $data = $m_hotel_ext->getOnerow(array('hotel_id'=>$hotel_id));
            if(!empty($data)){
                $redis->set($cache_key, json_encode($data));
            }
        }
        $this->to_back(array());
    }

    public function stockgoodslist(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $datalist = array();
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(9);
        $key = C('FINANCE_HOTELSTOCK').':'.$hotel_id;
        $res_cache = $redis->get($key);
        if(!empty($res_cache)){
            $hotel_stock = json_decode($res_cache,true);
            $stock_goods = array();
            foreach ($hotel_stock['goods_list'] as $v){
                $stock_goods[$v['id']] = $v;
            }
            if(!empty($hotel_stock['goods_ids'])){
                $fields = 'g.id,g.name,g.price,g.advright_media_id,g.cover_imgs,g.line_price,g.type,g.finance_goods_id';
                $where = array('h.hotel_id'=>$hotel_id,'g.type'=>43,'g.status'=>1);
                $where['g.finance_goods_id'] = array('in',$hotel_stock['goods_ids']);
                $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
                $res_data = $m_hotelgoods->getGoodsList($fields,$where,'g.id desc','','');
                $oss_host = get_oss_host();
                foreach ($res_data as $v){
                    $img_url = '';
                    if(!empty($v['cover_imgs'])){
                        $cover_imgs_info = explode(',',$v['cover_imgs']);
                        if(!empty($cover_imgs_info[0])){
                            $img_url = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                        }
                    }
                    $stock_num = 0;
                    if(isset($stock_goods[$v['finance_goods_id']])){
                        $stock_num = $stock_goods[$v['finance_goods_id']]['stock_num'];
                    }
                    $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'price'=>intval($v['price']),'type'=>$v['type'],
                        'img_url'=>$img_url,'stock_num'=>$stock_num,'finance_goods_id'=>$v['finance_goods_id']);
                    $datalist[] = $dinfo;
                }
            }

        }
        $this->to_back($datalist);
    }

    public function stockidcodelist(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $goods_id = intval($this->params['goods_id']);
        $page = intval($this->params['page']);
        $pagesize = 20;

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $start = ($page-1)*$pagesize;
        $limit = "$start,$pagesize";
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $res_stock = $m_stock_record->getStockIdcodeList($hotel_id,$goods_id,$limit);
        $this->to_back(array('datalist'=>$res_stock));
    }

    public function addhoteldrinks(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $is_nosell = intval($this->params['is_nosell']);//是否无在售酒水 1是 0否
        $images = $this->params['images'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $m_hotel_drinks = new \Common\Model\HotelDrinksModel();
        if($is_nosell==1){
            $add_data = array('hotel_id'=>$hotel_id,'openid'=>$openid,'image'=>'','type'=>2);
            $m_hotel_drinks->add($add_data);
        }else{
            if(empty($images)){
                $this->to_back(1001);
            }
            $all_images = explode(',',$images);
            foreach ($all_images as $v){
                if(!empty($v)){
                    $add_data = array('hotel_id'=>$hotel_id,'openid'=>$openid,'image'=>$v,'type'=>2);
                    $m_hotel_drinks->add($add_data);
                }
            }
        }
        $this->to_back(array('message'=>'添加成功'));

    }

    public function hoteldrinkslist(){
        $hotel_id = intval($this->params['hotel_id']);
        $m_hotel_drinks = new \Common\Model\HotelDrinksModel();
        $fields = 'date(add_time) as adate, GROUP_CONCAT(id) as all_ids';
        $where = array('hotel_id'=>$hotel_id,'type'=>2);
        $group = 'adate';
        $all_drinks = $m_hotel_drinks->getALLDataList($fields,$where,'','',$group);
        $datalist = array();
        if(!empty($all_drinks)){
            $oss_host = get_oss_host();
            foreach ($all_drinks as $v){
                $imgs = array();
                $re_imgs = $m_hotel_drinks->getALLDataList('image',array('id'=>array('in',$v['all_ids'])),'id desc','','');
                foreach ($re_imgs as $iv){
                    if(empty($iv['image'])){
                        break;
                    }else{
                        $imgs[]=$oss_host.$iv['image'];
                    }
                }
                $content = '';
                if(empty($imgs)){
                    $content = '当前餐厅无在售酒水';
                }
                $info = array('date'=>$v['adate'],'imgs'=>$imgs,'content'=>$content);
                $datalist[]=$info;
            }
            sortArrByOneField($datalist,'date',true);
        }
        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function nearby(){
        $latitude = $this->params['latitude'];
        $longitude = $this->params['longitude'];
        $openid = $this->params['openid'];

        $nearby_m = 200;
        $ret = getgeoByloa($latitude,$longitude);
        $m_area = new \Common\Model\AreaModel();
        if(empty($ret)){
            $area_id = 1;
        }else {
            $city_name = $ret['addressComponent']['city'];
            $fields = "id,region_name";
            $where['region_name'] = $city_name;
            $where['is_in_hotel'] = 1;
            $where['is_valid']    = 1;
            $city_info = $m_area->field($fields)->where($where)->order('id asc')->find();
            if(empty($city_info)){
                $area_id = 1;
            }else{
                $area_id = $city_info['id'];
            }
        }
        $oss_host = get_oss_host();
        $m_hotel = new \Common\Model\HotelModel();
        $fields = "a.id hotel_id,a.media_id,a.name,a.addr,a.tel,concat('".$oss_host."',media.`oss_addr`) as img_url,a.gps,a.htype";
        $where = array('a.area_id'=>$area_id,'a.state'=>array('in','1,4'),'a.flag'=>0,'a.gps'=>array('neq',''));

        if(!in_array($openid,array('oreqO5NXrcBFni6VVkHY_aBioa70','oreqO5JaMORW7oCcXRpwfTBIy9XE'))){
            $test_hotel_ids = C('TEST_HOTEL');
            $where['a.id'] = array('not in',"$test_hotel_ids");
        }

        $hotel_list = $m_hotel->alias('a')
            ->join('savor_hotel_ext ext on a.id=ext.hotel_id','left')
            ->join('savor_media media on ext.hotel_cover_media_id=media.id','left')
            ->field($fields)->where($where)->select();
        $nearby_data = array();
        if($longitude>0 && $latitude>0){
            $bd_lnglat = getgeoByTc($latitude, $longitude);
            foreach($hotel_list as $k=>$v){
                $v['dis'] = '';
                if($v['gps']!='' && $longitude>0 && $latitude>0){
                    $latitude = $bd_lnglat[0]['y'];
                    $longitude = $bd_lnglat[0]['x'];

                    $gps_arr = explode(',',$v['gps']);
                    $dis = geo_distance($latitude,$longitude,$gps_arr[1],$gps_arr[0]);
                    $v['dis_com'] = $dis;
                    if($dis>1000){
                        $tmp_dis = $dis/1000;
                        $dis = sprintf('%0.2f',$tmp_dis);
                        $dis = $dis.'km';
                    }else{
                        $dis = intval($dis);
                        $dis = $dis.'m';
                    }
                    $v['dis'] = $dis;

                    if($v['dis_com']<=$nearby_m){
                        $nearby_data[]=$v;
                    }
                }
            }
            sortArrByOneField($nearby_data,'dis_com');
        }
        $datalist = array();
        foreach ($nearby_data as $k=>$v){
            $dis = $v['dis'];
            if(empty($dis)){
                $dis = '';
            }
            if($v['htype']==10){
                $htype_str = '已签约';
            }else{
                $htype_str = '';
            }
            $datalist[]=array('hotel_id'=>$v['hotel_id'],'name'=>$v['name'],'addr'=>$v['addr'],'dis'=>$dis,'htype_str'=>$htype_str);
        }
        $range_str = "可选{$nearby_m}米范围内的地点";
        $this->to_back(array('datalist'=>$datalist,'range_str'=>$range_str));
    }

    public function getsignprogress(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $sign_progress = $m_salerecord->getSignProcess($hotel_id);
        $this->to_back($sign_progress);
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
    }
}