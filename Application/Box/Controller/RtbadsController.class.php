<?php
namespace Box\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use Common\Lib\UmengNotice;
use \Common\Controller\CommonController as CommonController;
class RtbadsController extends CommonController{ 
    private $production_mode ;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'pushToBox':
                $this->is_verify = 1;
                $this->valid_fields = array('probe'=>'1001');
                break;
           
        }
        parent::_init_();
        $this->production_mode =  C('UMENG_PRODUCTION_MODE');
    }
    public function pushToBox(){
        $probe = $this->params['probe'];
        $rtb_ads_config_arr =  C('RTB_ADS_CONFIG_ARR');
        $rtb_ads_config_arr = $rtb_ads_config_arr['hotel_meal_time']; //推送时间
        $now_time = date('H:i:s');
        $remain_time = 0;
        
        
        if($now_time>=$rtb_ads_config_arr['lunch_time']['start_time'] && $now_time<$rtb_ads_config_arr['lunch_time']['end_time']){
            $end_time = strtotime(date('Y-m-d').' '.$rtb_ads_config_arr['lunch_time']['end_time']);
            $remain_time = $end_time - time();       
        }
        if($now_time>=$rtb_ads_config_arr['dinner_time']['start_time'] && $now_time<$rtb_ads_config_arr['dinner_time']['end_time']){
            $end_time = strtotime(date('Y-m-d').' '.$rtb_ads_config_arr['dinner_time']['end_time']);
            $remain_time = $end_time-time();
        }
        if(empty($remain_time)){
            $this->to_back(80002);
        }
        
        $m_room = new \Common\Model\RoomModel();
        $m_box = new \Common\Model\BoxModel();
        $device_tokens = '';
        //foreach($probe as $key=>$v){
        $where = array();
        $tmp = explode(':', $probe);
        $probe_str = $tmp[2];
        $where['probe'] = $probe_str;
        $where['flag']  = 0;
        $room_info = $m_room->getOne('id,hotel_id',$where);
        if(empty($room_info)){
            continue;
        }
        //获取包间下的机顶盒
        
        $where = array();
        $where['room_id'] = $room_info['id'];
        $where['flag']    = '0';
        $where['state']   = 1;
        $where['device_token'] = array('neq','');
        
        $box_list = $m_box->field('id,device_token')->where($where)->select();
        //print_r($box_list);exit;
        $space = '';
        $push_list = array();
        
        $redis = new SavorRedis();
        $redis->select(11);
        $cacke_key = $probe;
        $ads_list = $redis->get($cacke_key);
        //
        if(empty($ads_list)){
            $this->to_back(80003);
        }
        foreach($box_list as $ks=>$vs){
            if(!empty($vs['device_token'])){
                $device_tokens  .= $space . $vs['device_token'];
                $space = ",";
                $tmp = array();
                $tmp['hotel_id']  = $room_info['hotel_id'];
                $tmp['room_id']   = $room_info['id'];
                $tmp['box_id']    = $vs['id'];
                $tmp['push_info'] = $ads_list;
                $tmp['push_time'] = date('Y-m-d H:i:s');
                $tmp['push_type'] = 1;
                $push_list[] = $tmp;
            }
        }
        if(empty($device_tokens)){
            $this->to_back(70005);
        }
        
        $data = array();
        $ads_list = json_decode($ads_list,true);
        
        foreach($ads_list as $key=>$v){
            $ads_list[$key]['remain_time'] = $remain_time;
        }
        
        $data =  $ads_list;
        
        $custom = array();
        $custom['type'] = 1;
        $custom['data'] = $data;
        $obj = new UmengNotice();
        $type = 'listcast';
        $listcast = $obj->umeng_android($type);
        //设置属于哪个app
        $config_parm = 'boxclient';
        //设置app打开后选项
        $after_a = C('AFTER_APP');
        $listcast->setParam($config_parm);
        //$pam['device_tokens'] = 'AqWNvmADF_1bqndJXoPF6ZqPBSNz--iRzfGQMy-E_n9P,AtBHUz8wGEqACpVAX8iZ5m1O-HkiWvqFviS09x8aYd6A';
        $pam['device_tokens'] = $device_tokens;
        $pam['time'] = time();
        $pam['ticker'] = 'RTB广告推送';
        $pam['title'] = 'RTB广告推送';
        $pam['text'] = 'RTB广告推送';
        $pam['after_open'] = $after_a[3];
        $pam['production_mode'] = $this->production_mode;
        $pam['display_type'] = 'notification';
        
        $pam['custom'] = json_encode($custom);
        $listcast->sendAndroidListcast($pam);
        $m_push_log = new \Common\Model\PushLogModel(); 
        $m_push_log->addInfo($push_list,2);
        $this->to_back(10000);
    }
}