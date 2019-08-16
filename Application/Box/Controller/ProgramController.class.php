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
            case 'getSelectcontentProgramList':
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
            $redis->set($cache_key, $resource_info);
            $this->to_back(10000);
        }else {
            $this->to_back(30073);
        }  
    }

    public function getActivitygoodsProgramList(){
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
        $fields = 'g.id as goods_id,g.media_id,g.name,g.price,g.start_time,g.end_time,g.type,g.scope';
        $where = array('h.hotel_id'=>$hotel_id,'g.status'=>2);
        $where['g.end_time'] = array('egt',$nowtime);
        $where['g.start_time'] = array('elt',$nowtime);
        $orderby = 'g.id desc';
        $limit = "0,10";
        $res_goods = $m_hotelgoods->getList($fields,$where,$orderby,$limit);
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

    public function getSelectcontentProgramList(){
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
                foreach ($help_forscreen as $v){
                    $info = array('vid'=>$v['id'],'chinese_name'=>'','duration'=>floor($v['duration']),'md5'=>$v['md5_file']);
                    $imgs_info = json_decode($v['imgs'],true);
                    $info['oss_path'] = $imgs_info[0];
                    $name_info = pathinfo($info['oss_path']);
                    $surfix = strtolower($name_info['extension']);
                    $info['media_type'] = $typeinfo[$surfix];
                    $info['name'] = $name_info['basename'];
                    if($info['media_type']==2 && $info['duration']==0){
                        $info['duration']=15;
                    }
                    $res_play = $m_play->getOne('create_time',array('res_id'=>$v['id'],'type'=>4),'id desc');
                    if(!empty($res_play)){
                        $create_time = $res_play['create_time'];
                    }else{
                        $redis->rpush($push_key,$v['openid']);
                        $create_time = date('Y-m-d H:i:s');
                        $add_data = array('res_id'=>$v['id'],'type'=>4,'nums'=>0,'create_time'=>$create_time);
                        $m_play->add($add_data);
                        $m_help->updateData(array('id'=>$v['help_id']),array('status'=>3));
                    }
                    $info['start_date'] = $create_time;
                    $end_date = strtotime($create_time)+$play_time;
                    $info['end_date'] = date('Y-m-d H:i:s',$end_date);
                    $program_list[] = $info;
                }
            }
            $redis->select(5);
            $program_key = C('SAPP_SELECTCONTENT_PROGRAM').":$hotel_id";
            $period = $redis->get($program_key);
            if(empty($period)){
                $period = getMillisecond();
                $redis->set($program_key,$period);
            }
        }
        $res = array('period'=>$period,'datalist'=>$program_list);
        $this->to_back($res);
    }
}