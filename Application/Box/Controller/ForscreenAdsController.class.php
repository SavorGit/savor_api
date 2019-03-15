<?php
namespace Box\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;
class ForscreenAdsController extends CommonController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getAdsList':
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1001,'versionCode'=>1000);
                break;
        }
        parent::_init_(); 
    }
    public function getAdsList(){
        $box_mac = $this->params['box_mac'];
        $m_box = new \Common\Model\BoxModel();
        $where = array();
        $where['a.mac']   = $box_mac;
        $where['a.state'] = 1;
        $where['a.flag']  = 0;
        $where['d.state'] = 1;
        $where['d.flag']  = 0;
        $fields = "a.id box_id";
        $box_info = $m_box->getBoxInfo($fields,$where);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $box_id = $box_info[0]['box_id'];
        
        $cache_key = C('SMALLAPP_FORSCREEN_ADS').$box_id;
        $redis = SavorRedis::getInstance();
        $redis->select(12);
        $list = $redis->get($cache_key);
        
        if(!empty($list)){
            $data = json_decode($list,true);
            $this->to_back($data);
        }else {
            $now_date = date('Y-m-d H:i:s');
            $m_forscreen_ads_box = new \Common\Model\Smallapp\ForscreenAdsBoxModel(); 
            $fields = "media.id vid,substring(media.oss_addr,16) name ,media.md5,ads.name as chinese_name,
                       'forscreen' as `type`,media.oss_addr AS oss_path,media.duration AS duration,
                       media.surfix AS suffix,fads.start_date,fads.end_date,ads.resource_type media_type,fads.play_position";
            $where = array();
            $where['a.box_id'] = $box_id;
            $where['fads.state']= 2;
            $where['fads.start_date'] = array('ELT',$now_date);
            $where['fads.end_date']   = array('EGT',$now_date);
            $order = "fads.id asc";
            $list = $m_forscreen_ads_box->getList($fields, $where, $order);
            
            if(empty($list)){
                $this->to_back(10000);
            }
            $m_forscreen_ads = new \Common\Model\Smallapp\ForscreenAdsModel();
            $fields = 'update_time';
            $where  = array();
            $where['state'] = 2;
            $where['start_date'] = array('ELT',$now_date);
            $where['end_date']   = array('EGT',$now_date);
            $order = 'update_time desc';
            $info = $m_forscreen_ads->getOne($fields, $where, $order);
            $proid = strtotime($info['update_time']) ;
            $data = array();
            $data['period'] = $proid;
            $data['media_list'] = $list;
            $redis->set($cache_key, json_encode($data),86400);
            $this->to_back($data);
            
        }
    }
}