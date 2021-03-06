<?php
namespace Smallapp4\Controller;
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
                $this->valid_fields = array('openid'=>1001,'page_id'=>1000,'box_mac'=>1000);
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
            case 'getMyPublic':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1000);
                break;
            case 'registerCom':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'avatarUrl'=>1000,
                                            'nickName'=>1000,'gender'=>1000,
                                            'session_key'=>1001,'iv'=>1001,
                                            'encryptedData'=>1001,
                );
                break;
        }
        parent::_init_();
    }
    public function isRegister(){
        $openid  = $this->params['openid'];
        $page_id = $this->params['page_id'];
        $box_mac = $this->params['box_mac'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $userinfo = $m_user->getOne('id user_id,openid,avatarUrl,nickName,gender,status,is_wx_auth,close_hotel_hint', $where);
        $data = array();
        if(empty($userinfo)){
            $data['openid'] = $openid;
            $data['status'] = 1;
            $m_user->addInfo($data);
            $userinfo['openid'] = $openid;
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
            $redis = SavorRedis::getInstance();
            $redis->select(5);
            $cache_key = C('SAPP_PAGEVIEW_LOG').$openid;
            $map = array();
            $map['openid'] = $openid;
            $map['page_id']= $page_id;
            $map['create_time'] = date('Y-m-d H:i:s');
            $redis->rpush($cache_key, json_encode($map));
        }
        $this->to_back($data);
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
        $where['a.type'] = array('in',array(1,2,3));
    
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
            }else if($v['type']==3){
                $collect_info[$key]['res_type'] = 3;
                $info = $m_ads->alias('a')
                ->field("a.id,concat('".$oss_host."',a.img_url) imgurl,concat('".$oss_host."',`oss_addr`) res_url,media.oss_filesize as resource_size")
                ->join('savor_media media on a.media_id=media.id')
                ->where(array('a.id'=>$v['res_id']))
                ->find();
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
            $map['type']   = $v['type'];
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

        $page_size = 6;
        $limit = "limit 0,".$page_size;
        $fields = 'a.forscreen_id,a.res_type';
        $where = array();
        $where['a.openid']     = $openid;
        $where['box.flag']   = 0;
        $where['box.state']  = 1;
        $where['hotel.flag'] = 0;
        $where['hotel.state']= 1;
        $where['a.status']   = 2;
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
        $where['a.type']   = array('in','1,2,3');
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
            switch ($v['type']){//1:点播2:投屏3:节目单资源4:商品
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
                    $collect_info[$key]['res_type'] = 3;
                    $info = $m_ads->alias('a')
                        ->field("a.id,a.name title,concat('".$oss_host."',a.img_url) imgurl,concat('".$oss_host."',`oss_addr`) res_url,`oss_addr` lz_url")
                        ->join('savor_media media on a.media_id=media.id')
                        ->where(array('a.id'=>$v['res_id']))
                        ->find();
                    $filename = explode('/', $info['lz_url']);
                    $collect_info[$key]['filename'] = $filename[2];
                    $collect_info[$key]['res_nums'] = 1;
                    $collect_info[$key]['title']  = $info['title'];
                    if(empty($info['imgurl'])){
                        $collect_info[$key]['imgurl'] = $info['res_url'] .'?x-oss-process=video/snapshot,t_3000,f_jpg,w_220,m_fast';
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
        $fields = 'a.forscreen_id,a.res_type,a.res_nums,a.is_pub_hotelinfo,a.create_time,hotel.name hotel_name';
        $where = array();
        $where['a.openid']     = $openid;
        $where['box.flag']   = 0;
        $where['box.state']  = 1;
        $where['hotel.flag'] = 0;
        $where['hotel.state']= 1;
        $where['a.status']   = 2;
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
            $data['unionId']   = $encryptedData['unionId'];
            $data['is_wx_auth']= 3;
            $m_user->addInfo($data);
            $this->to_back($data);
        }else {
            $data = array();
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['unionId']   = $encryptedData['unionId'];
            $data['is_wx_auth']= 3;
            $m_user->updateInfo($where, $data);
            $data['openid'] = $openid;
            $this->to_back($data);
        }
    }
}