<?php
namespace Smallapp21\Controller;
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
                $this->valid_fields = array('openid'=>1001,'page_id'=>1000);
                break;
            case 'register':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'avatarUrl'=>1000,'nickName'=>1000,'gender'=>1000);
                break;
            case 'refuseRegister':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'index':
                $this->is_verify = 1;
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
        }
        parent::_init_();
    }
    public function isRegister(){
        $openid = $this->params['openid'];
        $page_id = $this->params['page_id'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $userinfo = $m_user->getOne('id user_id,openid,avatarUrl,nickName,gender,status,is_wx_auth', $where);
        $data = array();
        if(empty($userinfo)){
            $data['openid'] = $openid;
            $data['status'] = 1;
            $m_user->addInfo($data);
            $userinfo['openid'] = $openid;
        }
        $data['userinfo'] = $userinfo;
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
    public function register(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $nums = $m_user->countNum($where);
        if(empty($nums)){
            $data['openid']    = $openid;
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['is_wx_auth']= 2;
            $m_user->addInfo($data);
            $this->to_back($data);
        }else {
            $data = array();
            $data['avatarUrl'] = $this->params['avatarUrl'];
            $data['nickName']  = $this->params['nickName'];
            $data['gender']    = $this->params['gender'];
            $data['is_wx_auth']= 2;
            $m_user->updateInfo($where, $data);
            $data['openid'] = $openid;
            $this->to_back($data);
        }
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
    
        $order="a.create_time desc";
        $collect_info = $m_collect->getList($fields, $where, $order, $limit);
    
        $m_content  = new \Common\Model\ContentModel();
        $m_pubdetail= new \Common\Model\Smallapp\PubdetailModel();
        $m_public      = new \Common\Model\Smallapp\PublicModel();
        $m_ads = new \Common\Model\AdsModel();
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
                $collect_info[$key]['res_url'] = $info['res_url'];
                $collect_info[$key]['imgurl']  = $info['imgurl'];
    
            }else if($v['type'] ==2){ //投屏
                 
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
            }else if($v['type']==3){//节目单列表
                $collect_info[$key]['res_type'] = 3;
                $info = $m_ads->alias('a')
                              ->field("a.id,concat('".$oss_host."',a.img_url) imgurl,concat('".$oss_host."',`oss_addr`) res_url")
                              ->join('savor_media media on a.media_id=media.id')
                              ->where(array('a.id'=>$v['res_id']))
                              ->find();
                
                $collect_info[$key]['res_nums'] = 1;
                if(empty($info['imgurl'])){
                    $collect_info[$key]['imgurl'] = $info['res_url'] .'?x-oss-process=video/snapshot,t_3000,f_jpg,w_220,m_fast';
                }else {
                    $collect_info[$key]['imgurl']  = $info['imgurl'];
                }
                $collect_info[$key]['res_url'] = $info['res_url'];
                
            }
    
        }
        if(empty($collect_info)) $collect_info = '';
        $data['collect_list'] = $collect_info;
        $this->to_back($data);
    }
    /**
     * @desc 获取我的收藏
     */
    public function getMyCollect(){
        $openid = $this->params['openid'];
        $page   = $this->params['page'] ? intval($this->params['page']) : 1;
        //获取用户信息
        //$m_user = new \Common\Model\Smallapp\UserModel();
        //$user_info = $m_user->getOne('id,avatarUrl,nickName', array('openid'=>$openid,'status'=>1));
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $page_size = 10;
        $limit = "limit 0,".$page*$page_size;
        
        $fields = "a.res_id,a.type,a.create_time,b.res_type";
        $where = array();
        $where['a.openid'] = $openid;
        $where['a.status'] = 1;
        
        $order="create_time desc";
        $collect_info = $m_collect->getList($fields, $where, $order, $limit);
        
        $m_content  = new \Common\Model\ContentModel();
        $m_pubdetail= new \Common\Model\Smallapp\PubdetailModel();
        $m_public      = new \Common\Model\Smallapp\PublicModel();
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
                    
                        $fields = "resource_id,concat('".$oss_host."',`res_url`) res_url";
                        $pubdetails = $m_pubdetail->getWhere($fields, array('forscreen_id'=>$v['res_id']));
                        if($v['res_type']==2){
                            $pubdetails[0]['imgurl'] = $pubdetails['0']['res_url'].'?x-oss-process=video/snapshot,t_3000,f_jpg,w_450,m_fast';
                            $collect_info[$key]['list'] = $pubdetails[0];
                        }else {
                            $collect_info[$key]['list'] = $pubdetails;
                        }
                        
                    
                    
                } 
            }else if($v['type']==3){
                $collect_info[$key]['res_type'] = 3;
                $info = $m_ads->alias('a')
                ->field("a.id,concat('".$oss_host."',a.img_url) imgurl,concat('".$oss_host."',`oss_addr`) res_url")
                ->join('savor_media media on a.media_id=media.id')
                ->where(array('a.id'=>$v['res_id']))
                ->find();
                
                $collect_info[$key]['list'] = $info;
                
            }
            $collect_info[$key]['create_time'] = date('n月j日',strtotime($v['create_time']));
            
            //收藏数量
            $map = array();
            $map['res_id'] =$v['res_id'];
            $map['type']   = $v['type'];
            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);
            $collect_info[$key]['collect_num'] = $collect_num;
            //分享个数
            $m_share = new \Common\Model\Smallapp\ShareModel();
            $map = array();
            $map['res_id'] =$v['res_id'];
            $map['type']   = $v['type'];
            $map['status'] = 1;
            $share_num = $m_share->countNum($map);
            $collect_info[$key]['share_num'] = $share_num;
        }
        $data = array();
        //$data['user_info'] = $user_info;
        $data['list'] = $collect_info;
        $this->to_back($data);
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
            $org_arr[] = $info['forscreen_id'].'_'.$info['resource_id'];
            
        }
        
        
        
        $small_cache_key = C('SAPP_SCRREN').":".$box_mac;
        //$this->to_back(array('is_forscreen'=>1));
        $data = $redis->lgetrange($small_cache_key,0,-1);
        $is_forscreen = 0;
        foreach($data as $key=>$v){
            $info = json_decode($v,true);
            $action = $info['action'];
            $resource_type = $info['resource_type'];
            if($action ==4 || ($action==2 and $resource_type==2)
                || $action==11 || $action==12  ){
        
                $tmp = $info['forscreen_id'].'_'.$info['resource_id'];
                if(!in_array($tmp, $org_arr)){
                    $is_forscreen = 1;
                    break;
                }
        
            }
        
        }
        
        $ars = array('is_forscreen'=>$is_forscreen);
        $this->to_back($ars);
        //$this->to_back(array('is_forscreen'=>1));
        
    }
}