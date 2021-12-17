<?php
namespace Box\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;
class ProgramController extends CommonController{
    private $box_download_pre ;
    private $box_program_play_pre;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'reportDownloadInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>'1001','type'=>'1001','resource_info'=>'1000');
                break;
            case 'reportPlayInfo';
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>'1001','resource_info'=>'1000');
                break;
            case 'getActivitygoodsProgramList':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
            case 'getGoodsProgramList':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
            case 'getGoodsCountdown':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001);
                break;
            case 'getSelectcontentProgramList':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
            case 'getHotPlayProgramList':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
            case 'getWelcomeResource':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;

        }
        parent::_init_();
        $this->box_download_pre = 'box:download:';
        $this->box_program_play_pre = 'box:play:';
    }
    /**
     * @desc 机顶盒当前下载中资源上报(1广告、2节目、3宣传片)
     */
    public function reportDownloadInfo(){
        $box_mac = $this->params['box_mac'];
        $type = $this->params['type'];
        $resource_info = str_replace("\\", '',$this->params['resource_info'] );
        if(!empty($resource_info)){
            $redis = new SavorRedis();
            $redis->select(14);
            $cache_key = $this->box_download_pre.$type.':'.$box_mac;
            $redis_resource_info = $redis->get($cache_key);
            if(md5($resource_info) != md5($redis_resource_info)){
                $redis->set($cache_key, $resource_info);
                $this->to_back(10000);
            }else {
                $this->to_back(30072);
            }
        }else {
            $this->to_back(30071);
        }
    }
    /**
     * @desc 机顶盒当前已下载(播放中)的节目单资源
     */
    public function reportPlayInfo(){
        $box_mac = $this->params['box_mac'];
        $resource_info = str_replace("\\", '',$this->params['resource_info'] );
        $redis = new SavorRedis();
        $redis->select(14);
        $cache_key = $this->box_program_play_pre.$box_mac;

        $play_info = $redis->get($cache_key);
        if(md5($resource_info)!== md5($play_info)){
            $now_play_info = json_decode($resource_info,true);
            if(!isset($now_play_info['hotplay']) || empty($now_play_info['hotplay'])){
                if(!empty($play_info)){
                    $old_play_info = json_decode($play_info,true);
                    if(isset($old_play_info['hotplay']) && !empty($old_play_info['hotplay'])){
                        $now_play_info['hotplay'] = $old_play_info['hotplay'];
                        $resource_info = json_encode($now_play_info);
                    }
                }
            }
            $redis->set($cache_key, $resource_info);
            $this->to_back(10000);
        }else {
            $this->to_back(30073);
        }
    }

    public function getGoodsProgramList(){
        $box_mac = $this->params['box_mac'];
        $m_box = new \Common\Model\BoxModel();
        $map = array();
        $map['a.mac'] = $box_mac;
        $map['a.state'] = 1;
        $map['a.flag']  = 0;
        $map['d.state'] = 1;
        $map['d.flag']  = 0;
        $box_info = $m_box->getBoxInfo('a.id as box_id,d.id as hotel_id,c.type as room_type', $map);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $hotel_id = $box_info[0]['hotel_id'];
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $program_key = C('SAPP_SALE_ACTIVITYGOODS_PROGRAM').":$hotel_id";
        $res_period = $redis->get($program_key);
        if(empty($res_period)){
            $period = getMillisecond();
            $period_data = array('period'=>$period);
            $redis->set($program_key,json_encode($period_data));
        }else{
            $period_info = json_decode($res_period,true);
            $period = $period_info['period'];
        }

        $cache_key = C('SAPP_SALE').'activitygoods:loopplay:'.$hotel_id;
        $res_cache = $redis->get($cache_key);
        if(!empty($res_cache)){
            $loopplay_data = json_decode($res_cache,true);
        }else{
            $fields = 'h.hotel_id,h.goods_id';
            $where = array('h.hotel_id'=>$hotel_id,'g.status'=>2);
            $orderby = 'g.id desc';
            $limit = "0,1";
            $res_goods = $m_hotelgoods->getList($fields,$where,$orderby,$limit);
            $loopplay_data = array($res_goods[0]['goods_id']=>$res_goods[0]['goods_id']);
            $redis->set($cache_key,json_encode($loopplay_data));
        }
        $nowtime = date('Y-m-d H:i:s');
        /*
        $type = 10;//10官方活动促销(统一为优选),20我的活动,30积分兑换现金 40秒杀商品
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $fields = 'id as goods_id,media_id,name,price,start_time,end_time,type,scope,is_storebuy,jd_url,duration';
        $where = array('status'=>2);
        $where['type'] = $type;
        $where['start_time'] = array('elt',$nowtime);
        $where['end_time'] = array('egt',$nowtime);
        $orderby = 'id desc';
        $optimize_goods = $m_goods->getDataList($fields,$where,$orderby);
        */

        $fields = 'g.id as goods_id,g.media_id,g.salemedia_id,g.name,g.price,g.start_time,g.end_time,g.type,g.scope,g.is_storebuy,g.jd_url,g.duration';
        $where = array('h.hotel_id'=>$hotel_id,'g.status'=>2,'h.type'=>1);
//        $types = array(20,40);//10官方活动促销(统一为优选),20我的活动,30积分兑换现金 40秒杀商品
        $types = array(40);
        $where['g.type'] = array('in',$types);
        $where['g.start_time'] = array('elt',$nowtime);
        $where['g.end_time'] = array('egt',$nowtime);
        $orderby = 'g.id desc';
        $limit = "";
        $my_hotelgoods = $m_hotelgoods->getList($fields,$where,$orderby,$limit,'g.id');
//        $res_goods = array_merge($optimize_goods,$my_hotelgoods);
        $res_goods = $my_hotelgoods;
        /*
        $fields = 'g.id as goods_id';
        $where = array('h.hotel_id'=>$hotel_id,'g.status'=>2,'h.type'=>1);
        $where['g.type']= 10;
        $orderby = 'g.id desc';
        $all_hotel_goods = $m_hotelgoods->getList($fields,$where,$orderby,'','g.id');
        $hotel_goods_ids = array();
        foreach ($all_hotel_goods as $gv){
            $hotel_goods_ids[]=$gv['goods_id'];
        }
        */
        $host_name = C('HOST_NAME');
        $m_task = new \Common\Model\Integral\TaskHotelModel();
        $tfields = $fields = 'g.id as goods_id,g.video_intromedia_id as media_id,g.name,g.price,g.type,0 as start_time,0 as end_time,0 as scope,0 as is_storebuy';
        $twhere = array('a.hotel_id'=>$hotel_id,'task.task_type'=>array('in',array(22,24)),'task.status'=>1,'task.flag'=>1);
        $res_taskgoods = $m_task->getHotelTaskGoodsList($tfields,$twhere,'g.id asc','g.id');
        if(!empty($res_taskgoods)){
            $res_goods = array_merge($res_taskgoods,$res_goods);
        }

        $m_media = new \Common\Model\MediaModel();
        $goods_ids = array();
        $program_list = array();
        $all_laimao_sale_hotels = C('LAIMAO_SALE_HOTELS');
        foreach ($res_goods as $v){
            $info = array('goods_id'=>$v['goods_id'],'chinese_name'=>$v['name'],'price'=>intval($v['price']),
                'start_date'=>$v['start_time'],'end_date'=>$v['end_time'],'type'=>intval($v['type']));
            if($v['type']==41 || $v['type']==42){
                $info['price'] = '';
                $info['start_date'] = '';
                $info['end_date'] = '';
            }
            if($info['goods_id']==C('LAIMAO_SECKILL_GOODS_ID')){
                if(isset($all_laimao_sale_hotels[$hotel_id])){
                    $v['media_id'] = $v['salemedia_id'];
                }
            }
            $media_info = $m_media->getMediaInfoById($v['media_id']);
            $info['oss_path'] = $media_info['oss_path'];
            $name_info = pathinfo($info['oss_path']);
            $info['name'] = $name_info['basename'];
            $info['media_type'] = $media_info['type'];
            $info['md5'] = $media_info['md5'];
            $info['duration'] = intval($media_info['duration']);
            $qrcode_url = '';
            $is_storebuy = 0;
            if($v['type']==20){
                $is_storebuy = intval($v['is_storebuy']);
                $qrcode_url = $host_name."/smallsale/qrcode/getBoxQrcode?box_mac=$box_mac&goods_id={$v['goods_id']}&type=22";
                if($media_info['type']==2 && $v['duration']){
                    $info['duration'] = intval($v['duration']);
                }
            }elseif($v['type']==40){
                $is_storebuy = intval($v['is_storebuy']);
//                $content = urlencode($v['jd_url'].'?mac='.$box_mac);
                if($info['goods_id']==C('LAIMAO_SECKILL_GOODS_ID')){
                    $info['price'] = '';
                }
                $qrcode_url = $host_name."/smallapp46/qrcode/getBoxQrcode?box_mac=$box_mac&box_id={$box_info[0]['box_id']}&data_id={$v['goods_id']}&type=24";
                if(empty($info['duration'])){
                    $info['duration'] = 30;
                }
            }else{
                /*
                if(in_array($v['goods_id'],$hotel_goods_ids)){
                    $qrcode_url = $host_name."/smallsale/qrcode/getBoxQrcode?box_mac=$box_mac&goods_id={$v['goods_id']}&type=22";
                    $is_storebuy = intval($v['is_storebuy']);
                }
                */
            }
            $info['qrcode_url'] = $qrcode_url;
            $info['is_storebuy'] = $is_storebuy;
            if(isset($loopplay_data[$v['goods_id']])){
                if($v['type']==20 && $v['scope']){
                    if($v['scope']==1){
                        if($box_info[0]['room_type']==1){
                            $info['play_type'] = 1;
                        }else{
                            $info['play_type'] = 2;
                        }
                    }else{
                        if($box_info['room_type']!=1){
                            $info['play_type'] = 1;
                        }else{
                            $info['play_type'] = 2;
                        }
                    }
                }else{
                    $info['play_type'] = 1;
                }
            }else{
                if($v['type']==40){
                    $info['play_type'] = 1;
                }else{
                    $info['play_type'] = 2;
                }
            }
            $program_list[] = $info;
            $goods_ids[] = $v['goods_id'];
        }
        $is_newperiod = 0;
        foreach ($loopplay_data as $k=>$v){
            if(!in_array($v,$goods_ids)){
                $is_newperiod = 1;
                unset($loopplay_data[$k]);
            }
        }
        if($is_newperiod){
            $redis->set($cache_key,json_encode($loopplay_data));

            $program_key = C('SAPP_SALE_ACTIVITYGOODS_PROGRAM').":$hotel_id";
            $period = getMillisecond();
            $period_data = array('period'=>$period);
            $redis->set($program_key,json_encode($period_data));
        }
        $res = array('period'=>$period,'datalist'=>$program_list);
        $this->to_back($res);
    }

    public function getShopGoodsProgramList(){
        $box_mac = $this->params['box_mac'];
        $m_box = new \Common\Model\BoxModel();
        $map = array();
        $map['a.mac'] = $box_mac;
        $map['a.state'] = 1;
        $map['a.flag']  = 0;
        $map['d.state'] = 1;
        $map['d.flag']  = 0;
        $box_info = $m_box->getBoxInfo('a.id as box_id,d.id as hotel_id,c.type as room_type', $map);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $hotel_id = $box_info[0]['hotel_id'];
        $box_id = $box_info[0]['box_id'];
        $m_programmenu = new \Common\Model\ProgramMenuHotelModel();
        $res_menu = $m_programmenu->getLatestMenuid($hotel_id);
        $menu_id = $res_menu['menu_id'];

        $m_programitem = new \Common\Model\ProgramMenuItemModel();
        $where = array('menu_id'=>$menu_id,'type'=>4);
        $res_item = $m_programitem->getData('*',$where,'id asc');
        $item_goods = array();
        if(!empty($res_item)){
            $goods_ids = array();
            foreach ($res_item as $v){
                if($v['ads_id'] && !in_array($v['ads_id'],$goods_ids)){
                    $goods_ids[] = $v['ads_id'];
                }
            }
            if(!empty($goods_ids)){
                $where = array('id'=>array('in',$goods_ids));
                $orderby = 'id desc';
                $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
                $res_goods = $m_goods->getDataList('*',$where,$orderby);
                if(!empty($res_goods)){
                    foreach ($res_goods as $v){
                        $item_goods[$v['id']] = $v;
                    }
                }
            }
        }
        $key = C('SAPP_SHOP_PROGRAM');
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(2);
        $program_goods_key = $key.":$menu_id:goods";
        $program_goods = array();
        $res_program_goods = $redis->get($program_goods_key);
        if(!empty($res_program_goods)){
            $program_goods = json_decode($res_program_goods,true);
        }
        $is_newperiod = 0;
        $program_period_key = $key.":$menu_id:period";
        $res_period = $redis->get($program_period_key);
        if(empty($res_period)){
            $is_newperiod = 1;
            $period = '';
        }else{
            $period_info = json_decode($res_period,true);
            $period = $period_info['period'];
        }

        $program_list = array();
        if(!empty($item_goods)){
            $host_name = C('HOST_NAME');
            $m_media = new \Common\Model\MediaModel();
            foreach ($res_item as $v){
                $goods_id = $v['ads_id'];
                if($goods_id && isset($item_goods[$goods_id])){
                    $goods_info = $item_goods[$goods_id];
                    if(!isset($program_goods[$goods_id])){
                        $is_newperiod = 1;
                        $program_goods[$goods_id] = array('id'=>$goods_info['id'],'tv_media_id'=>$goods_info['tv_media_id'],'status'=>$goods_info['status']);
                    }else{
                        if($goods_info['tv_media_id']!=$program_goods[$goods_id]['tv_media_id'] || $goods_info['status']!=$program_goods[$goods_id]['status']){
                            $is_newperiod = 1;
                            $program_goods[$goods_id]['tv_media_id'] = $goods_info['tv_media_id'];
                            $program_goods[$goods_id]['status'] = $goods_info['status'];
                        }
                    }

                    $info = array('goods_id'=>$goods_info['id'],'chinese_name'=>$goods_info['name'],'type'=>intval($goods_info['type']),
                        'location_id'=>intval($v['location_id']));
                    $media_info = $m_media->getMediaInfoById($goods_info['tv_media_id']);
                    $info['oss_path'] = $media_info['oss_path'];
                    $name_info = pathinfo($info['oss_path']);
                    $info['name'] = $name_info['basename'];
                    $info['media_type'] = $media_info['type'];
                    $info['md5'] = $media_info['md5'];
                    $info['duration'] = intval($v['duration']);

                    $qrcode_url = $host_name."/smallsale19/qrcode/dishQrcode?box_id=$box_id&data_id={$info['goods_id']}&type=28";
//                    $qrcode_url = $host_name."/Smallapp46/qrcode/getBoxQrcode?box_mac={$box_mac}&box_id={$box_id}&data_id={$info['goods_id']}&type=28";
                    $info['qrcode_url'] = $qrcode_url;
                    $program_list[] = $info;
                }
            }
        }

        if($is_newperiod){
            $period = getMillisecond();
            $period_data = array('period'=>$period);
            $redis->set($program_period_key,json_encode($period_data),30*86400);
            $redis->set($program_goods_key,json_encode($program_goods),30*86400);
        }
        $res = array('period'=>$period,'datalist'=>$program_list);
        $this->to_back($res);
    }


    public function getGoodsCountdown(){
        $goods_id = intval($this->params['goods_id']);
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        $remain_time = 0;
        if($res_goods['type']==40 && $res_goods['status']==2){
            $end_time = strtotime($res_goods['end_time']);
            $now_time = time();
            $remain_time = $end_time-$now_time>0?$end_time-$now_time:0;
            if($remain_time==0){
                $m_goods->updateData(array('id'=>$goods_id),array('status'=>5));
            }
        }
        $res = array('remain_time'=>intval($remain_time));
        $this->to_back($res);
    }

    public function getActivitygoodsProgramList(){//已废弃
        $box_mac = $this->params['box_mac'];
        $m_box = new \Common\Model\BoxModel();
        $map = array();
        $map['a.mac'] = $box_mac;
        $map['a.state'] = 1;
        $map['a.flag']  = 0;
        $map['d.state'] = 1;
        $map['d.flag']  = 0;
        $box_info = $m_box->getBoxInfo('a.id as box_id,d.id as hotel_id,c.type as room_type', $map);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $hotel_id = $box_info[0]['hotel_id'];
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $program_key = C('SAPP_SALE_ACTIVITYGOODS_PROGRAM').":$hotel_id";
        $res_period = $redis->get($program_key);
        if(empty($res_period)){
            $period = getMillisecond();
            $period_data = array('period'=>$period);
            $redis->set($program_key,json_encode($period_data));
        }else{
            $period_info = json_decode($res_period,true);
            $period = $period_info['period'];
        }

        $cache_key = C('SAPP_SALE').'activitygoods:loopplay:'.$hotel_id;
        $res_cache = $redis->get($cache_key);
        if(!empty($res_cache)){
            $loopplay_data = json_decode($res_cache,true);
        }else{
            $fields = 'h.hotel_id,h.goods_id';
            $where = array('h.hotel_id'=>$hotel_id,'g.status'=>2);
            $orderby = 'g.id desc';
            $limit = "0,1";
            $res_goods = $m_hotelgoods->getList($fields,$where,$orderby,$limit);
            $loopplay_data = array($res_goods[0]['goods_id']=>$res_goods[0]['goods_id']);
            $redis->set($cache_key,json_encode($loopplay_data));
        }
        $nowtime = date('Y-m-d H:i:s');
        $fields = 'g.id as goods_id,g.media_id,g.name,g.price,g.start_time,g.end_time,g.type,g.scope,g.duration';
        $where = array('h.hotel_id'=>$hotel_id,'g.status'=>2,'h.type'=>1);
        $where['g.type']= array('in',array(10,20));
        $where['g.end_time'] = array('egt',$nowtime);
        $orderby = 'g.id desc';
        $limit = "";
        $res_goods = $m_hotelgoods->getList($fields,$where,$orderby,$limit,'g.id');
        $host_name = C('HOST_NAME');
        $m_media = new \Common\Model\MediaModel();

        $goods_ids = array();
        $program_list = array();
        foreach ($res_goods as $v){
            $info = array('goods_id'=>$v['goods_id'],'chinese_name'=>$v['name'],'price'=>intval($v['price']),
                'start_date'=>$v['start_time'],'end_date'=>$v['end_time']);
            $media_info = $m_media->getMediaInfoById($v['media_id']);
            $info['oss_path'] = $media_info['oss_path'];
            $name_info = pathinfo($info['oss_path']);
            $info['name'] = $name_info['basename'];
            $info['media_type'] = $media_info['type'];
            $info['md5'] = $media_info['md5'];
            $info['duration'] = $media_info['duration'];
            $info['qrcode_url'] = $host_name."/smallsale/qrcode/getBoxQrcode?box_mac=$box_mac&goods_id={$v['goods_id']}&type=22";
            if($v['type']==20 && $media_info['type']==2 && $v['duration']){
                $info['duration'] = $v['duration'];
            }
            if(isset($loopplay_data[$v['goods_id']])){
                if($v['type']==20 && $v['scope']){
                    if($v['scope']==1){
                        if($box_info[0]['room_type']==1){
                            $info['play_type'] = 1;
                        }else{
                            $info['play_type'] = 2;
                        }
                    }else{
                        if($box_info['room_type']!=1){
                            $info['play_type'] = 1;
                        }else{
                            $info['play_type'] = 2;
                        }
                    }
                }else{
                    $info['play_type'] = 1;
                }
            }else{
                $info['play_type'] = 2;
            }
            $program_list[] = $info;
            $goods_ids[] = $v['goods_id'];
        }
        $is_newperiod = 0;
        foreach ($loopplay_data as $k=>$v){
            if(!in_array($v,$goods_ids)){
                $is_newperiod = 1;
                unset($loopplay_data[$k]);
            }
        }
        if($is_newperiod){
            $redis->set($cache_key,json_encode($loopplay_data));

            $program_key = C('SAPP_SALE_ACTIVITYGOODS_PROGRAM').":$hotel_id";
            $period = getMillisecond();
            $period_data = array('period'=>$period);
            $redis->set($program_key,json_encode($period_data));
        }
        $res = array('period'=>$period,'datalist'=>$program_list);
        $this->to_back($res);
    }

    public function getOptimizeProgramList(){//已废弃
        $box_mac = $this->params['box_mac'];

        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $program_key = C('SAPP_OPTIMIZE_PROGRAM');
        $res_period = $redis->get($program_key);
        if(empty($res_period)){
            $period = getMillisecond();
            $period_data = array('period'=>$period);
            $redis->set($program_key,json_encode($period_data));
        }else{
            $period_info = json_decode($res_period,true);
            $period = $period_info['period'];
        }

        $type = 10;//10官方活动促销(统一为优选),20我的活动,30积分兑换现金
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $fields = 'id as goods_id,media_id,name,price,is_storebuy,start_time,end_time';
        $where = array('type'=>$type,'status'=>2);
        $orderby = 'id desc';
        $res_goods = $m_goods->getDataList($fields,$where,$orderby);

        $m_media = new \Common\Model\MediaModel();
        $program_list = array();
        foreach ($res_goods as $v){
            $info = array('goods_id'=>$v['goods_id'],'chinese_name'=>$v['name'],'price'=>intval($v['price']),
                'start_date'=>'','end_date'=>'');
            $media_info = $m_media->getMediaInfoById($v['media_id']);
            $info['oss_path'] = $media_info['oss_path'];
            $name_info = pathinfo($info['oss_path']);
            $info['name'] = $name_info['basename'];
            $info['media_type'] = $media_info['type'];
            $info['md5'] = $media_info['md5'];
            $info['duration'] = $media_info['duration'];
            $info['qrcode_url'] = '';
            $info['play_type'] = 3;
            $program_list[] = $info;
        }
        $res = array('period'=>$period,'datalist'=>$program_list);
        $this->to_back($res);
    }

    public function getFindcontentProgramList(){
        $find_program_key = C('SAPP_FIND_PROGRAM');
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $res_cache = $redis->get($find_program_key);
        if(!empty($res_cache)){
            $res_cache = json_decode($res_cache,true);
            $data = array('period'=>$res_cache['period'],'datalist'=>$res_cache['datalist']);
        }else {
            $period = getMillisecond();
            $where = array('a.status'=>2,'a.res_type'=>2);
            $where['box.flag'] = 0;
            $where['box.state'] = 1;
            $where['hotel.flag'] = 0;
            $where['hotel.state'] = 1;
            $where['user.status'] = 1;
            $fields = 'a.id,a.forscreen_id,a.res_type,user.avatarUrl,user.nickName';
            $m_public = new \Common\Model\Smallapp\PublicModel();
            $findprogram_data = $m_public->getList($fields, $where,'a.id desc',200);

            $findprogram_num = 5;
            $resource_size = 1024*1024*20;
            $datalist = array();
            $m_forscreenrecord = new \Common\Model\Smallapp\ForscreenRecordModel();
            foreach ($findprogram_data as $fpv) {
                if(count($datalist)>$findprogram_num){
                    break;
                }
                $info = array('id' => $fpv['id'],'media_type'=>1,'nickName'=>$fpv['nickName'],'avatarUrl'=>$fpv['avatarUrl']);

                $where = array('forscreen_id'=>$fpv['forscreen_id']);
                $res_forscreen = $m_forscreenrecord->getWheredata('resource_id,imgs,resource_size,md5_file,duration',$where, 'id desc');

                if(!empty($res_forscreen) && !empty($res_forscreen[0]['resource_size']) && !empty($res_forscreen[0]['md5_file']) && $res_forscreen[0]['resource_size']<=$resource_size){
                    $info['duration'] = floor($res_forscreen[0]['duration']);
                    $imgs_info = json_decode($res_forscreen[0]['imgs'], true);
                    $oss_path = $imgs_info[0];
                    $name_info = pathinfo($oss_path);
                    $subdata = array(
                        array('vid'=>$res_forscreen[0]['resource_id'],'md5'=>$res_forscreen[0]['md5_file'],
                            'oss_path'=>$oss_path,'name'=>$name_info['basename'])
                    );
                    $info['subdata'] = $subdata;
                    $datalist[] = $info;
                }
            }
            $data_findprogram = array('period'=>$period, 'datalist'=>$datalist);
            $redis->set($find_program_key, json_encode($data_findprogram),86400*7);
            $data = array('period'=>$period,'datalist'=>$datalist);
        }
        $data['type'] =2;
        $this->to_back($data);
    }


    public function getSelectcontentProgramList(){//废弃
        $box_mac = $this->params['box_mac'];

        $m_box = new \Common\Model\BoxModel();
        $fileds = 'd.id as hotel_id';
        $where = array('a.mac'=>$box_mac,'a.state'=>1,'a.flag'=>0,'d.state'=>1,'d.flag'=>0);
        $res_box = $m_box->getBoxInfo($fileds,$where);
        $hotel_id = $res_box[0]['hotel_id'];
        $m_programmenu = new \Common\Model\ProgramMenuHotelModel();
        $res_menu = $m_programmenu->getLatestMenuid($hotel_id);

        $menu_id = $res_menu['menu_id'];
        $m_programitem = new \Common\Model\ProgramMenuItemModel();
        $field = 'count(id) as num';
        $where = array('menu_id'=>$menu_id,'type'=>7);
        $res_item = $m_programitem->getData($field,$where,'id desc');

        $selectcontent_num = 0;
        if(!empty($res_item)){
            $selectcontent_num = $res_item[0]['num'];
        }
        $program_list = array();

        $content_key = C('SAPP_SELECTCONTENT_CONTENT');
        $push_key = C('SAPP_SELECTCONTENT_PUSH').':ontv';
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $res_cache = $redis->get($content_key);
        if($selectcontent_num && !empty($res_cache)){
            $help_forscreen = json_decode($res_cache,true);
            if(!empty($help_forscreen)){
                $help_forscreen = array_slice($help_forscreen,0,$selectcontent_num);
                $m_config = new \Common\Model\SysConfigModel();
                $res_config = $m_config->getAllconfig();
                $play_time = intval($res_config['content_play_time'])*3600;

                $m_help = new \Common\Model\Smallapp\ForscreenHelpModel();
                $m_play = new \Common\Model\Smallapp\PlayLogModel();
                $typeinfo = C('RESOURCE_TYPEINFO');
                $redis->select(5);
                $version = isset($_SERVER['HTTP_X_VERSION'])?$_SERVER['HTTP_X_VERSION']:'';

                $m_user = new \Common\Model\Smallapp\UserModel();
                foreach ($help_forscreen as $v){
                    $info = array('id'=>$v['id'],'duration'=>floor($v['duration']));
                    $imgs_info = json_decode($v['imgs'],true);
                    $oss_path = $imgs_info[0];
                    $name_info = pathinfo($oss_path);
                    $surfix = strtolower($name_info['extension']);
                    $info['media_type'] = $typeinfo[$surfix];
                    $res_play = $m_play->getOne('create_time',array('res_id'=>$v['id'],'type'=>4),'id desc');
                    if(!empty($res_play)){
                        $create_time = $res_play['create_time'];
                    }else{
                        $create_time = date('Y-m-d H:i:s');

                        $push_data = array('openid'=>$v['openid'],'res_id'=>$v['id'],'type'=>4,'create_time'=>$create_time);
                        $redis->rpush($push_key,json_encode($push_data));
                        $add_data = array('res_id'=>$v['id'],'type'=>4,'nums'=>0,'create_time'=>$create_time);
                        $m_play->add($add_data);
                        $m_help->updateData(array('id'=>$v['help_id']),array('status'=>3));
                    }
                    $info['start_date'] = $create_time;
                    $end_date = strtotime($create_time)+$play_time;
                    $info['end_date'] = date('Y-m-d H:i:s',$end_date);

                    $subdata = array();
                    switch ($info['media_type']){
                        case 1:
                            $subdata[] = array('vid'=>$v['resource_id'],'md5'=>$v['md5_file'],'oss_path'=>$oss_path,'name'=>$name_info['basename']);
                            break;
                        case 2:
                            $info['media_type']= 21;
                            $fields = 'resource_id,imgs,md5_file';
                            $where = array('forscreen_id'=>$v['forscreen_id']);
                            $m_forscreenrecord = new \Common\Model\Smallapp\ForscreenRecordModel();
                            $res_forscreen = $m_forscreenrecord->getWheredata($fields,$where,'id asc');
                            foreach ($res_forscreen as $fv){
                                $sinfo = array('vid'=>$fv['resource_id'],'md5'=>$fv['md5_file']);
                                $tmp_imgs_info = json_decode($fv['imgs'],true);
                                $sinfo['oss_path'] = $tmp_imgs_info[0];
                                $sname_info = pathinfo($sinfo['oss_path']);
                                $sinfo['name'] = $sname_info['basename'];
                                $subdata[]=$sinfo;
                            }
                            if(count($subdata)>1){
                                $info['duration']=3;
                            }else{
                                $info['duration']=15;
                            }
                            break;
                    }
                    $info['subdata'] = $subdata;
                    $userinfo = $m_user->getOne('avatarUrl,nickName', array('openid'=>$v['openid']));
                    $info['nickName'] = $userinfo['nickName'];
                    $info['avatarUrl'] = $userinfo['avatarUrl'];
                    $program_list[] = $info;
                }

            }
        }
        $redis->select(5);
        $program_key = C('SAPP_SELECTCONTENT_PROGRAM').":$hotel_id";
        $period = $redis->get($program_key);
        if(empty($period)){
            $period = getMillisecond();
            $redis->set($program_key,$period);
        }
        $res = array('period'=>$period,'type'=>1,'datalist'=>$program_list);
        $this->to_back($res);
    }

    public function getHotPlayProgramList(){
        $box_mac = $this->params['box_mac'];
        $version = isset($_SERVER['HTTP_X_VERSION'])?$_SERVER['HTTP_X_VERSION']:'';
        $program_list = array();

        $where = array('status'=>1);
        $orderby = 'sort desc';
        $m_hotplay = new \Common\Model\Smallapp\HotplayModel();
        $res_playlog = $m_hotplay->getDataList('*',$where,$orderby,0,8);

        $m_ads = new \Common\Model\AdsModel();
        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        foreach ($res_playlog['list'] as $v){
            if($v['type']==1){
                $res_id = $v['forscreen_record_id'];
                $res_pinfo = $m_forscreen->getInfo(array('id'=>$res_id));
                $resource_type = $res_pinfo['resource_type'];//1图片 2视频
                if($resource_type==2){
                    $media_type = 1;//1视频 2图片
                }else{
                    $media_type = 2;
                }
                $info = array('id'=>$res_id,'media_type'=>$media_type);
                $subdata = array();
                switch ($info['media_type']){
                    case 1:
                        $info['duration'] = floor($res_pinfo['duration']);
                        $imgs_info = json_decode($res_pinfo['imgs'],true);
                        $oss_path = $imgs_info[0];
                        $name_info = pathinfo($oss_path);
                        $subdata[] = array('vid'=>$res_pinfo['resource_id'],'md5'=>$res_pinfo['md5_file'],'oss_path'=>$oss_path,'name'=>$name_info['basename']);
                        break;
                    case 2:
                        $info['media_type']= 21;
                        $fields = 'resource_id,imgs,md5_file';
                        $where = array('forscreen_id'=>$res_pinfo['forscreen_id']);
                        $res_allforscreen = $m_forscreen->getWheredata($fields,$where,'id asc');
                        foreach ($res_allforscreen as $fv){
                            $sinfo = array('vid'=>$fv['resource_id'],'md5'=>$fv['md5_file']);
                            $tmp_imgs_info = json_decode($fv['imgs'],true);
                            $sinfo['oss_path'] = $tmp_imgs_info[0];
                            $sname_info = pathinfo($sinfo['oss_path']);
                            $sinfo['name'] = $sname_info['basename'];
                            $subdata[]=$sinfo;
                        }
                        if(count($subdata)>1){
                            $info['duration']=3;
                        }else{
                            $info['duration']=15;
                        }
                        break;
                }
                $info['subdata'] = $subdata;
            }else{
                $res_id = $v['data_id'];
                $fields = 'a.id as ads_id,a.name title,a.type as ads_type,a.duration,b.id as media_id,b.type as media_type,b.oss_addr,b.md5 as md5_file';
                $res_ads = $m_ads->getAdsList($fields,array('a.id'=>$res_id),'a.id desc','0,1');
                $ads_info = $res_ads[0];
                $oss_path = $ads_info['oss_addr'];
                $name_info = pathinfo($oss_path);
                $subdata = array();
                $subdata[] = array('vid'=>$res_id,'md5'=>$ads_info['md5_file'],'oss_path'=>$oss_path,'name'=>$name_info['basename']);
                $info = array('id'=>$res_id,'media_type'=>1,'duration'=>floor($ads_info['duration']),'subdata'=>$subdata);
            }
            $program_list[] = $info;
        }

        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $program_key = C('SAPP_HOTPLAYDEMAND');
        $period = $redis->get($program_key);
        if(empty($period)){
            $period = getMillisecond();
            $redis->set($program_key,$period);
        }
        $res = array('period'=>$period,'type'=>3,'datalist'=>$program_list);
        $this->to_back($res);
    }

    public function getWelcomeResource(){
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $program_key = C('SAPP_SALE_WELCOME_RESOURCE');
        $period = $redis->get($program_key);
        if(empty($period)){
            $period = getMillisecond();
            $redis->set($program_key,$period);
        }
        $m_welcomeresource = new \Common\Model\Smallapp\WelcomeresourceModel();
        $fields = 'id,name,media_id,type';
        $where = array('status'=>1);

        $version = isset($_SERVER['HTTP_X_VERSION'])?$_SERVER['HTTP_X_VERSION']:'';
        if($version>=2019123101){
            $where['type'] = array('in',array(3,4,5));
        }else{
            $where['type'] = array('in',array(3,4));
        }

        $res_resource = $m_welcomeresource->getDataList($fields,$where,'id asc');
        $data_list = array();
        if(!empty($res_resource)){
            $m_media = new \Common\Model\MediaModel();
            foreach ($res_resource as $v){
                $res_media = $m_media->getMediaInfoById($v['media_id']);
                $oss_path = $res_media['oss_path'];
                $sname_info = pathinfo($oss_path);
                $file_name = $sname_info['basename'];
                $media_type = $res_media['type'];
                $data_list[]=array('id'=>$v['id'],'chinese_name'=>$v['name'],'name'=>$file_name,'oss_path'=>$oss_path,'md5'=>$res_media['md5'],
                    'type'=>$v['type'],'media_type'=>$media_type);
            }
        }

        $res = array('period'=>$period,'datalist'=>$data_list);
        $this->to_back($res);
    }
}