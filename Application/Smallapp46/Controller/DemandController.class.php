<?php
/**
 * @desc   小程序3.0首页列表
 * @author zhang.yingtao
 * @since  2019-01-03
 */
namespace Smallapp46\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class DemandController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getDemanList': //用户未连接盒子之前获取点播列表
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001,'openid'=>1000);
            break;
            case 'getBoxProgramList':    //获取该机顶盒下的节目单列表 在小程序中展示
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'page'=>1001);
            break;
            case 'getVideoInfo':         //获取节目单视频详情
                $this->is_verify = 1;
                $this->valid_fields = array('res_id'=>1001,'openid'=>1001);
                break;
        }
        parent::_init_();
    }
    /**
     * @用户未连接盒子之前获取点播列表
     */
    public function getDemanList(){
        $oss_host = get_oss_host();
        $pagesize = 10;
        $page = $this->params['page'] ?  $this->params['page']: 1;
        $openid = $this->params['openid'] ;
        
        //获取最新一期设置为小程序的节目单
        $m_program_list =  new \Common\Model\ProgramMenuListModel();
        $fields = "";
        $where  = array();
        $where['is_small_app'] = 1;
        $order = " id desc";
        $program_info = $m_program_list->getInfo('id', $where, $order);
        $menu_id = $program_info['id'];
        
        $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
        
        $fields = 'ads.id,ads.name title,ads.img_url,ads.duration,media.oss_addr tx_url';
        $where = array();
        $where['a.menu_id'] = $menu_id;
        $where['a.type']    = 2;
        $where['media.id']  = array('not in',array('17614','19533')) ;
        $where['media.type'] = 1;
        //$where['media.id']     = array('neq',17614);
        $order  = ' a.sort_num asc';
        $menu_item_arr = $m_program_menu_item->alias('a')
        ->join('savor_ads ads on a.ads_id = ads.id','left')
        ->join('savor_media media on ads.media_id =media.id ','left')
        ->field($fields)
        ->where($where)
        ->order($order)
        ->select();
        
        $offset = $page*$pagesize;
        $menu_item_arr = array_slice($menu_item_arr, 0,$offset);
        
        foreach($menu_item_arr as $key=>$v){
            $v['img_url'] = $v['img_url']? $v['img_url'] :'media/resource/EDBAEDArdh.png';
            $menu_item_arr[$key]['img_url'] = $oss_host.$v['img_url'];
            $res_arr = explode('/', $v['tx_url']);
            $menu_item_arr[$key]['filename'] = $res_arr[2];
            $menu_item_arr[$key]['tx_url']= $oss_host.$v['tx_url'];
            $menu_item_arr[$key]['duration']= secToMinSec($v['duration']);
            $menu_item_arr[$key]['type'] = '3';
        
            //获取是否收藏、分享个数、收藏个数、获取播放次数
            $rets = $this->getPubShareInfo($openid,$v['id'],3);
            $menu_item_arr[$key]['is_collect'] = $rets['is_collect'];
            $menu_item_arr[$key]['collect_num']= $rets['collect_num'];
            $menu_item_arr[$key]['share_num']  = $rets['share_num'];
            $menu_item_arr[$key]['play_num']   = $rets['play_num']; 
            
            
        }
        $this->to_back($menu_item_arr);
    }
    /**
     * @desc 当用户连接上盒子获取盒子播放的节目单列表
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
            if($v['type']=='pro' && !in_array($v['media_id'], array('17614','19533')) ){//节目
                $map['a.media_id']= $v['media_id'];
                $map['a.type']    = 2;
                $map['b.type']    = 1;
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
                    $media_info['play_num']   = $rets['play_num'];
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
                    //获取是否收藏、分享个数、收藏个数、播放次数
                    $rets = $this->getPubShareInfo($openid,$media_info['id'],3);
                    $media_info['is_collect'] = $rets['is_collect'];
                    $media_info['collect_num']= $rets['collect_num'];
                    $media_info['share_num']  = $rets['share_num'];
                    $media_info['play_num']   = $rets['play_num'];
                    $data[$flag] = $media_info;
                    $flag++;
                }
            }else if($v['type']=='ads'){//广告
                $map['ads.media_id'] = $v['media_id'];
                $map['ads.type']     = '1';
                $map['med.type']     = 1;
                 
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
                    //获取是否收藏、分享个数、收藏个数、播放次数
                    $rets = $this->getPubShareInfo($openid,$media_info['id'],3);
                    $media_info['is_collect'] = $rets['is_collect'];
                    $media_info['collect_num']= $rets['collect_num'];
                    $media_info['share_num']  = $rets['share_num'];
                    $media_info['play_num']   = $rets['play_num'];
                    $data[$flag] = $media_info;
                    $flag++;
                }
            }
        }
        $this->to_back($data);
    }
    /**
     * @desc 获取节目单视频详情
     */
    public function getVideoInfo(){
        $res_id = $this->params['res_id'];
        $openid = $this->params['openid'];
        $type   = 3;
        //播放数据+1
        $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
        $nums = $m_play_log->countNum(array('res_id'=>$res_id));
        if(empty($nums)){
            $data = array();
            $data['res_id'] = $res_id;
            $data['type']   = 3;
            $data['nums']   = 1;
            $m_play_log->addInfo($data);
        }else {
            $where = array();
            $where['res_id'] = $res_id;
            $where['type']   = 3;
            $m_play_log->where($where)->setInc('nums',1);
        }
        $m_ads = new \Common\Model\AdsModel();
        $res_ads = $m_ads->getWhere(array('id'=>$res_id),'*');
        if($res_ads[0]['type']==8){
            $type = 5;
        }
        $data = $this->getPubShareInfo($openid, $res_id, $type);
        if($res_ads[0]['type']==1 || $res_ads[0]['type']==2){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(5);
            $hot_cache_key = C('SAPP_HOTPLAY_PRONUM');
            $all_pro_nums = $redis->get($hot_cache_key);
            $all_pro_nums = json_decode($all_pro_nums,true);
            if(isset($all_pro_nums[$res_ads[0]['media_id']])){
                $data['play_num'] = $data['play_num'] + $all_pro_nums[$res_ads[0]['media_id']];
            }
            if($res_ads[0]['type']==2){
                $hot_type = 3;
            }else{
                $hot_type = 2;
            }
            $m_hotplay = new \Common\Model\Smallapp\HotplayModel();
            $data['play_num'] = $m_hotplay->getHotplayNum($res_id,$hot_type,$data['play_num']);
        }
        $data['res_type'] = $type;
        $this->to_back($data);
    }

    public function recordPlaynum(){
        $res_id = $this->params['res_id'];
        $openid = $this->params['openid'];
        $playtype   = 5;
        //播放数据+1
        $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
        $res_nums = $m_play_log->getOne('id,nums',array('res_id'=>$res_id,'type'=>$playtype),'id desc');
        if(empty($res_nums)){
            $nums = 1;
            $data = array('res_id'=>$res_id,'type'=>$playtype,'nums'=>$nums);
            $m_play_log->addInfo($data);
        }else{
            $nums = $res_nums['nums']+1;
            $where = array('id'=>$res_nums['id']);
            $m_play_log->where($where)->setInc('nums',1);
        }

        $type = 4;
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $map = array('openid'=>$openid,'res_id'=>$res_id,'type'=>$type,'status'=>1);
        $is_collect = $m_collect->countNum($map);
        if(empty($is_collect)){
            $is_collect = 0;
        }else {
            $is_collect = 1;
        }
        $map = array('res_id'=>$res_id,'type'=>$type,'status'=>1);
        $collect_num = $m_collect->countNum($map);

        //分享个数
        $map = array('res_id'=>$res_id,'type'=>$type,'status'=>1);
        $share_num = $m_share->countNum($map);

        $res_data = array('is_collect'=>$is_collect,'collect_num'=>$collect_num,'share_num'=>$share_num,'play_num'=>$nums);
        $this->to_back($res_data);
    }


    private function getPubShareInfo($openid,$ads_id,$type){
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $m_play_log    = new \Common\Model\Smallapp\PlayLogModel();
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
        //播放次数
        if($type==5){
            $type = 3;
        }
        $map = array();
        $map['res_id'] = $ads_id;
        $map['type']   = $type;
        $play_info = $m_play_log->getOne('nums',$map);
        $data['play_num'] = intval($play_info['nums']);
        return $data;
    }
}