<?php
namespace Smallapp46\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class UserController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'isRegister':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'page_id'=>1000,'box_mac'=>1000,'is_have_link'=>1000,'unionid'=>1000);
                break;
            case 'refuseRegister':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'index':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'closeHotelHind':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'getMyCollect':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1000);
                break;
            case 'isForscreenIng':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac');
                break;
            case 'getMyPublic':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1000);
                break;
            case 'delMyPublic':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'forscreen_id'=>1001);
                break;
            case 'registerCom':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'avatarUrl'=>1000,
                                            'nickName'=>1000,'gender'=>1000,
                                            'session_key'=>1000,'iv'=>1000,
                                            'encryptedData'=>1000,
                );
                break;
            case 'bindMobile':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'session_key'=>1001,'iv'=>1001,'encryptedData'=>1001);
                break;
            case 'bindOffiaccount':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'wxmpopenid'=>1001,'subscribe_time'=>1002);
                break;
            case 'delMyCollect':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'res_id'=>1000);
                break;
			case 'editCard':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'name'=>1001,'mobile'=>1001,
                    'job'=>1002,'company'=>1002,'qrcode_img'=>1001);
			    break;
            case 'lotterylist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1000);
                break;
        }
        parent::_init_();
    }
    public function isRegister(){
        $openid  = $this->params['openid'];
        $page_id = $this->params['page_id'];
        $box_mac = $this->params['box_mac'];
        $is_have_link = $this->params['is_have_link'];
        $unionid = $this->params['unionid'] ? $this->params['unionid']: "";
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $userinfo = $m_user->getOne('id user_id,openid,avatarUrl,nickName,mobile,gender,status,is_wx_auth,close_hotel_hint,wx_mpopenid,use_time', $where);
        $data = array();
        
        $redis = SavorRedis::getInstance();
        if(empty($userinfo)){
            $data['openid'] = $openid;
            $data['status'] = 1;
            $data['unionId'] = $unionid;
            $m_user->addInfo($data);
            $userinfo['openid'] = $openid;
            $userinfo['subscribe'] = 1;
            $userinfo['avatarUrl'] = '';
            $userinfo['nickName']  = '';
            $userinfo['is_wx_auth']= 0;
            $userinfo['use_time'] = array('use_time_str'=>'本次您是第1次使用热点投屏','cut_sec'=>5);
        }else{
            $redis->select(1);
            $cache_key = C('SMALLAPP_DAY_QRCDE').$openid;
            $is_rec = $redis->get($cache_key);
            if(empty($is_rec)){
                if($is_have_link==1){
                    $is_show = true;
                }else {
                    $is_show = false;
                }
                $use_time = $userinfo['use_time']+1;
                $use_time_str = '本次您是第'.$use_time.'次使用热点投屏';
                $userinfo['use_time'] = array('use_time_str'=>$use_time_str,'cut_sec'=>5,'is_show'=>$is_show);
                
            }else {
                $use_time = $userinfo['use_time']+1;
                $use_time_str = '本次您是第'.$use_time.'次使用热点投屏';
                $userinfo['use_time'] = array('use_time_str'=>$use_time_str,'cut_sec'=>5,'is_show'=>false);
            }
            $userinfo['subscribe'] = 1;
        }
        $data['userinfo'] = $userinfo;
        if(!empty($box_mac) && $box_mac!='undefined'){
            $m_box = new \Common\Model\BoxModel();
            $map = array();
            $map['mac']   = $box_mac;
            $map['state'] = 1;
            $map['flag']  = 0;
            $ret = $m_box->field('is_open_simple')->where($map)->find();
            $data['userinfo']['is_open_simple'] = $ret['is_open_simple'];
        }
        if($page_id){
            $redis->select(5);
            $cache_key = C('SAPP_PAGEVIEW_LOG').$openid;
            $map = array();
            $map['openid'] = $openid;
            $map['page_id']= $page_id;
            $map['create_time'] = date('Y-m-d H:i:s');
            $redis->rpush($cache_key, json_encode($map));
            if($page_id == 1 && $is_have_link==1){
                $redis->select(1);
                $cache_key = C('SMALLAPP_DAY_QRCDE').$openid;
                
                $end_time = strtotime(date('Y-m-d').' 23:59:59');
                $now_time = time();
                $diff_time = $end_time - $now_time;
                $redis->set($cache_key, 1,$diff_time);
            }
        }
        $guide_prompt = array();
        $redis->select(5);
        $key = C('SAPP_GUIDE_PROMPT').$openid;
        $res_cache = $redis->get($key);
        if(!empty($res_cache)) {
            $res_data = json_decode($res_cache, true);
            $guide_prompt = array_keys($res_data);
        }
        $data['userinfo']['guide_prompt'] = $guide_prompt;
        $this->to_back($data);
    }
    
    public function bindOffiaccount(){
        $openid  = $this->params['openid'];
        $wxmpopenid = $this->params['wxmpopenid'];
        $subscribe_time = $this->params['subscribe_time'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $userinfo = $m_user->getOne('id,openid,wx_mpopenid', $where);
        $data = array('openid'=>$openid,'wx_mpopenid'=>$wxmpopenid,'is_subscribe'=>1,'status'=>1);
        if(!empty($subscribe_time)){
            $subscribe_time = date('Y-m-d H:i:s',$subscribe_time);
            $data['subscribe_time'] = $subscribe_time;
        }
        if(empty($userinfo)){
            $m_user->addInfo($data);
        }else{
            $m_user->updateInfo(array('id'=>$userinfo['id']),$data);
        }
        $this->to_back(array());
    }
	public function editCard(){
        $openid  = $this->params['openid'];
        $name  = trim($this->params['name']);
        $mobile = $this->params['mobile'];
        $job = $this->params['job'];
        $company = $this->params['company'];
        $qrcode_img = $this->params['qrcode_img'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        if(!empty($mobile)){
            $is_check = check_mobile($mobile);
            if(!$is_check){
                $this->to_back(93006);
            }
        }
        $data = array('user_id'=>$user_info['id'],'name'=>$name,'update_time'=>date('Y-m-d H:i:s'));
        if($mobile)     $data['mobile'] = $mobile;
        if($job)        $data['job'] = $job;
        if($company)    $data['company'] = $company;
        if($qrcode_img) $data['qrcode_img'] = $qrcode_img;

        $m_usercard = new \Common\Model\Smallapp\UsercardModel();
        $res_usercard = $m_usercard->getInfo(array('user_id'=>$user_info['id']));
        if(empty($res_usercard)){
            $m_usercard->add($data);
        }else{
            $m_usercard->updateData(array('id'=>$res_usercard['id']),$data);
        }
        $this->to_back(array());
    }
    public function refuseRegister(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $data = array();
        $data['is_wx_auth'] = 1;

        $ret = $m_user->updateInfo($where, $data);
        if($ret){

            $this->to_back(10000);
        }else {
            $this->to_back(91015);
        }
    }


    public function closeHotelHind(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = $data = array();
        $where['openid'] = $openid;
        $data['close_hotel_hint'] = 1;
        $ret = $m_user->updateInfo($where,$data);
        
        $this->to_back(10000);
    }
    /**
     * @desc 获取我的收藏
     */
    public function getMyCollect(){
        $openid = $this->params['openid'];
        $page   = $this->params['page'] ? intval($this->params['page']) : 1;
        //获取用户信息
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $page_size = 10;
        $limit = "limit 0,".$page*$page_size;
    
        $fields = "a.res_id,a.type,a.create_time,b.res_type,c.avatarUrl,c.nickName";
        $where = array();
        $where['a.openid'] = $openid;
        $where['a.status'] = 1;
        $where['a.type'] = array('in',array(1,2,3,5));
    
        $order="create_time desc";
        $collect_info = $m_collect->getList($fields, $where, $order, $limit);

        $m_content  = new \Common\Model\ContentModel();
        $m_pubdetail= new \Common\Model\Smallapp\PubdetailModel();
        $m_public      = new \Common\Model\Smallapp\PublicModel();
        $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
        $m_ads      = new \Common\Model\AdsModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        foreach($collect_info as $key=>$v){
            if($v['type'] ==1){ //点播
                $collect_info[$key]['res_type'] = 2;
                $info = $m_content->field("`title`,`tx_url` res_url, concat('".$oss_host."',`img_url`) imgurl, '2' as  res_type , '1' as res_nums")
                ->where(array('id'=>$v['res_id']))
                ->find();
                $res_url = strstr($info['res_url'], '?',-1);
                $collect_info[$key]['res_nums'] = 1;
                $info['res_url'] = $res_url;
                $collect_info[$key]['list'] = $info;
            }else if($v['type'] ==2){ //投屏
                $pub_info = $m_public->getOne('res_type,res_nums', array('forscreen_id'=>$v['res_id'],'status'=>2));
                $collect_info[$key]['res_type'] = $pub_info['res_type'];
                $collect_info[$key]['res_nums'] = $pub_info['res_nums'];
                if(!empty($pub_info)){
                    $fields = "resource_id res_id,concat('".$oss_host."',`res_url`) res_url,`res_url` forscreen_url,substring(`res_url`,20) filename,resource_size";
                    $pubdetails = $m_pubdetail->getWhere($fields, array('forscreen_id'=>$v['res_id']));
                    
                    if($v['res_type']==2){
                        $pubdetails[0]['imgurl'] = $pubdetails['0']['res_url'].'?x-oss-process=video/snapshot,t_3000,f_jpg,w_450,m_fast';
                        $collect_info[$key]['list'] = array($pubdetails[0]);
                    }else {
                        $collect_info[$key]['list'] = $pubdetails;
                    }
                    $collect_info[$key]['res_num'] = count($pubdetails);
                }
            }else if($v['type']==3 || $v['type']==5){
                $collect_info[$key]['res_type'] = $v['type'];
                if(empty($v['avatarUrl']) || empty($v['nickName'])){
                    $collect_info[$key]['nickName'] = '小热点';
                    $collect_info[$key]['avatarUrl'] = 'http://oss.littlehotspot.com/media/resource/btCfRRhHkn.jpg';
                }
                $info = $m_ads->alias('a')
                ->field("a.id,media.type as media_type,concat('".$oss_host."',a.img_url) imgurl,concat('".$oss_host."',`oss_addr`) res_url,media.oss_filesize as resource_size")
                ->join('savor_media media on a.media_id=media.id')
                ->where(array('a.id'=>$v['res_id']))
                ->find();
                $collect_info[$key]['media_type'] = $info['media_type'];
                if(empty($info['imgurl'])){
                    if($info['media_type']==1){
                        $imgurl = $info['res_url'] .'?x-oss-process=video/snapshot,t_3000,f_jpg,w_220,m_fast';
                    }else{
                        $imgurl = $info['res_url'];
                    }
                    $info['imgurl'] = $imgurl;
                }else {
                    $info['imgurl']  = $info['imgurl'];
                }
                if(empty($info['avatarUrl']) || empty($info['nickName'])){
                    $info['nickName'] = '小热点';
                    $info['avatarUrl'] = 'http://oss.littlehotspot.com/media/resource/btCfRRhHkn.jpg';
                }

                $info['filename'] = substr($info['res_url'], strripos($info['res_url'], '/')+1);
                $collect_info[$key]['list'] = $info;
            }
            $collect_info[$key]['create_time'] = date('n月j日',strtotime($v['create_time']));
    
            //收藏数量
            $map = array();
            $map['res_id'] =$v['res_id'];
            $map['type']   = $v['type'];
            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);
    
            $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
            $ret = $m_collect_count->field('nums')->where(array('res_id'=>$v['res_id']))->find();
            $collect_info[$key]['collect_num'] = $collect_num+$ret['nums'];
            //分享个数
            $m_share = new \Common\Model\Smallapp\ShareModel();
            $map = array();
            $map['res_id'] =$v['res_id'];
            $map['type']   = $v['type'];
            $map['status'] = 1;
            $share_num = $m_share->countNum($map);
            $collect_info[$key]['share_num'] = $share_num;
            
            
            //播放次数
            $map = array();
            $map['res_id'] = $v['res_id'];
            if($v['type']==5){
                $play_type = 3;
            }else{
                $play_type = $v['type'];
            }
            $map['type'] = $play_type;
            $play_info = $m_play_log->getOne('nums',$map); 
            $play_num  = intval($play_info['nums']);
            $collect_info[$key]['play_num'] =$play_num;
            $collect_info[$key]['is_collect'] = 1;
        }
        $data = array();
        //$data['user_info'] = $user_info;
        $data['list'] = $collect_info;
        $this->to_back($data);
    }
    public function index(){
        $openid = $this->params['openid'];
        //获取用户信息
        $m_user = new \Common\Model\Smallapp\UserModel();
        $user_info = $m_user->getOne('id,avatarUrl,nickName', array('openid'=>$openid,'status'=>1));
        $data['user_info'] = $user_info;
        //获取我的公开
        $all_status = C('PUBLIC_AUDIT_STATUS');
        unset($all_status['0']);
        $page_size = 6;
        $limit = "limit 0,".$page_size;
        $fields = 'a.forscreen_id,a.res_type,a.status';
        $where = array();
        $where['a.openid']     = $openid;
        $where['box.flag']   = 0;
        $where['box.state']  = 1;
        $where['hotel.flag'] = 0;
        $where['hotel.state']= 1;
        $where['a.status']   = array('in',array_keys($all_status));
        $order = "a.create_time desc";
        $m_public = new \Common\Model\Smallapp\PublicModel();
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $public_list = $m_public->getList($fields, $where, $order, $limit);
    
        $m_pubdetail = new \Common\Model\Smallapp\PubdetailModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        foreach($public_list as $key=>$v){
            $fields = "concat('".$oss_host."',`res_url`) res_url,duration";
            $where = array();
            $where['forscreen_id'] = $v['forscreen_id'];
            $pubdetail_info = $m_pubdetail->getWhere($fields, $where);
            if($v['res_type']==2){
                $pubdetail_info[0]['vide_img'] = $pubdetail_info[0]['res_url']."?x-oss-process=video/snapshot,t_3000,f_jpg,w_220,m_fast";
                $pubdetail_info[0]['duration'] = secToMinSec(intval($pubdetail_info[0]['duration']));
            }else {
                $pubdetail_info[0]['vide_img'] = $pubdetail_info[0]['res_url'];
            }
            $public_list[$key]['status_str'] = $all_status[$v['status']];
            $public_list[$key]['res_url'] = $pubdetail_info[0]['res_url'];
            $public_list[$key]['imgurl']  = $pubdetail_info[0]['vide_img'] ;
            //收藏个数
            $map = array();
            $map['res_id'] =$v['forscreen_id'];
            $map['type']   = 2;
            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);
            $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
            $ret = $m_collect_count->field('nums')->where(array('res_id'=>$v['forscreen_id']))->find();
            $public_list[$key]['collect_num'] = $collect_num + $ret['nums'];
            
            $map = array();
            $map['openid']=$openid;
            $map['res_id'] =$v['forscreen_id'];
            
            $map['status'] = 1;
            $is_collect = $m_collect->countNum($map);
            if(empty($is_collect)){
                $public_list[$key]['is_collect'] = "0";
            }else {
                $public_list[$key]['is_collect'] = "1";
            }
    
        }
        if(empty($public_list)) $public_list = '';
        $data['public_list'] = $public_list;
    
        //我的收藏
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $limit = "limit 0,".$page_size;
        $fields = "a.res_id,a.type,b.res_type";
        $where = array();
        $where['a.openid'] = $openid;
        $where['a.status'] = 1;
        $where['a.type']   = array('in','1,2,3,5');
        $order="a.create_time desc";
        $collect_info = $m_collect->getList($fields, $where, $order, $limit);
    
        $m_content  = new \Common\Model\ContentModel();
        $m_pubdetail= new \Common\Model\Smallapp\PubdetailModel();
        $m_public      = new \Common\Model\Smallapp\PublicModel();
        $m_ads = new \Common\Model\AdsModel();
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $m_media = new \Common\Model\MediaModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        foreach($collect_info as $key=>$v){
            switch ($v['type']){//1:点播2:投屏3:节目单资源4:商品 5本地生活
                case 1:
                    $collect_info[$key]['res_type'] = 2;
                    $info = $m_content->field("`title`,`tx_url` res_url, concat('".$oss_host."',`img_url`) imgurl, '2' as  res_type , '1' as res_nums")
                        ->where(array('id'=>$v['res_id']))
                        ->find();
                    $res_url = strstr($info['res_url'], '?',-1);
                    $collect_info[$key]['res_nums'] = 1;
                    $info['res_url'] = $res_url;
                    $collect_info[$key]['res_url'] = $info['res_url'];
                    $collect_info[$key]['imgurl']  = $info['imgurl'];
                    break;
                case 2:
                    $pub_info = $m_public->getOne('res_type,res_nums', array('forscreen_id'=>$v['res_id'],'status'=>2));
                    $collect_info[$key]['res_type'] = $pub_info['res_type'];
                    $collect_info[$key]['res_nums'] = $pub_info['res_nums'];
                    if(!empty($pub_info)){

                        $fields = "resource_id,concat('".$oss_host."',`res_url`) res_url";
                        $pubdetails = $m_pubdetail->getWhere($fields, array('forscreen_id'=>$v['res_id']));
                        if($v['res_type']==2){
                            $pubdetails[0]['imgurl'] = $pubdetails['0']['res_url'].'?x-oss-process=video/snapshot,t_3000,f_jpg,w_220,m_fast';
                            $collect_info[$key]['res_url'] = $pubdetails[0]['res_url'];
                            $collect_info[$key]['imgurl']  = $pubdetails[0]['imgurl'];
                        }else {
                            $collect_info[$key]['res_url'] = $pubdetails[0]['res_url'];
                            $collect_info[$key]['imgurl']  = $pubdetails[0]['res_url'];
                        }
                    }
                    break;
                case 3:
                case 5:
                    $collect_info[$key]['res_type'] = $v['type'];
                    $info = $m_ads->alias('a')
                        ->field("a.id,a.name title,media.type as media_type,concat('".$oss_host."',a.img_url) imgurl,concat('".$oss_host."',`oss_addr`) res_url,`oss_addr` lz_url")
                        ->join('savor_media media on a.media_id=media.id')
                        ->where(array('a.id'=>$v['res_id']))
                        ->find();
                    $collect_info[$key]['media_type'] = $info['media_type'];

                    $filename = explode('/', $info['lz_url']);
                    $collect_info[$key]['filename'] = $filename[2];
                    $collect_info[$key]['res_nums'] = 1;
                    $collect_info[$key]['title']  = $info['title'];
                    if(empty($info['imgurl'])){
                        if($info['media_type']==1){
                            $imgurl = $info['res_url'] .'?x-oss-process=video/snapshot,t_3000,f_jpg,w_220,m_fast';
                        }else{
                            $imgurl = $info['res_url'];
                        }
                        $collect_info[$key]['imgurl'] = $imgurl;
                    }else {
                        $collect_info[$key]['imgurl']  = $info['imgurl'];
                    }
                    $collect_info[$key]['res_url'] = $info['res_url'];
                    break;
                case 4:
                    $info = $m_goods->getInfo(array('id'=>$v['res_id']));
                    $media_info = $m_media->getMediaInfoById($info['media_id']);
                    $res_url = $media_info['oss_addr'];

                    $imgurl = '';
                    if(!empty($v['cover_imgmedia_ids'])){
                        $cover_imgmedia_ids = json_decode($v['cover_imgmedia_ids'],true);
                        foreach ($cover_imgmedia_ids as $cv){
                            if(!empty($cv)){
                                $media_info = $m_media->getMediaInfoById($cv);
                                $imgurl = $media_info['oss_addr'];
                                break;
                            }
                        }
                    }else{
                        $imgurl = $res_url.'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450';
                    }
                    $collect_info[$key]['res_nums'] = 1;
                    $info['res_url'] = $res_url;
                    $collect_info[$key]['res_url'] = $res_url;
                    $collect_info[$key]['imgurl'] = $imgurl;
                    break;
            }
            //收藏个数
            $map = array();
            $map['res_id'] =$v['res_id'];
            $map['type']   = $v['type'];
            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);
            $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
            $ret = $m_collect_count->field('nums')->where(array('res_id'=>$v['res_id']))->find();
            $collect_info[$key]['collect_num'] = $collect_num +$ret['nums'];
        }
        if(empty($collect_info)) $collect_info = '';
        $data['collect_list'] = $collect_info;
        $this->to_back($data);
    }
    /**
     * @desc 获取我的公开记录
     */
    public function getMyPublic(){
        $openid = $this->params['openid'];
        $page   = $this->params['page'] ? intval($this->params['page']) : 1;
    
        //获取用户信息
        $m_user = new \Common\Model\Smallapp\UserModel();
        $user_info = $m_user->getOne('id,avatarUrl,nickName', array('openid'=>$openid,'status'=>1));
    
        $page_size = 10;
        $limit = "limit 0,".$page*$page_size;
        $fields = 'a.forscreen_id,a.res_type,a.status,a.res_nums,a.is_pub_hotelinfo,a.create_time,hotel.name hotel_name';
        $all_status = C('PUBLIC_AUDIT_STATUS');
        unset($all_status['0']);
        $where = array();
        $where['a.openid']     = $openid;
        $where['box.flag']   = 0;
        $where['box.state']  = 1;
        $where['hotel.flag'] = 0;
        $where['hotel.state']= 1;
        $where['a.status']   = array('in',array_keys($all_status));
        $order = "a.create_time desc";
        $m_public = new \Common\Model\Smallapp\PublicModel();
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $m_play_log= new \Common\Model\Smallapp\PlayLogModel();
        $public_list = $m_public->getList($fields, $where, $order, $limit);
    
        $m_pubdetail = new \Common\Model\Smallapp\PubdetailModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        foreach($public_list as $key=>$v){
            $fields = "concat('".$oss_host."',`res_url`) res_url,duration,`res_url` forscreen_url,resource_size";
            $where = array();
            $where['forscreen_id'] = $v['forscreen_id'];
            $pubdetail_info = $m_pubdetail->getWhere($fields, $where);
            if($v['res_type']==2){
                $filename = explode('/', $pubdetail_info[0]['forscreen_url']);
                
                $pubdetail_info[0]['filename'] = $filename[2];
                $tmp_arr = explode('.', $filename[2]);
                $pubdetail_info[0]['res_id']   = $tmp_arr[0];
                $pubdetail_info[0]['vide_img'] = $pubdetail_info[0]['res_url']."?x-oss-process=video/snapshot,t_3000,f_jpg,w_450,m_fast";
                $pubdetail_info[0]['duration'] = secToMinSec(intval($pubdetail_info[0]['duration']));
            }else {
                foreach($pubdetail_info as $kk=>$vv){
                    $filename = explode('/', $vv['forscreen_url']);
                    
                    $pubdetail_info[$kk]['filename'] = $filename[2];
                    $tmp_arr = explode('.', $filename[2]);
                    $pubdetail_info[$kk]['res_id']   = $tmp_arr[0];
                }
            }
            $public_list[$key]['status_str'] = $all_status[$v['status']];
            $public_list[$key]['pubdetail'] = $pubdetail_info;
            $public_list[$key]['create_time'] = date('n月j日',strtotime($v['create_time']));
            //收藏个数
            $map = array();
            $map['res_id'] =$v['forscreen_id'];
            $map['type']   = 2;
            $map['status'] = 1;
            $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
            $ret = $m_collect_count->field('nums')->where(array('res_id'=>$v['forscreen_id']))->find();
            $collect_num = $m_collect->countNum($map);
            $public_list[$key]['collect_num'] = $collect_num + $ret['nums'];
            //分享个数
            $map = array();
            $map['res_id'] =$v['forscreen_id'];
            $map['type']   = 2;
            $map['status'] = 1;
            $share_num = $m_share->countNum($map);
            $public_list[$key]['share_num'] = $share_num;
            
            //播放次数
            $map = array();
            $map['res_id'] = $v['forscreen_id'];
            $map['type']   = 2;
            $play_info = $m_play_log->getOne('nums',$map);
            $play_num  = intval($play_info['nums']);
            $public_list[$key]['play_num'] = $play_num;
            
            
            $map = array();
            $map['openid']=$openid;
            $map['res_id'] =$v['forscreen_id'];
            
            $map['status'] = 1;
            $is_collect = $m_collect->countNum($map);
            if(empty($is_collect)){
                $public_list[$key]['is_collect'] = 0;
            }else {
                $public_list[$key]['is_collect'] = 1;
            }
        }
        $data = array();
        $data['user_info'] = $user_info;
        $data['list'] = $public_list;
        $this->to_back($data);
    }
    //用户注册 获取unionid
    public function registerCom(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $nums = $m_user->countNum($where);
        $encryptedData = $this->params['encryptedData'];
        
        if(empty($nums)){
            $data['openid']    = $openid;
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            if(!empty($encryptedData['unionId'])){
                $data['unionId']   = $encryptedData['unionId'];
            }else if(!empty($this->params['unionid'])){
                $data['unionId'] =$this->params['unionid'];
            }
            if(!empty($encryptedData['phoneNumber'])){
                $data['mobile'] = $encryptedData['phoneNumber'];
            }
            $data['is_wx_auth']= 3;
            $m_user->addInfo($data);
            $this->to_back($data);
        }else {
            $data = array();
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            if(!empty($encryptedData['unionId'])){
                $data['unionId']   = $encryptedData['unionId'];
            }else if(!empty($this->params['unionid'])){
                $data['unionId'] =$this->params['unionid'];
            }
            
            if(!empty($encryptedData['phoneNumber'])){
                $data['mobile'] = $encryptedData['phoneNumber'];
            }
            $data['is_wx_auth']= 3;
            $m_user->updateInfo($where, $data);
            $data['openid'] = $openid;
            $this->to_back($data);
        }
    }

    public function bindMobile(){
        $openid = $this->params['openid'];
        $encryptedData = $this->params['encryptedData'];
        if(!empty($encryptedData['phoneNumber'])){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $where = array('openid'=>$openid);
            $m_user->updateInfo($where, array('mobile'=>$encryptedData['phoneNumber']));
        }
        $this->to_back($encryptedData);
    }


    /**
     * @desc 判断是否有正在投屏的内容，提示是否打断
     */
    public function isForscreenIng(){
        $box_mac = $this->params['box_mac'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);

        $box_net_key = C('SAPP_BOX_FORSCREEN_NET').$box_mac;
        $data = $redis->lgetrange($box_net_key,0,-1);
        $org_arr = array();
        foreach($data as $key=>$v){
            $info = json_decode($v,true);
            $org_arr[] = $info['forscreen_id'];
        }
        $small_cache_key = C('SAPP_SCRREN').":".$box_mac;
        $data = $redis->lgetrange($small_cache_key,0,-1);
        $is_forscreen = 0;
        foreach($data as $key=>$v){
            $info = json_decode($v,true);
            $action = $info['action'];
            $resource_type = $info['resource_type'];
            if($action ==4 || ($action==2 and $resource_type==2)){
                $tmp = $info['forscreen_id'];
                if(!in_array($tmp, $org_arr)){
                    $is_forscreen = 1;
                    break;
                }
            }
        }
        $ars = array('is_forscreen'=>$is_forscreen);
        $this->to_back($ars);
    }

    public function delMyPublic(){
        $openid = $this->params['openid'];
        $forscreen_id = $this->params['forscreen_id'];
        $m_public = new \Common\Model\Smallapp\PublicModel();
        $where = array();
        $where['openid']= $openid;
        $where['forscreen_id'] = $forscreen_id;
        $ret = $m_public->updateInfo($where, array('status'=>0));
        if($ret){
            $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
            $res_forscreen = $m_forscreen->getWhere('id',$where,'id asc','0,1','');
            $forscreen_record_id = $res_forscreen[0]['id'];

            $m_help = new \Common\Model\Smallapp\ForscreenHelpModel();
            $res_help = $m_help->getInfo(array('openid'=>$openid,'forscreen_record_id'=>$forscreen_record_id));
            if(!empty($res_help)){
                $help_id = $res_help['id'];
                $m_help->updateData(array('id'=>$help_id),array('status'=>4));

                $m_playlog = new \Common\Model\Smallapp\PlayLogModel();
                $m_playlog->updateInfo(array('res_id'=>$help_id,'type'=>4),array('nums'=>0,'update_time'=>date('Y-m-d H:i:s')));

                $content_key = C('SAPP_SELECTCONTENT_CONTENT');
                $redis  =  \Common\Lib\SavorRedis::getInstance();
                $redis->select(5);
                $res_cache = $redis->get($content_key);
                if(!empty($res_cache)) {
                    $content_data = json_decode($res_cache, true);
                    if(isset($content_data[$help_id])){
                        unset($content_data[$help_id]);
                        $redis->set($content_key,json_encode($content_data));

                        $redis->select(5);
                        $allkeys  = $redis->keys('smallapp:selectcontent:program:*');
                        foreach ($allkeys as $program_key){
                            $period = getMillisecond();
                            $redis->set($program_key,$period);
                        }
                    }
                }
            }
            $where = array('openid'=>$openid,'forscreen_id'=>$forscreen_id);
            $res_public = $m_public->getOne('*',$where,'');
            if(!empty($res_public)){
                $m_publicplay = new \Common\Model\Smallapp\PublicplayModel();
                $res_public_play = $m_publicplay->getInfo(array('public_id'=>$res_public['id']));
                if(!empty($res_public_play)){
                    $add_data = array('status'=>2,'update_time'=>date('Y-m-d H:i:s'));
                    $m_publicplay->updateData(array('id'=>$res_public_play['id']),$add_data);
                }
            }
            $this->to_back(10000);
        }else {
            $this->to_back(90107);
        }
    }

    /**
     * @desc 删除我的收藏
     */
    public function delMyCollect(){
        $openid = $this->params['openid'];
        $res_id = $this->params['res_id'];
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $where = array();
        $where['openid']= $openid;
        $where['res_id'] = $res_id;
        $m_collect->updateInfo($where, array('status'=>0));
        $this->to_back(10000);

    }

    public function lotterylist(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $page_size = 10;
        $all_nums = $page*$page_size;

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,avatarUrl,nickName,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90157);
        }
        $fields = 'activity.id as activity_id,activity.start_time,activity.end_time,activity.type,activity.prize,a.id,a.prize_id,a.status,a.add_time';
        $where = array('a.openid'=>$openid,'activity.type'=>array('in',array(1,3)),'a.status'=>array('in',array(1,2,4,5)));
        $order = 'a.id desc';
        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        $limit = "0,$all_nums";
        $res_activity_apply = $m_activityapply->getApplyDatas($fields,$where,$order,$limit,'');
        $datalist = array();
        if(!empty($res_activity_apply)){
            $now_time = date('Y-m-d H:i:s');
            $m_prize = new \Common\Model\Smallapp\ActivityprizeModel();
            foreach ($res_activity_apply as $v){
                if($v['type']==1){
                    $name = $v['prize'];
                }else{
                    $res_prize = $m_prize->getInfo(array('id'=>$v['prize_id']));
                    $name = $res_prize['name'];
                }
                $lottery_time = date('Y-m-d-H:i',strtotime($v['add_time']));
                switch ($v['status']){
                    case 1:
                        $status = 1;
                        break;
                    case 2:
                        $status = 2;//1待领取 2已领取 3已过期
                        break;
                    case 4:
                        if($now_time>=$v['start_time'] && $now_time<=$v['end_time']){
                            $status = 1;
                        }else{
                            $status = 3;
                        }
                        break;
                    case 5:
                        $status = 1;
                        break;
                    default:
                        $status = 3;
                }
                $info = array('activity_id'=>$v['activity_id'],'name'=>$name,'lottery_time'=>$lottery_time,'status'=>$status,'id'=>$v['id'],'type'=>$v['type']);
                $datalist[]=$info;
            }
        }
        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }


}