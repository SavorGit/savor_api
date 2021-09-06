<?php
namespace Box\Controller;
use \Common\Controller\CommonController as CommonController;
class DownloadController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getLanip':
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
            case 'report':
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1001,'status'=>1001);
                break;
        }
        parent::_init_(); 
    }
    public function getLanip(){
        $box_mac = $this->params['box_mac'];

        $m_box = new \Common\Model\BoxModel();
        $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);
        $redis = new \Common\Lib\SavorRedis();
        $hotel_id = $is_5g = 0;
        $lan_ip = '';
        if(isset($forscreen_info['box_id']) && $forscreen_info['box_id']>0){
            $redis->select(15);
            $cache_key = 'savor_box_'.$forscreen_info['box_id'];
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info,true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $cache_key = 'savor_hotel_' . $room_info['hotel_id'];
            $redis_hotel_info = $redis->get($cache_key);
            $res_hotel = json_decode($redis_hotel_info, true);
            $hotel_id = $room_info['hotel_id'];
            $is_5g = $res_hotel['is_5g'];
            $lan_ip = $box_info['lanip'];
        }
        if(empty($hotel_id)){
            $where = array('a.mac'=>$box_mac,'a.state'=>1,'a.flag'=>0,'d.state'=>1,'d.flag'=>0);
            $rets = $m_box->getBoxInfo('d.id hotel_id,d.is_5g,a.lanip',$where);
            $hotel_id = $rets[0]['hotel_id'];
            $is_5g = $rets[0]['is_5g'];
            $lan_ip = $rets[0]['lanip'];
        }
        if($is_5g!=1 || empty($lan_ip)){//非5G酒楼或机顶盒无WAN口IP 正常下载
            $resp_data = array('type'=>1,'lan_ip'=>'','lan_mac'=>'');
            $this->to_back($resp_data);
        }

        $redis->select(21);
        $queue_key = C('BOX_LANHOTEL_DOWNLOADQUEUE');
        $download_cache_key = C('BOX_LANHOTEL_DOWNLOAD').$hotel_id;
        $res_download = $redis->get($download_cache_key);
        if(!empty($res_download)){
            /*
            1正在下载 2下载成功
            $all_download = array(
                '00226D583D92'=>array('status'=>1,'start_time'=>'','end_time'=>'','from_box'=>''),
            );
            */
            $all_download = json_decode($res_download,true);
        }else{
            $all_download = array();
        }
        if(isset($all_download[$box_mac])){
            if($all_download[$box_mac]['status']==1){
                $code = 70007;
            }else{
                $code = 70008;
            }
            $this->to_back($code);
        }else{
            $had_download_online_box = array();
            $downing_box = array();
            $redis->select(13);
            foreach ($all_download as $k=>$v){
                if($v['status']==2){
                    $heart_key = "heartbeat:2:$k";
                    $res_heart = $redis->get($heart_key);
                    if(!empty($res_heart)){
                        $heart_info = json_decode($res_heart,true);
                        $heart_diff_time = time() - strtotime($heart_info['date']);
                        if($heart_diff_time<600){
                            $had_download_online_box[]=$k;
                        }
                    }
                }else{
                    $downing_box[]=$k;
                }
            }
            $redis->select(21);
            if(!empty($had_download_online_box)){
                $max_box_download_num = 1;
                $usable_box = array();
                foreach ($had_download_online_box as $box){
                    $download_queuecache_key = $queue_key."$hotel_id:$box";
                    $download_queuecache = $redis->lgetrange($download_queuecache_key,0,100);
                    if(!empty($download_queuecache)){
                        $box_now_download_num = count($download_queuecache);
                    }else{
                        $box_now_download_num = 0;
                    }
                    if($box_now_download_num<$max_box_download_num){
                        $usable_box[$box] = $box_now_download_num;
                    }
                }
                if(!empty($usable_box)){
                    asort($usable_box);
                    $tmp_boxs = array_keys($usable_box);
                    $lan_box = $tmp_boxs[0];

                    $download_queuecache_key = $queue_key."$hotel_id:$lan_box";
                    $redis->rpush($download_queuecache_key,$box_mac);

                    $all_download[$box_mac] = array('status'=>1,'start_time'=>date('Y-m-d H:i:s'),'end_time'=>'','from_box'=>$lan_box);
                    $redis->set($download_cache_key,json_encode($all_download),86400*14);

                    $forscreen_info = $m_box->checkForscreenTypeByMac($lan_box);
                    $redis->select(15);
                    $cache_key = 'savor_box_' . $forscreen_info['box_id'];
                    $redis_box_info = $redis->get($cache_key);
                    $box_info = json_decode($redis_box_info, true);
                    $lan_box_ip = $box_info['lanip'];
                    $resp_data = array('type'=>2,'lan_ip'=>$lan_box_ip,'lan_mac'=>$lan_box);
                    $this->to_back($resp_data);
                }else{
                    $this->to_back(70009);
                }
            }else{
                if(!empty($downing_box)){
                    $this->to_back(70009);
                }else{
                    $all_download[$box_mac] = array('status'=>1,'start_time'=>date('Y-m-d H:i:s'),'end_time'=>'','from_box'=>'');
                    $redis->set($download_cache_key,json_encode($all_download),86400*14);
                    $resp_data = array('type'=>1,'lan_ip'=>'','lan_mac'=>'');
                    $this->to_back($resp_data);
                }
            }
        }
    }

    public function report(){
        $box_mac = $this->params['box_mac'];
        $status = $this->params['status'];//1下载成功 2下载超时 3下载失败

        $m_box = new \Common\Model\BoxModel();
        $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);
        $redis = new \Common\Lib\SavorRedis();
        $hotel_id = 0;
        if(isset($forscreen_info['box_id']) && $forscreen_info['box_id']>0){
            $redis->select(15);
            $cache_key = 'savor_box_'.$forscreen_info['box_id'];
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info,true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $cache_key = 'savor_hotel_' . $room_info['hotel_id'];
            $redis_hotel_info = $redis->get($cache_key);
            $res_hotel = json_decode($redis_hotel_info, true);
            $hotel_id = $room_info['hotel_id'];
        }
        if(empty($hotel_id)){
            $where = array('a.mac'=>$box_mac,'a.state'=>1,'a.flag'=>0,'d.state'=>1,'d.flag'=>0);
            $rets = $m_box->getBoxInfo('d.id hotel_id,d.is_5g',$where);
            $hotel_id = $rets[0]['hotel_id'];
        }

        $redis = new \Common\Lib\SavorRedis();
        $redis->select(21);
        $download_cache_key = C('BOX_LANHOTEL_DOWNLOAD').$hotel_id;
        $res_download = $redis->get($download_cache_key);
        if(!empty($res_download)){
            $download_info = json_decode($res_download,true);
            if(isset($download_info[$box_mac])){
                $queue_key = C('BOX_LANHOTEL_DOWNLOADQUEUE');
                $fail_key = C('BOX_LANHOTEL_DOWNLOAD_FAIL');
                switch ($status){//1下载成功 2下载超时 3下载失败
                    case 1:
                        $download_info[$box_mac]['status'] = 2;
                        $download_info[$box_mac]['end_time'] = date('Y-m-d H:i:s');
                        $redis->set($download_cache_key,json_encode($download_info),86400*14);
                        break;
                    case 2:
                    case 3:
                        $lan_box = $download_info[$box_mac]['from_box'];
                        $download_queuecache_key = $queue_key."$hotel_id:$lan_box";
                        $redis->lrem($download_queuecache_key,$box_mac,0);

                        $res_fail = $redis->get($fail_key.$hotel_id);
                        if(!empty($res_fail)){
                            $fail_info = json_decode($res_fail,true);
                        }else{
                            $fail_info = array();
                        }
                        $download_info[$box_mac]['status'] = $status;
                        $fail_info[$box_mac] = $download_info[$box_mac];
                        $redis->set($fail_key.$hotel_id,json_encode($fail_info),86400*14);

                        unset($download_info[$box_mac]);
                        $redis->set($download_cache_key,json_encode($download_info),86400*14);
                        break;
                }
            }
        }
        $this->to_back(array());
    }

}