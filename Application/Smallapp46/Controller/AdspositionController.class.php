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
            $hotel_id = $room_info['hotel_id'];
            $m_activity = new \Common\Model\Smallapp\ActivityModel();
            $start_time = date('Y-m-d 00:00:00');
            $end_time = date('Y-m-d 23:59:59');
            $where = array('hotel_id'=>$hotel_id,'status'=>array('in',array('1','0')));
            $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
            $res_activity = $m_activity->getDataList('*',$where,'id asc');
            $is_act_time = 0;
            if(!empty($res_activity)){
                $now_time = date('Y-m-d H:i:s');
                foreach ($res_activity as $v){
                    if($now_time>=$v['start_time'] && $now_time<$v['end_time']){
                        $is_act_time = 1;
                        break;
                    }
                }
            }
            if($is_act_time==1){
                $info = array();
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
