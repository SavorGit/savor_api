<?php
/**
 * @desc   小程序埋点
 * @author zhang.yingtao
 * @since  2018-09-05
 */
namespace Smallapp\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class BuriedPointController extends CommonController{
    /**
     * 构造函数
     */
    function _init_(){
        switch (ACTION_NAME){
            case  'activity':
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001,'action'=>1001,
                                            'order_time'=>1001,'openid'=>1000);
            break;
            case 'images':
                $this->is_verify = 1;
                $this->valid_fields = array('img_id'=>1001,'action'=>1001,'order_time'=>1001,'openid'=>1001);
            break;
            case 'videos':
                $this->is_verify = 1;
                $this->valid_fields = array('video_id'=>1001,'action'=>1001,'order_time'=>1001);
            break;
            case 'sunCodeLog':
                $this->is_verify = 1;
                $this->valid_fields = array('id'=>1001,'media_id'=>1001,'box_mac'=>1001,
                                            'log_time'=>1001,'action'=>1001
                );
            break;
        }
        parent::_init_();
        
    }
    /**
     * @desc 互动游戏埋点
     */
    public function activity(){
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        
        $activity_id = $this->params['activity_id'];  //游戏id
        $action      = $this->params['action'];       //1：机顶盒收到发起游戏2：机顶盒收到用户加入信息3：机顶盒收到开始指令
        $order_time  = $this->params['order_time'];   //对应指令时间
        $openid      = $this->params['openid'];       //微信用户唯一标识
        $cache_key = C("SAPP_PLAY_GAME").":".$activity_id;
        $data = array();
        if($action==1){
            
            $data['activity_id']     = $activity_id;
            $data['box_orggame_time'] = getMillisecond();
            
        }else if($action==2){
            
            $data['activity_id']      = $activity_id;
            $data['openid']           = $openid;
            $data['box_join_time'] = getMillisecond();;
            
        }else if($action==3){
            $m_turntable_log = new \Common\Model\Smallapp\TurntableLogModel();
            $data['activity_id']       = $activity_id;
            $data['box_startgame_time'] = getMillisecond();
            
        }
        $redis->rpush($cache_key, json_encode($data));
        $this->to_back(10000);
    }
    /**
     * @desc 图片投屏埋点
     */
    public function images(){
        $img_id     = $this->params['img_id'];
        $openid     = $this->params['openid'];
        $action     = $this->params['action'];
        $order_time = $this->params['order_time'];
        $data = array();
        $m_forscreen_record = new \Common\Model\Smallapp\ForscreenRecordModel();
        $data['resource_id'] = $img_id;
        $data['openid']      = $openid;
        if($action==1){
            $data['box_res_sdown_time'] = getMillisecond();
        }else if($action==2){
            $data['box_res_edown_time'] = getMillisecond();
        }
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_UPRES_FORSCREEN').":".$openid;
        $redis->rpush($cache_key, json_encode($data));
        $this->to_back(10000);
        
    }
    /**
     * @desc 视频投屏埋点
     */
    public function videos(){
        $video_id = $this->params['video_id'];
        $action     = $this->params['action'];
        $openid     = $this->params['openid'];
        $order_time = $this->params['order_time'];
        $data = array();
        $m_forscreen_record = new \Common\Model\Smallapp\ForscreenRecordModel();
        $data['resource_id'] = $video_id;
        if($action==1){
            $data['box_res_sdown_time'] = getMillisecond();
        }else if($action==2){
            $data['box_res_edown_time'] = getMillisecond();
        }
        $data['openid']      = $openid;
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_UPRES_FORSCREEN').":".$openid;
        $redis->rpush($cache_key, json_encode($data));
        $this->to_back(10000);
        
        /* $ret = $m_forscreen_record->updateInfo($where, $data);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(91013);
        } */
    }
    /**
     * @desc 小程序码显示日志
     */
    public function sunCodeLog(){
        $this->to_back(10000);
        /*$data = array();
        $action    = $this->params['action'];
        $data['log_id']        = $this->params['id'];
        $data['media_id']  = $this->params['media_id'];
        $data['box_mac']   = $this->params['box_mac'];
        
        if($action==1){
            $data['start_time']      = $this->params['log_time'];
        }else if($action==2){
            $data['end_time']      = $this->params['log_time'];
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_SUNCODE_LOG').$data['box_mac'] ; 
        $redis->rpush($cache_key, json_encode($data));
        $this->to_back(10000);*/
    }
}