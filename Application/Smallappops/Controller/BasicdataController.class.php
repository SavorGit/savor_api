<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class BasicdataController extends CommonController{

    public $map_stat_hotel_key = array('1'=>'small_platform_online_hotels','2'=>'small_platform_24_hotels','3'=>'small_platform_7day_hotels','4'=>'small_platform_30day_hotels',
        '5'=>'box_online_hotels','6'=>'box_24_hotels','7'=>'box_7day_hotels','8'=>'box_30day_hotels',
        '9'=>'small_platform_notup_hotels','10'=>'box_notup_hotels',
        '11'=>'adv_notup_hotels','12'=>'pro_notup_hotels','13'=>'ads_notup_hotels','14'=>'small_platform_24_7_hotels',
        '15'=>'box_24_7_hotels','16'=>'abnormal_hotels',
    );

    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'stathotels':
                $this->is_verify = 1;
                $this->valid_fields = array('source'=>1001,'type'=>1001,'openid'=>1001,'area_id'=>1001,'staff_id'=>1001);
                break;
            case 'hotel':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'staff_id'=>1001);
                break;
            case 'versionupgrade':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'staff_id'=>1001);
                break;
            case 'resourceupdate':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'staff_id'=>1001);
                break;
            case 'device':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'staff_id'=>1001);
                break;
            case 'data':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'staff_id'=>1001,'day'=>1001);
                break;

        }
        parent::_init_();
    }

    public function stathotels(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $source = $this->params['source'];//hotel,versionup,resourceup,device
        $type = $this->params['type'];//1小平台-在线(酒楼),2小平台-24h开机(酒楼),3小平台-7天失联(酒楼),4小平台-30日失联(酒楼),
        //5机顶盒-在线(酒楼),6机顶盒-24h开机(酒楼),7机顶盒-7天失联(酒楼),8小平台-30日失联(酒楼)
        //9小平台未升级,10机顶盒未升级(版本),11酒楼宣传片未更新(资源),12节目未更新(资源),13广告未更新(资源)
        //,14小平台-大于24小时小于7天失联(酒楼),15机顶盒-大于24小时小于7天失联(酒楼),16设备概况

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $check_type = $this->check_permission($res_staff,$area_id,$staff_id);
        if($check_type==0){
            $this->to_back(1001);
        }
        $res_staff = $m_staff->getInfo(array('id'=>$staff_id));
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(22);
        if(in_array($check_type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
            $cache_key = C('SAPP_OPS')."stat:$source:area:".$area_id;
        }elseif($area_id>0 && $staff_id>0){
            $cache_key = C('SAPP_OPS')."stat:$source:area_staff:".$res_staff['sysuser_id'].':'.$area_id;
        }elseif($area_id==0 && $staff_id>0){
            $cache_key = C('SAPP_OPS')."stat:$source:staff:".$res_staff['sysuser_id'];
        }else{
            $cache_key = '';
        }
        $stat_hotel = array();
        if(!empty($cache_key)) {
            $res_cache_data = $redis->get($cache_key);
            $res_data = json_decode($res_cache_data, true);
            if(!empty($res_data)){
                $hotels = $res_data[$this->map_stat_hotel_key[$type]];
                if(!empty($hotels)){
                    $hotel_types = C('HEART_HOTEL_BOX_TYPE');
                    $m_hotel = new \Common\Model\HotelModel();
                    $where = array('a.state'=>1,'a.flag'=>0,'a.hotel_box_type'=>array('in',array_keys($hotel_types)));
                    $where['a.id'] = array('in',array_keys($hotels));
                    $fields = 'a.id,a.name,a.pinyin';
                    $res_hotels = $m_hotel->getHotelLists($where,'a.pinyin asc','',$fields);
                    $all_hotels = array();
                    foreach ($res_hotels as $v){
                        $letter = substr($v['pinyin'],0,1);
                        $letter = strtoupper($letter);
                        $box_num = 0;
                        if(isset($hotels[$v['id']]) && is_array($hotels[$v['id']])){
                            $box_num = count($hotels[$v['id']]);
                        }
                        $all_hotels[$letter][]=array('hotel_id'=>$v['id'],'hotel_name'=>$v['name'],'box_num'=>$box_num);
                    }
                    foreach ($all_hotels as $k=>$v){
                        $dinfo = array('id'=>ord("$k")-64,'region'=>$k,'items'=>$v);
                        $stat_hotel[]=$dinfo;
                    }
                }
            }
        }
        $this->to_back($stat_hotel);
    }

    public function hotel(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $this->check_permission($res_staff,$area_id,$staff_id);
        if($type==0){
            $this->to_back(1001);
        }
        $res_staff = $m_staff->getInfo(array('id'=>$staff_id));
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(22);
        if(in_array($type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
            $cache_key = C('SAPP_OPS').'stat:hotel:area:'.$area_id;
        }elseif($area_id>0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:hotel:area_staff:'.$res_staff['sysuser_id'].':'.$area_id;
        }elseif($area_id==0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:hotel:staff:'.$res_staff['sysuser_id'];
        }else{
            $cache_key = '';
        }
        $res_data = array();
        if(!empty($cache_key)) {
            $desc = array('酒楼数：当前范围下所有正常状态的酒楼数量', '在线：15分钟内有心跳', '24h内开机：24小时内有心跳', '7日失联：7日内无心跳', '30日失联：30日内无心跳');
            $res_cache_data = $redis->get($cache_key);
            $res_data = json_decode($res_cache_data, true);
            foreach ($this->map_stat_hotel_key as $v){
                if(isset($res_data[$v])){
                    unset($res_data[$v]);
                }
            }
            $res_data['desc'] = $desc;
        }
        $this->to_back($res_data);
    }

    public function versionupgrade(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $this->check_permission($res_staff,$area_id,$staff_id);
        if($type==0){
            $this->to_back(1001);
        }
        $res_staff = $m_staff->getInfo(array('id'=>$staff_id));
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(22);
        if(in_array($type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
            $cache_key = C('SAPP_OPS').'stat:versionup:area:'.$area_id;
        }elseif($area_id>0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:versionup:area_staff:'.$res_staff['sysuser_id'].':'.$area_id;
        }elseif($area_id==0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:versionup:staff:'.$res_staff['sysuser_id'];
        }else{
            $cache_key = '';
        }
        $res_data = array();
        if(!empty($cache_key)) {
            $desc = array('小平台：已升级数量/未升级数量', '机顶盒：已升级数量/未升级数量');
            $res_cache_data = $redis->get($cache_key);
            $res_data = json_decode($res_cache_data, true);
            foreach ($this->map_stat_hotel_key as $v){
                if(isset($res_data[$v])){
                    unset($res_data[$v]);
                }
            }
            $res_data['desc'] = $desc;
        }
        $this->to_back($res_data);
    }

    public function resourceupdate(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $this->check_permission($res_staff,$area_id,$staff_id);
        if($type==0){
            $this->to_back(1001);
        }
        $res_staff = $m_staff->getInfo(array('id'=>$staff_id));
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(22);
        if(in_array($type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
            $cache_key = C('SAPP_OPS').'stat:resourceup:area:'.$area_id;
        }elseif($area_id>0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:resourceup:area_staff:'.$res_staff['sysuser_id'].':'.$area_id;
        }elseif($area_id==0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:resourceup:staff:'.$res_staff['sysuser_id'];
        }else{
            $cache_key = '';
        }
        $res_data = array();
        if(!empty($cache_key)) {
            $desc = array('通过各类型资源的期号进行对比，期号最新的为已更新，其余为未更新。');
            $res_cache_data = $redis->get($cache_key);
            $res_data = json_decode($res_cache_data, true);
            foreach ($this->map_stat_hotel_key as $v){
                if(isset($res_data[$v])){
                    unset($res_data[$v]);
                }
            }
            $res_data['desc'] = $desc;
        }
        $this->to_back($res_data);
    }

    public function device(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $this->check_permission($res_staff,$area_id,$staff_id);
        if($type==0){
            $this->to_back(1001);
        }
        $res_staff = $m_staff->getInfo(array('id'=>$staff_id));
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(22);
        if(in_array($type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
            $cache_key = C('SAPP_OPS').'stat:device:area:'.$area_id;
        }elseif($area_id>0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:device:area_staff:'.$res_staff['sysuser_id'].':'.$area_id;
        }elseif($area_id==0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:device:staff:'.$res_staff['sysuser_id'];
        }else{
            $cache_key = '';
        }
        $res_data = array();
        if(!empty($cache_key)) {
            $desc = array('内存使用：异常代表机顶盒内存已满，无法下载新的节目资源。');
            $res_cache_data = $redis->get($cache_key);
            $res_data = json_decode($res_cache_data, true);
            foreach ($this->map_stat_hotel_key as $v){
                if(isset($res_data[$v])){
                    unset($res_data[$v]);
                }
            }
            $res_data['desc'] = $desc;
        }
        $this->to_back($res_data);
    }

    public function statdata(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $day = intval($this->params['day']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $this->check_permission($res_staff,$area_id,$staff_id);
        if($type==0){
            $this->to_back(1001);
        }
        $res_staff = $m_staff->getInfo(array('id'=>$staff_id));

        $start_time = date('Y-m-d 00:00:00',strtotime('-1day'));
        switch ($day){
            case 1:
                $start_time = date('Y-m-d 00:00:00',strtotime('-1day'));
                break;
            case 2:
                $start_time = date('Y-m-d 00:00:00',strtotime('-6day'));
                break;
            case 3:
                $start_time = date('Y-m-01 00:00:00');
                break;
        }
        $end_time = date('Y-m-d 23:59:59',strtotime('-1day'));
        $is_data = 1;
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $merchant_fields = 'count(m.id) as hotel_num';
        $merchant_where = array('m.status'=>1,'hotel.state'=>1,'hotel.flag'=>0);
        $merchant_where['m.add_time'] = array('elt',$end_time);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $staff_fields = 'count(a.id) as staff_num';
        $staff_where = array('merchant.status'=>1,'hotel.state'=>1,'hotel.flag'=>0);
        $staff_where['merchant.add_time'] = array('elt',$end_time);

        $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
        $remain_maintainer_id = $remain_area_id = 0;

        $m_statichotelstaffdata = new \Common\Model\Smallapp\StaticHotelstaffdataModel();
        $static_maintainer_id = $static_area_id = 0;

        $m_finance_stockrecord = new \Common\Model\Finance\StockRecordModel();
        if(in_array($type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
            if($area_id>0){
                $merchant_where['hotel.area_id'] = $area_id;
                $staff_where['hotel.area_id'] = $area_id;
                $remain_area_id = $area_id;
                $static_area_id = $area_id;
            }
        }elseif($area_id>0 && $staff_id>0){
            $merchant_where['ext.maintainer_id'] = $res_staff['sysuser_id'];
            $merchant_where['hotel.area_id'] = $area_id;
            $staff_where['ext.maintainer_id'] = $res_staff['sysuser_id'];
            $staff_where['hotel.area_id'] = $area_id;
            $remain_area_id = $area_id;
            $remain_maintainer_id = $res_staff['sysuser_id'];
            $static_area_id = $area_id;
            $static_maintainer_id = $res_staff['sysuser_id'];
        }elseif($area_id==0 && $staff_id>0){
            $merchant_where['ext.maintainer_id'] = $res_staff['sysuser_id'];
            $staff_where['ext.maintainer_id'] = $res_staff['sysuser_id'];
            $remain_maintainer_id = $res_staff['sysuser_id'];
            $static_maintainer_id = $res_staff['sysuser_id'];
        }else{
            $is_data = 0;
        }

        $res_data = array();
        if($is_data){
            $res_merchant = $m_merchant->getMerchantInfo($merchant_fields,$merchant_where);
            $hotel_num = intval($res_merchant[0]['hotel_num']);
            $res_staff = $m_staff->getMerchantStaffInfo($staff_fields,$staff_where);
            $staff_num = intval($res_staff[0]['staff_num']);
            $res_remain = $m_userintegral->getRemainIntegral($remain_area_id,$remain_maintainer_id);
            $remain_integral = 0;
            if(!empty($res_remain)){
                $remain_integral = $res_remain['total_integral'];
            }
            $res_staticdata = $m_statichotelstaffdata->getStaffData($static_area_id,$static_maintainer_id,$start_time,$end_time);
            $res_sell = $m_finance_stockrecord->getStaticData($static_area_id,$static_maintainer_id,$start_time,$end_time);

            $res_data = array('hotel_num'=>$hotel_num,'staff_num'=>$staff_num,'get_integral'=>$res_staticdata['get_integral'],
                'remain_integral'=>$remain_integral,'money'=>$res_staticdata['money'],'task_data'=>$res_staticdata['task_data'],
                'brand_num'=>$res_sell['brand_num'],'series_num'=>$res_sell['series_num'],'sell_num'=>$res_sell['sell_num']
            );
            $res_data['desc'] = array('1.获得积分：时间段内一共获得多少积分；','2.剩余积分：截止到昨天24:00，剩余积分总量（待核销+正常）；','3.提现金额：时间段内一共提现多少钱',
                '一.奖券任务','1.发布次数：发布任务的餐厅范围内，餐厅经理数量每日加和；','2.领取次数：发布任务的餐厅范围内，餐厅经理领取任务次数每日加和；','3.售酒数量：发布任务的餐厅范围内，每日核销数量加和；','4.领券人：发布任务的餐厅范围内，总共领取金卡优惠券人数；','5.奖励：发布任务的餐厅范围内，本任务共产生多少积分奖励（待核销+正常）；',
                '二.点播任务','1.发布次数：发布任务的餐厅范围内，餐厅经理数量每日加和；','2.领取次数：发布任务的餐厅范围内，餐厅经理领取任务次数每日加和；','3.点播应操作次数：发布任务的餐厅范围内，正常版位数*1.8，每日加和，四舍五入；', '4.完成次数：发布任务的餐厅范围内，所以餐厅经理总共完成次数；','5.奖励：发布任务的餐厅范围内，本任务共产生多少积分奖励（正常）；',
                '三.邀请函任务','1.发布次数：发布任务的餐厅范围内，餐厅经理数量每日加和；','2.领取次数：发布任务的餐厅范围内，餐厅经理领取任务次数每日加和；', '3.点播应操作次数：发布任务的餐厅范围内，正常版位数*1.6，每日加和，四舍五入；','4.完成次数：发布任务的餐厅范围内，所以餐厅经理总共完成次数；','5.奖励：发布任务的餐厅范围内，本任务共产生多少积分奖励（正常）；',
            );
        }
        $this->to_back($res_data);
    }

    private function check_permission($staff_info,$area_id,$staff_id){
        $permission = json_decode($staff_info['permission'],true);
        switch ($permission['hotel_info']['type']) {
            case 1:
                $type = 1;
                break;
            case 2:
                if(!in_array($area_id,$permission['hotel_info']['area_ids'])){
                    $this->to_back(1001);
                }
                $type = 2;
                break;
            case 3:
                if($staff_id!=$staff_info['id']){
                    $this->to_back(1001);
                }
                $type = 3;
                break;
            case 4:
                if($area_id>0){
                    if(!in_array($area_id,$permission['hotel_info']['area_ids'])){
                        $this->to_back(1001);
                    }
                }
                $type = 4;
                break;
            default:
                $type = 0;
        }
        return $type;
    }

}