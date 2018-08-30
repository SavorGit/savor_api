<?php
namespace Smallapp\Controller;
use Think\Controller;
use Common\Lib\Smallapp_api;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class ActivityController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            
            case 'getGameCode':
                $this->is_verify = 1;
                $this->valid_fields = array('scene'=>'1001');
                break;
            case 'orgGameLog':   //发起游戏
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001);
                break;
            case 'joinGameLog':
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001);
                break;   
            case 'startGameLog':
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001);
                break;
        }
        parent::_init_();
    }
    public function getGameCode(){
        
        $scene = $this->params['scene'];
        $r = $this->params['r'] !='' ? $this->params['r'] : 255;
        $g = $this->params['g'] !='' ? $this->params['g'] : 255;
        $b = $this->params['b'] !='' ? $this->params['b'] : 255;
        $m_small_app = new Smallapp_api();
        $tokens  = $m_small_app->getWxAccessToken();
        header('content-type:image/png');
        $data = array();
        $data['scene'] = $scene;//自定义信息，可以填写诸如识别用户身份的字段，注意用中文时的情况
        $data['page'] = "pages/activity/turntable/joingame";//扫描后对应的path
        $data['width'] = "280";//自定义的尺寸
        $data['auto_color'] = false;//是否自定义颜色
        $color = array(
            "r"=>$r,
            "g"=>$g,
            "b"=>$b,
        );
        $data['line_color'] = $color;//自定义的颜色值
        $data['is_hyaline'] = true;
        
        $data = json_encode($data);
        $m_small_app->getSmallappCode($tokens,$data);
    }
    /**
     * @desc 记录发起游戏日志
     * 
     */
    public function orgGameLog(){
        $activity_id = $this->params['activity_id'];
        $data = array();
        $m_turntable_log = new \Common\Model\Smallapp\TurntableLogModel();
        $data['activity_id'] = $activity_id;
        $data['join_num']    = 1;
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['is_start']    = 0;
        $ret = $m_turntable_log->addInfo($data);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(91012);
        }
    }
    /**
     * @desc 记录加入游戏日志
     */
    public function joinGameLog(){
        $activity_id = $this->params['activity_id'];
        $m_turntable_log = new \Common\Model\Smallapp\TurntableLogModel();
        $ret = $m_turntable_log->update_join_info($activity_id);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(91012);
        }
    }
    /**
     * @desc 记录开始游戏
     */
    public function startGameLog(){
        $activity_id = $this->params['activity_id'];
        $m_turntable_log = new \Common\Model\Smallapp\TurntableLogModel();
        $ret = $m_turntable_log->update_start_info($activity_id,1);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(91012);
        }
    }
}