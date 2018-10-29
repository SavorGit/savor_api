<?php
/**
 * @desc   机顶盒节目单
 * @author zhang.yingtao
 * @since  2018-08-21 
 */
namespace Smallapp21\Controller;
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
            $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'page'=>1001);
            break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取该机顶盒下的节目单列表 在小程序中展示
     */
    public function getBoxProgramList(){
        $openid  = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $page    = $this->params['page'] ? $this->params['page'] :1;
        $pagesize = 10;
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
          /*if($v['media_id']==17614) {
              unset($play_info[$key]);
              continue;
          }*/ 
          $map = array();
          if($v['type']=='pro' && $v['media_id'] !=17614){//节目
              $map['a.media_id']= $v['media_id'];
              $map['a.type']    = 2;
              $media_info = $m_ads->getAdsList('a.id,a.name title,a.img_url,a.duration,b.oss_addr tx_url', $map,'',' limit 1');
              
              if(!empty($media_info)){
                  $media_info = $media_info[0];
                  $media_info['img_url'] = $media_info['img_url']? $media_info['img_url'] :'media/resource/EDBAEDArdh.png';
                  $media_info['img_url'] = $oss_host.$media_info['img_url'];
                  $res_arr = explode('/', $media_info['tx_url']);
                  $media_info['filename'] = $res_arr[2];
                  $media_info['tx_url']= $oss_host.$media_info['tx_url'];
                  
                  $media_info['duration']= secToMinSec($media_info['duration']); 
                  $media_info['type'] = '3'; 
                  
                  //获取是否收藏、分享个数、收藏个数
                  $rets = $this->getPubShareInfo($openid,$media_info['id'],3);
                  $media_info['is_collect'] = $rets['is_collect'];
                  $media_info['collect_num']= $rets['collect_num'];
                  $media_info['share_num']  = $rets['share_num'];
                  
                  $data[$flag] = $media_info;
                  $flag++;
              }
          }else if($v['type']=='adv'){//宣传片
              $map['a.media_id']= $v['media_id'];
              $map['a.type']    = 3;
              $media_info = $m_ads->getAdsList('a.id ,a.name title,a.img_url,a.duration,b.oss_addr tx_url', $map,'',' limit 1');
              
              if(!empty($media_info)){
                  $media_info = $media_info[0];
                  $media_info['img_url'] = $media_info['img_url']? $media_info['img_url'] :'media/resource/EDBAEDArdh.png';
                  $media_info['img_url'] = $oss_host.$media_info['img_url'];
                  $res_arr = explode('/', $media_info['tx_url']);
                  $media_info['filename'] = $res_arr[2];
                  $media_info['tx_url']= $oss_host.$media_info['tx_url'];
                  
                  $media_info['duration']= secToMinSec($media_info['duration']);
                  $media_info['type'] = '3';
                  //获取是否收藏、分享个数、收藏个数
                  $rets = $this->getPubShareInfo($openid,$media_info['id'],3);
                  $media_info['is_collect'] = $rets['is_collect'];
                  $media_info['collect_num']= $rets['collect_num'];
                  $media_info['share_num']  = $rets['share_num'];
                  $data[$flag] = $media_info;
                  $flag++;
              }
          }else if($v['type']=='ads'){//广告
              $map['ads.media_id'] = $v['media_id'];
              $map['ads.type']     = '1';
             
              $m_pub_ads = new \Common\Model\PubAdsModel();
              $media_info = $m_pub_ads->getPubAdsInfo('ads.id,ads.name title,mda.oss_addr img_url,ads.duration,med.oss_addr tx_url', $map);
              if(!empty($media_info)){
                  $media_info['img_url'] = $media_info['img_url']? $media_info['img_url'] :'media/resource/EDBAEDArdh.png';
                  $media_info['img_url'] = $oss_host.$media_info['img_url'];
                  $res_arr = explode('/', $media_info['tx_url']);
                  $media_info['filename'] = $res_arr[2];
                  $media_info['tx_url']= $oss_host.$media_info['tx_url'];
                  
                  $media_info['duration']= secToMinSec($media_info['duration']);
                  $media_info['type'] = '3';
                  //获取是否收藏、分享个数、收藏个数
                  $rets = $this->getPubShareInfo($openid,$media_info['id'],3);
                  $media_info['is_collect'] = $rets['is_collect'];
                  $media_info['collect_num']= $rets['collect_num'];
                  $media_info['share_num']  = $rets['share_num'];
                  $data[$flag] = $media_info;
                  $flag++;
              }
          } 
        }
        $this->to_back($data);
    }
    private function getPubShareInfo($openid,$ads_id,$type){
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $map = array();
        $map['openid']=$openid;
        $map['res_id'] =$ads_id;
        $map['type']   = $type;
        $map['status'] = 1;
        $is_collect = $m_collect->countNum($map);
        if(empty($is_collect)){
            $data['is_collect'] = 0;
        }else {
            $data['is_collect'] = 1;
        }
        $map = array();
        $map['res_id'] =$ads_id;
        $map['type']   = $type;
        $map['status'] = 1;
        $collect_num = $m_collect->countNum($map);
        $data['collect_num'] = $collect_num;
        //分享个数
        $map = array();
        $map['res_id'] =$ads_id;
        $map['type']   = $type;
        $map['status'] = 1;
        $share_num = $m_share->countNum($map);
        $data['share_num'] = $share_num;
        return $data;
    }
}
            
