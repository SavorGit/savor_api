<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController;
use \Common\Lib\SavorRedis;
class AdspositionController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getAdspositionList':
                $this->is_verify = 1;
                $this->valid_fields = array('position'=>1001,'box_id'=>1002);
                break;
        }
        parent::_init_();
    }

    /**
     * @desc 获取广告位列表
     */
    public function getAdspositionList(){
        $position = $this->params['position'];
        $box_id = $this->params['box_id'];
        if(empty($box_id)){
            $result = $this->getAdList($position);
            
        }else {
            $redis = SavorRedis::getInstance();
            $redis->select(15);
            $cache_key = 'savor_box_'.$box_id;
            $box_info = $redis->get($cache_key);
            $box_info = json_decode($box_info,true);
            $cache_key = 'savor_room_'.$box_info['room_id'];
            $room_info = $redis->get($cache_key);
            $room_info = json_decode($room_info,true);
            
            //从缓存中读取抽奖的酒楼
            $redis->select(1);
            $cache_key = 'smallapp:activity:kingmealhotel';
            $hotel_list = $redis->get($cache_key);
            $hotel_list = json_decode($hotel_list,true);
            $hotel_arr = [];
            foreach($hotel_list as $key=>$v){
                $hotel_arr[] = $key;
            }
            
            $start_time = $hotel_list[$room_info['hotel_id']][0]['start_time'];
            $end_time = $hotel_list[$room_info['hotel_id']][0]['end_time'];
            
            $now_time = date('Y-m-d H:i:s');
            if(in_array($room_info['hotel_id'],$hotel_arr)&& $now_time>=$start_time && $now_time<$end_time){
                $info = [];
                $info['appid'] = '';
                $info['bindtap'] = 'gotoActivity';
                $info['clicktype'] = 2;
                $info['id'] = 999;
                $info['linkcontent'] = '/games/pages/activity/din_dash';
                $info['name'] = '霸王餐';
                $info['oss_addr'] = 'http://oss.littlehotspot.com/media/resource/5b5ks2pdzt.jpg';
                $info['position'] = 2;
                $result[2][]= $info;
            }else {
                $result = $this->getAdList($position);
            }
        }
        
        $this->to_back($result);
        
    }
    private function getAdList($position){
        $redis = SavorRedis::getInstance();
        $redis->select(1);
        $cache_key = 'smallapp:adsposition:'.$position;
        $result = $redis->get($cache_key);
        if(empty($result)){
            $fields = 'id,name,media_id,linkcontent,clicktype,appid,position,bindtap';
            $where = array('status'=>1);
            $orderby = 'sort desc,id desc';
            $m_adsposition = new \Common\Model\Smallapp\AdspositionModel();
            
            $result = array();
            if($position && strstr($position, ',')){
            
                $where['position'] = array('in',$position);
                $res_positions = $m_adsposition->getDataList($fields,$where,$orderby);
                if(!empty($res_positions)){
                    $m_media = new \Common\Model\MediaModel();
                    foreach ($res_positions as $k=>$v){
                        $res_media = $m_media->getMediaInfoById($v['media_id']);
                        $v['oss_addr'] = $res_media['oss_addr'];
                        unset($v['media_id']);
                        $result[$v['position']][] = $v;
                    }
                }
            }else {
                $where['position'] = $position;
                $order =" a.order desc";
            
                $res_positions = $m_adsposition->getDataList($fields,$where,$orderby);
            
                if(!empty($res_positions)){
                    $m_media = new \Common\Model\MediaModel();
                    foreach ($res_positions as $k=>$v){
                        $res_media = $m_media->getMediaInfoById($v['media_id']);
                        $v['oss_addr'] = $res_media['oss_addr'];
                        unset($v['media_id']);
                        $result[] = $v;
                    }
                }
            
            } 
            if(!empty($result)){
                $redis->set($cache_key, json_encode($result),14400);
            }
        }else {
            $result = json_decode($result,true);
        }
        
        return $result;
    }
}
