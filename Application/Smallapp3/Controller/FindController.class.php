<?php
namespace Smallapp3\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class FindController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'index':    //获取精选公开内容
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001);
                break;
            case 'showPic':
                $this->is_verify = 1;
                $this->valid_fields = array('forscreen_id'=>1001,'openid'=>1001);
                break;
            
        }
        parent::_init_();
    }
    /**
     * @desc 获取精选公开内容
     */
    public function index(){
        $openid = $this->params['openid'];
        $page   = intval($this->params['page']) ? intval($this->params['page']) :1;
        
        $pagesize = 10;
        
        //获取用户的好友列表
        $m_friend = new \Common\Model\Smallapp\FriendModel();
        $fields = "f_openid";
        $where =array();
        $where['status'] = 1;
        
        $friend_list = $m_friend->getWhere($fields, $where);
        //print_r($friend_list);exit;
        //$friend_list = array();
        $m_public = new \Common\Model\Smallapp\PublicModel();
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $m_pubdetail = new \Common\Model\Smallapp\PubdetailModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $public_list = array();
        
        $all_nums = $page * $pagesize;
        $order = 'a.create_time desc';
        if(!empty($friend_list)){//如果该用户有朋友公开的内容
         
        }else {//如果该用户没有朋友公开的内容
            //获取系统推荐
            $where = array();
            
            $where['a.is_recommend'] = 1;
            $where['a.status']       = 2;
              
            $where['box.flag']       = 0;
            $where['box.state']      = 1;
            $where['hotel.flag']     = 0;
            $where['hotel.state']    = 1;
            $rec_nums = $m_public->countWhere($where);
            $limit = "limit 0,".$all_nums;
            $fields= 'a.forscreen_id,a.res_type,a.res_nums,a.is_pub_hotelinfo,
                    a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
            $order = 'a.create_time desc';
            $public_list = $m_public->getList($fields, $where, $order, $limit);
            
        }
        foreach($public_list as $key=>$v){
            
            if(empty($v['avatarUrl'])){
                $public_list[$key]['avatarUrl'] = 'http://oss.littlehotspot.com/WeChat/MiniProgram/LaunchScreen/source/images/imgs/default_user_head.png';
                
            }
            if(empty($v['nickName'])){
                $public_list[$key]['nickName'] = '游客';
            }
            $fields = "concat('".$oss_host."',`res_url`) res_url, res_url as forscreen_url, duration,resource_size";
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
            $map = array();
            $map['openid']=$openid;
            $map['res_id'] =$v['forscreen_id'];
            //$map['type']   = 1;
            $map['status'] = 1;
            $is_collect = $m_collect->countNum($map);
            if(empty($is_collect)){
                $public_list[$key]['is_collect'] = "0";
            }else {
                $public_list[$key]['is_collect'] = "1";
            }
            $public_list[$key]['pubdetail'] = $pubdetail_info;
            //echo $v['create_time'];exit;
            $public_list[$key]['create_time'] = viewTimes(strtotime($v['create_time']));
            //收藏个数
            $map = array();
            $map['res_id'] =$v['forscreen_id'];
            $map['type']   = 2;
            
            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);
            
            $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
            $ret = $m_collect_count->field('nums')->where(array('res_id'=>$v['forscreen_id']))->find();
            
            $public_list[$key]['collect_num'] = $collect_num + $ret['nums'];
            //分享个数
            $map = array();
            $map['res_id'] =$v['forscreen_id'];
            $map['type']   = 2;
            $map['status'] = 1;
            $share_num = $m_share->countNum($map);
            $public_list[$key]['share_num'] = $share_num;
            
            
        }
        $this->to_back($public_list);
    }
    public function showPic(){
        $forscreen_id = $this->params['forscreen_id'];
        $openid       = $this->params['openid'];
        $m_public = new \Common\Model\Smallapp\PublicModel();
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $m_pubdetail = new \Common\Model\Smallapp\PubdetailModel();
        
        
        //播放数据+1
        $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
        $nums = $m_play_log->countNum(array('res_id'=>$forscreen_id));
        if(empty($nums)){
            $data = array();
            $data['res_id'] = $forscreen_id;
            $data['type']   = 2;
            $data['nums']   = 1;
            $m_play_log->addInfo($data);
        }else {
            $where = array();
            $where['res_id'] = $forscreen_id;
            $where['type']   = 2;
            $m_play_log->where($where)->setInc('nums',1);
        
        }
        
        //echo $v['create_time'];exit;
        $fields= 'a.forscreen_id,a.forscreen_char,a.public_text,a.res_type,a.res_nums,a.is_pub_hotelinfo,
                    a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
        $order = 'a.res_type desc,a.create_time desc';
        $rec_pub_list = $m_public->getList($fields, array('forscreen_id'=>$forscreen_id));
        $pub_info = $rec_pub_list[0];
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $field = "forscreen_id,resource_id,openid,box_mac,resource_type,imgs";
        $where = array();
        $where['forscreen_id'] = $forscreen_id;
        $fields = "concat('".$oss_host."',`res_url`) res_url, res_url as forscreen_url,duration,resource_size";
        $where = array();
        $where['forscreen_id'] = $forscreen_id;
        $pubdetail_info = $m_pubdetail->getWhere($fields, $where);
        foreach($pubdetail_info as $kk=>$vv){
            if($pub_info['res_type']==2){
                $filename = explode('/', $pubdetail_info[0]['forscreen_url']);
                
                $pubdetail_info[$kk]['filename'] = $filename[2];
                $tmp_arr = explode('.', $filename[2]);
                $pubdetail_info[$kk]['res_id']   = $tmp_arr[$kk];
                $pubdetail_info[$kk]['vide_img'] = $pubdetail_info[$kk]['res_url']."?x-oss-process=video/snapshot,t_3000,f_jpg,w_450,m_fast";
                $pubdetail_info[$kk]['duration'] = secToMinSec(intval($pubdetail_info[$kk]['duration']));
            }else{
                $filename = explode('/', $vv['forscreen_url']);
                
                $pubdetail_info[$kk]['filename'] = $filename[2];
                $tmp_arr = explode('.', $filename[2]);
                $pubdetail_info[$kk]['res_id']   = $tmp_arr[0];
            }
            
            
        }
       
        $map = array();
        $map['openid']=$openid;
        $map['res_id'] =$forscreen_id;
        
        $map['status'] = 1;
        $is_collect = $m_collect->countNum($map);
        
        
        $data = array();
        if(!empty($rec_pub_list)){
            
            if(empty($pub_info['avatarUrl'])){
                $pub_info['avatarUrl'] = 'http://oss.littlehotspot.com/WeChat/MiniProgram/LaunchScreen/source/images/imgs/default_user_head.png';
            
            }
            if(empty($pub_info['nickName'])){
                $pub_info['nickName'] = '游客';
            }
            $create_time = viewTimes(strtotime($pub_info['create_time']));
            //收藏个数
            $map = array();
            $map['res_id'] =$forscreen_id;
            $map['type']   = 2;
            
            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);
            $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
            $ret = $m_collect_count->field('nums')->where(array('res_id'=>$forscreen_id))->find();
            //分享个数
            $map = array();
            $map['res_id'] =$forscreen_id;
            $map['type']   = 2;
            $map['status'] = 1;
            $share_num = $m_share->countNum($map);
            
            //播放次数
            $map = array();
            $map['res_id'] = $forscreen_id;
            $map['type']   = 2;
            $play_info = $m_play_log->getOne('nums',$map);
            $play_num  = intval($play_info['nums']);
            
            if(empty($is_collect)){
                $pub_info['is_collect'] = "0";
            }else {
                $pub_info['is_collect'] = "1";
            }
            $pub_info['create_time'] = $create_time;
            $pub_info['collect_num'] = $collect_num + $ret['nums'];
            $pub_info['share_num']   = $share_num;
            $pub_info['play_num']    = $play_num;
            
            $pub_info['pubdetail']   = $pubdetail_info;
            $this->to_back($pub_info);
        }else {
            $this->to_back($data);
        }
    }
    
}
