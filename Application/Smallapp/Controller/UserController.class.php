<?php
namespace Smallapp\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class UserController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'register':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'avatarUrl'=>1000,'nickName'=>1000,'gender'=>1000);
                break;
            case 'getMyPublic':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1000);
                break;
            case 'getMyCollect':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1000);
                break;
            case 'delMyPublic':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'forscreen_id'=>1001);
                break;
            case 'delMyCollect':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'res_id'=>1000);
                break;
            case 'index':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'test':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
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
            }
        
        }
        if(empty($collect_info)) $collect_info = '';
        $data['collect_list'] = $collect_info;
        $this->to_back($data);
    }
    
    public function register(){
        $data = $where =  array();
        $where['openid'] = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel(); 
        $nums = $m_user->countNum($where);
        if(empty($nums)){
            $data['openid'] = $this->params['openid'];
            $data['avatarUrl'] = $this->params['avatarUrl'] ? $this->params['avatarUrl'] :'';
            $data['nickName']  = $this->params['nickName'] ?$this->params['nickName']: '';
            $data['gender']    = $this->params['gender'];
            $data['status']    = 1;
            $ret = $m_user->addInfo($data,1);
            
            if($ret){
                $user_id = $m_user->getLastInsID();
                $data['user_id'] = $user_id;
                $this->to_back($data);
            }else {
                $this->to_back(91015);
            }
        }else {
            $user_info = $m_user->getOne('id user_id,openid,avatarUrl,nickName,gender', $where);
            
            $this->to_back($user_info);
        }
        $this->to_back(91014);
    }
    /**
     * @desc 获取我的公开记录
     */
    public function getMyPublic(){
        $openid = $this->params['openid'];
        $page   = $this->params['page'] ? intval($this->params['page']) : 1;
        
        //获取用户信息
        //$m_user = new \Common\Model\Smallapp\UserModel();
        //$user_info = $m_user->getOne('id,avatarUrl,nickName', array('openid'=>$openid,'status'=>1));
        
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
        $public_list = $m_public->getList($fields, $where, $order, $limit);
        
        $m_pubdetail = new \Common\Model\Smallapp\PubdetailModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        foreach($public_list as $key=>$v){
            $fields = "concat('".$oss_host."',`res_url`) res_url,duration";
            $where = array();
            $where['forscreen_id'] = $v['forscreen_id'];
            $pubdetail_info = $m_pubdetail->getWhere($fields, $where);
            if($v['res_type']==2){
                $pubdetail_info[0]['vide_img'] = $pubdetail_info[0]['res_url']."?x-oss-process=video/snapshot,t_3000,f_jpg,w_450,m_fast";
                $pubdetail_info[0]['duration'] = secToMinSec(intval($pubdetail_info[0]['duration']));
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
        }
        $data = array();
        //$data['user_info'] = $user_info;
        $data['list'] = $public_list;
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
            }
            $collect_info[$key]['create_time'] = date('n月j日',strtotime($v['create_time']));
            
            //收藏数量
            $map = array();
            $map['res_id'] =$v['res_id'];
            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);
            
            $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
            $ret = $m_collect_count->field('nums')->where(array('res_id'=>$v['res_id']))->find();
            $collect_info[$key]['collect_num'] = $collect_num + $ret['nums'];
            //分享个数
            $m_share = new \Common\Model\Smallapp\ShareModel();
            $map = array();
            $map['res_id'] =$v['res_id'];
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
     * @desc 删除我的公开
     */
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
        $ret = $m_collect->updateInfo($where, array('status'=>0));
        echo $m_collect->getLastSql();exit;
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(90107);
        }
    
    }
    
    public function test(){
        $m_user = new \Common\Model\Smallapp\UserModel(); 
        $data = $m_user->getOne('nickName', array('id'=>1));
        $this->to_back($data);
    }
}