<?php
namespace Games\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;
class ClimbTreeController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
    
        switch(ACTION_NAME) {
            case 'pushBox':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,);
                break;
            case 'launchGame':    //发起游戏
                $this->is_verify =1;
                $this->valid_fields = array('game_id'=>1001,'box_mac'=>1001);
                break;
            case 'startGame':
                $this->is_verify = 1;
                $this->valid_fields = array('game_id'=>1001,'activity_id'=>1001);
                break;
            case 'reportGameScore':
                $this->is_verify = 1;
                $this->valid_fields = array('game_id'=>1001,'activity_id'=>1001,'users_score'=>1001);
                break;
            case 'haveLaunchGame':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
            case 'clearLaunchGame':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
            case 'logoutGameH5':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
            case 'isHaveGameimg':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 发起游戏
     */
    public function launchGame(){
        $game_id   = $this->params['game_id'];   //猴子爬树游戏id
        
        $box_mac   = $this->params['box_mac'];   //机顶盒mac
        
        
        //注册游戏用户
        $game_user = new \Common\Model\Smallapp\GameUserModel();
        
        //添加活动
        $game_interact = new \Common\Model\Smallapp\GameInteractModel();
        $data = array();
        $data['game_id'] = $game_id;
        
        $data['box_mac'] = $box_mac;
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['is_start']= 0;
        $ret = $game_interact->addInfo($data);
        if($ret){//发起游戏成功
            $activity_id = $game_user->getLastInsID();
            $this->to_back(array('activity_id'=>$activity_id));
        }else {//发起游戏失败
            $this->to_back(90110);
        }
    }
    /**
     * @desc 开始游戏
     */
    public function startGame(){
        $game_id     =  $this->params['game_id'];
        $activity_id = $this->params['activity_id'];
        $m_game_interact    =  new \Common\Model\Smallapp\GameInteractModel();
        $where = array();
        $where['game_id'] = $game_id;
        $where['start_time'] = 0;
        $where['id'] = $activity_id;
        $nums = $m_game_interact->countNum($where);
        if(empty($nums)){//游戏已开始
            $this->to_back(90111);
        }else {
            $data = array();
            $data['start_time'] = getMillisecond();
            $data['is_start']   = 1;
            $ret = $m_game_interact->updateInfo($where, $data);
            if($ret){
                $this->to_back(10000);
            }else {
                $this->to_back(90112);
            }
        }
    }
    /**
     * @desc 游戏结束上报用户游戏数据
     */
    public function reportGameScore(){
        $game_id     = $this->params['game_id'];
        $activity_id = $this->params['activity_id'];
        $users_score = urldecode($this->params['users_score']);
        //$users_score = str_replace('\\', '', $users_score);
        //结束游戏
        $m_game_interact = new \Common\Model\Smallapp\GameInteractModel();
        $where = array();
        $where['id']      = $activity_id;
        $where['game_id'] = $game_id;
        $where['start_time'] = array('gt',0);
        $where['end_time'] = 0;
        $where['is_start'] = 1;
        
        $game_info = $m_game_interact->getOne('start_time', $where);
        if(empty($game_info)){
            $this->to_back(90113);
        }else {
            $m_game_climbtree = new \Common\Model\Smallapp\GameClimbtreeModel(); 
            $data = array();
            $end_time = getMillisecond();
            $data['end_time'] = $end_time;
            $m_game_interact->updateInfo($where, $data);
            $users_score = json_decode($users_score,true);
            
            $m_game_user = new \Common\Model\Smallapp\GameUserModel();
            //二维数组排序
            sortArrByOneField($users_score, 'rock_nums',true);
            
            $used_time = $end_time -  $game_info['start_time']  ;
            $game_rank = array();
            $flag = 1;
            $user_info = array();
            $climbtree = array();
            foreach($users_score as $key=>$v){
                $where = array();
                $where['openid'] = $v['openid'];
                $user_arr = $m_game_user->getOne('id,max_rock_rate', $where);
                $rock_rate = number_format(($v['rock_nums'] /$used_time) * 1000,3);
                if(empty($user_arr)){//未注册用户
                    
                    $user_info[$key]['openid']    = $v['openid'];
                    $user_info[$key]['avatarUrl'] = $v['avatarUrl'];
                    $user_info[$key]['nickName']  = $v['nickName'];
                    $user_info[$key]['gender']    = $v['gender'];
                    $user_info[$key]['status']    = 1;
                    
                    $max_rock_rate = $rock_rate;
                    $user_info[$key]['max_rock_rate'] = $max_rock_rate;
                    
                }else {//该用户已注册
                    //判断当前摇动速率是不是最大
                    
                    if($rock_rate>$user_arr['max_rock_rate']){
                        $max_rock_rate = $rock_rate;
                        $m_game_user->updateInfo($where, array('max_rock_rate'=>$rock_rate));
                    }else {
                        $max_rock_rate = $user_arr['max_rock_rate'];
                    }      
                }
                
                $users_score[$key]['rock_rate']   = $rock_rate;
                $game_rank[$key]['openid']        = $v['openid'];
                $game_rank[$key]['avatarUrl']     = $v['avatarUrl'];
                $game_rank[$key]['nickName']      = $v['nickName'];
                $game_rank[$key]['rock_rate'] =   $rock_rate.'次/秒';
                
                $climbtree[$key]['activity_id'] = $activity_id;
                $climbtree[$key]['openid']      = $v['openid']; 
                $climbtree[$key]['rock_nums']   = $v['rock_nums'];
                $climbtree[$key]['rock_rate']   = $rock_rate;
            }//end foreach
            
            
            $m_game_climbtree ->addInfo($climbtree,2);
            $m_game_user->addInfo($user_info,2);
            
            foreach($users_score as $key=>$v){
                $where = array();
                $where['rock_rate'] = array('gt',floatval ($v['rock_rate']));
                $high_nums = $m_game_climbtree->countNum($where);
                
                $game_rank[$key]['ranking'] = $high_nums +1 ;
            }
        }
        $fields = "openid,avatarUrl,nickName,concat(`max_rock_rate`,'次/秒') max_rock_rate";
        $where = array();
        $where['status'] = 1;
        $where['max_rock_rate'] = array('gt',0);
        $order = "max_rock_rate  desc,id desc";
        $limit = 'limit 0, 10';
        $game_all_rank = $m_game_user->getWhere($fields, $where, $order, $limit);
        
        $data = array();
        $data['game_rank'] = $game_rank;
        $data['game_all_rank'] = $game_all_rank;
        $this->to_back($data);
    }
    public function haveLaunchGame(){
        $box_mac = $this->params['box_mac'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_CALL_CLIMBTREE').$box_mac;
        $redis->set($cache_key, 1,60);
        $cache_key = C('SAPP_CALL_CLIMBTREE_LOGOUT').$box_mac;
        $redis->set($cache_key, 1,180);
        $this->to_back(10000);
    }
    public function isHaveLaunchGame(){
        $box_mac = $this->params['box_mac'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_CALL_CLIMBTREE').$box_mac;
        $info = $redis->get($cache_key);
        if(!empty($info)){
            $this->to_back(10000);
        }else {
            $this->to_back(90115);
        }
    }
    public function clearLaunchGame(){
        $box_mac = $this->params['box_mac'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_CALL_CLIMBTREE').$box_mac;
        $redis->remove($cache_key);
        $this->to_back(10000);
    }
    public function  logoutGameH5(){
        $box_mac = $this->params['box_mac'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_CALL_CLIMBTREE_LOGOUT').$box_mac;
        $redis->remove($cache_key);
        $this->to_back(10000);
    }
    public function isHaveGameimg(){
        $box_mac = $this->params['box_mac'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_CALL_CLIMBTREE_LOGOUT').$box_mac;
        $is_gaming = $redis->get($cache_key);
        if(empty($is_gaming)){
            $is_gaming = 0;
        }else {
            $is_gaming = 1;
        }
        //清除h5回调websocket链接成功
        $cache_key = C('SAPP_CALL_CLIMBTREE').$box_mac;
        $redis->remove($cache_key);
        $data['is_gaming'] = $is_gaming;
        $this->to_back($data);
    }
}