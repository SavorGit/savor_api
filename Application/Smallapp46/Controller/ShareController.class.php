<?php
namespace Smallapp46\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class ShareController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'recLogs':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'res_id'=>1001,'type'=>1001);
            break;
            case 'showVideo':
                $this->is_verify = 1;
                $this->valid_fields = array('res_id'=>1001,'type'=>1001,'openid'=>1000);
                break;
        }
        parent::_init_();
    }
    /**
     * 分享记录日志
     */
    public function recLogs(){
        $openid = $this->params['openid'];
        $res_id  = $this->params['res_id'];
        $type    = $this->params['type'];
        $status  = 1;
        $data = array();
        $data['openid'] = $openid;
        $data['res_id']  = $res_id;
        $data['type']    = $type;
        $data['status']  = $status;
        $m_share = new \Common\Model\Smallapp\ShareModel();
        $ret = $m_share->addInfo($data,1);
        if($ret){
            $nums = $m_share->countNum(array('res_id'=>$res_id,'status'=>1));
            
            $this->to_back(array('share_nums'=>$nums));
        }else {
            $this->to_back(90106);
        }
    }
    public function showVideo(){
        $res_id = $this->params['res_id'];
        $type = $this->params['type'];
        $openid = $this->params['openid'];
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $info = array();
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $m_play_log= new \Common\Model\Smallapp\PlayLogModel();
        if($type==3){//投屏视频
            
            $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
            
            
            $fields = 'ads.id res_id,ads.name title,ads.img_url,ads.duration,media.oss_addr video_url';
            $where = array();
            $where['ads.id'] = $res_id;
            
            
            $info = $m_program_menu_item->alias('a')
            ->join('savor_ads ads on a.ads_id = ads.id','left')
            ->join('savor_media media on ads.media_id =media.id ','left')
            ->field($fields)
            ->where($where)
            
            ->find();
            
            
            //收藏个数
            $map = array();
            $map['res_id'] =$res_id;
            $map['type']   = $type;
            
            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map); 
            $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
            $ret = $m_collect_count->field('nums')->where(array('res_id'=>$res_id))->find();
            //分享个数
            $map = array();
            $map['res_id'] =$res_id;
            $map['type']   = $type;
            $map['status'] = 1;
            $share_num = $m_share->countNum($map);
            
            //播放次数
            $map = array();
            $map['res_id'] = $res_id;
            $map['type']   = $type;
            $play_info = $m_play_log->getOne('nums',$map);
            $play_num  = intval($play_info['nums']);
            
            $info['img_url'] = $oss_host.$info['img_url'];
            $info['video_url']=$oss_host.$info['video_url'];
            $info['play_nums'] = $play_num;
            $info['share_nums'] = $share_num ;
            $info['collect_nums'] = $collect_num + $ret['nums'];
            $info['avatarUrl'] = '';
            $info['nickName']  = '';
            $info['type']  = 3;
        }else if($type==2){//节目单视频
            
            $where = array();
            $where['a.forscreen_id'] = $res_id;
            $m_public = new \Common\Model\Smallapp\PublicModel();
            $info = $m_public->alias('a')
                     ->join('savor_smallapp_pubdetail b on a.forscreen_id = b.forscreen_id','left')
                     ->join('savor_smallapp_user c on a.openid=c.openid','left')
                     ->field('res_url,avatarUrl,nickName')
                     ->where($where)
                     ->find();
            
            //收藏个数
            $map = array();
            $map['res_id'] =$res_id;
            $map['type']   = $type;
            
            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);
            $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
            $ret = $m_collect_count->field('nums')->where(array('res_id'=>$res_id))->find();
            //分享个数
            $map = array();
            $map['res_id'] =$res_id;
            $map['type']   = $type;
            $map['status'] = 1;
            $share_num = $m_share->countNum($map);
            
            //播放次数
            $map = array();
            $map['res_id'] = $res_id;
            $map['type']   = $type;
            $play_info = $m_play_log->getOne('nums',$map);
            $play_num  = intval($play_info['nums']);
            
            $info['res_id'] = $res_id;
            $info['img_url'] = $oss_host.$info['res_url']."?x-oss-process=video/snapshot,t_3000,f_jpg,w_450,m_fast";
            $info['video_url']=$oss_host.$info['res_url'];
            $info['play_nums'] = $play_num;
            $info['share_nums'] = $share_num;
            $info['collect_nums'] = $collect_num +$ret['nums'];
            $info['type']  = 2;
        }
        $map = array();
        $map['openid']=$openid;
        $map['res_id'] =$res_id;
        
        $map['status'] = 1;
        $is_collect = $m_collect->countNum($map);
        if(empty($is_collect)){
            $info['is_collect'] = 0;
        }else {
            $info['is_collect'] = 1;
        }
        $this->to_back($info);
    }
}