<?php
/**
 * @desc   机顶盒节目单
 * @author zhang.yingtao
 * @since  2018-08-21 
 */
namespace Smallapp\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class BoxProgramController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        
        switch(ACTION_NAME) {
            
            case 'getBoxProgramList':    //获取该机顶盒下的节目单列表 在小程序中展示
            $this->is_verify =1;
            $this->valid_fields = array('box_mac'=>1001,'page'=>1001);
            break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取该机顶盒下的节目单列表 在小程序中展示
     */
    public function getBoxProgramList(){
        $box_mac = $this->params['box_mac'];
        $page    = $this->params['page'] ? $this->params['page'] :1;
        $pagesize = 5;
        $m_box = new \Common\Model\BoxModel();
        $where = array();
        $where['mac'] = $box_mac;
        $where['flag'] = 0;
        $where['state']= 1;
        $box_info = $m_box->where($where)->count();
        if(empty($box_info)){
            $this->to_back(15003);
        }
        $redis = SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = 'box:play:'.$box_mac;
        $play_info = $redis->get($cache_key);
        if(empty($play_info)){
            $this->to_back(30115);
        }
        $play_info = json_decode($play_info,true);
        $play_info = $play_info['list'];
        $data = array();
        $flag = 0;
        $m_ads = new \Common\Model\AdsModel();
        $oss_host = $this->getOssAddr();
        $offset = $page*$pagesize;
        //print_r($play_info);exit;
        $play_info = array_slice($play_info, 0,$offset);
        
        foreach($play_info as $key=>$v){
          $map = array();
          if($v['type']=='pro'){//节目
              $map['a.media_id']= $v['media_id'];
              $map['a.type']    = 2;
              $media_info = $m_ads->getAdsList('a.name,a.img_url,a.duration,b.oss_addr', $map,'',' limit 1');
              
              if(!empty($media_info)){
                  $media_info = $media_info[0];
                  $media_info['img_url'] = $media_info['img_url']? $media_info['img_url'] :'media/resource/NRsrwrhift.png';
                  $media_info['img_url'] = $oss_host.$media_info['img_url'];
                  $media_info['oss_addr']= $oss_host.$media_info['oss_addr'];
                  $media_info['duration']= secToMinSec($media_info['duration']); 
                  $media_info['type'] = 'pro';  
                  $data[$flag] = $media_info;
                  $flag++;
              }
          }else if($v['type']=='adv'){//宣传片
              $map['a.media_id']= $v['media_id'];
              $map['a.type']    = 3;
              $media_info = $m_ads->getAdsList('a.name,a.img_url,a.duration,b.oss_addr', $map,'',' limit 1');
              
              if(!empty($media_info)){
                  $media_info = $media_info[0];
                  $media_info['img_url'] = $media_info['img_url']? $media_info['img_url'] :'media/resource/NRsrwrhift.png';
                  $media_info['img_url'] = $oss_host.$media_info['img_url'];
                  $media_info['oss_addr']= $oss_host.$media_info['oss_addr'];
                  $media_info['duration']= secToMinSec($media_info['duration']);
                  $media_info['type'] = 'adv';
                  $data[$flag] = $media_info;
                  $flag++;
              }
          }else if($v['type']=='ads'){//广告
              $map['ads.media_id'] = $v['media_id'];
              $map['ads.type']     = '1';
             
              $m_pub_ads = new \Common\Model\PubAdsModel();
              $media_info = $m_pub_ads->getPubAdsInfo('ads.name,mda.oss_addr img_url,ads.duration,med.oss_addr', $map);
              if(!empty($media_info)){
                  $media_info['img_url'] = $media_info['img_url']? $media_info['img_url'] :'media/resource/NRsrwrhift.png';
                  $media_info['img_url'] = $oss_host.$media_info['img_url'];
                  $media_info['oss_addr']= $oss_host.$media_info['oss_addr'];
                  $media_info['duration']= secToMinSec($media_info['duration']);
                  $media_info['type'] = 'ads';
                  $data[$flag] = $media_info;
                  $flag++;
              }
          } 
        }
        $this->to_back($data);
    }
}
            
