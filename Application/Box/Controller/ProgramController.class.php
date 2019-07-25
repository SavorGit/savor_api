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
        $box_info = $m_box->getBoxInfo('a.id as box_id,d.id as hotel_id', $map);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $hotel_id = $box_info[0]['hotel_id'];
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $program_key = C('SAPP_DINNER_ACTIVITYGOODS_PROGRAM').":$hotel_id";;
        $res_period = $redis->get($program_key);
        if(empty($res_period)){
            $period = getMillisecond();
            $period_data = array('period'=>$period);
            $redis->set($program_key,json_encode($period_data));
        }else{
            $period_info = json_decode($res_period,true);
            $period = $period_info['period'];
        }

        $cache_key = C('SAPP_DINNER').'activitygoods:loopplay:'.$hotel_id;
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
        $fields = 'g.id as goods_id,g.media_id,g.name,g.price,g.start_time,g.end_time';
        $where = array('h.hotel_id'=>$hotel_id,'g.status'=>2);
        $orderby = 'g.id desc';
        $limit = "0,10";
        $res_goods = $m_hotelgoods->getList($fields,$where,$orderby,$limit);
        $program_list = array();
        $host_name = C('HOST_NAME');
        $m_media = new \Common\Model\MediaModel();
        foreach ($res_goods as $v){
            $info = array('goods_id'=>$v['goods_id'],'chinese_name'=>$v['name'],'price'=>$v['price'],
                'start_date'=>$v['start_time'],'end_date'=>$v['end_time'],'duration'=>15);
            $media_info = $m_media->getMediaInfoById($v['media_id']);
            $info['oss_path'] = $media_info['oss_path'];
            $name_info = pathinfo($info['oss_path']);
            $info['name'] = $name_info['basename'];
            $info['media_type'] = $media_info['type'];
            $info['md5'] = $media_info['md5'];;
            $info['qrcode_url'] = $host_name."/smalldinnerapp11/qrcode/getBoxQrcode?box_mac=$box_mac&goods_id={$v['goods_id']}&type=1";
            if(isset($loopplay_data[$v['goods_id']])){
                $info['play_type'] = 1;
            }else{
                $info['play_type'] = 2;
            }
            $program_list[] = $info;
        }
        $res = array('period'=>$period,'datalist'=>$program_list);
        $this->to_back($res);
    }
}