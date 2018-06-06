<?php
/**
 * @desc 4G投屏
 * @since 2018-06-05
 * @author zhang.yingtao
 */

namespace Forscreen\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use Common\Lib\UmengNotice;
use \Common\Controller\CommonController as CommonController;
class IndexController extends CommonController{ 
    private $production_mode ;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'pushInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>'1001','resource_url'=>'1001');
                break;
            case 'removeInfo':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
        $this->production_mode =  C('UMENG_PRODUCTION_MODE');
    }
    /**
     * @desc 接收手机推送的机顶盒mac和要投屏的资源
     */
    public function pushInfo(){
        $data = array();
        $data['box_mac']      = $this->params['box_mac'];
        $data['resource_url'] = $this->params['resource_url'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = 'for_screen';
        $redis->rpush($cache_key, json_encode($data));
        $this->to_back(10000);
    }
    
    public function removeInfo(){
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = 'for_screen';
        $obj = new UmengNotice();
        $m_box = new \Common\Model\BoxModel();
        while(1==1){
            $len = $redis->lsize($cache_key);
            if(!empty($len)){
                $redis_value = $redis->lpop($cache_key);
                $data = json_decode($redis_value,true);
                $data_arr = pathinfo($data['resource_url']);
                $extension = strtolower($data_arr['extension']);
                if(in_array($extension, array('bmp','jpg','jpeg','png','gif'))){
                    $data['resource_type']  = 1;
                }else if($extension == 'mp4'){
                    $data['resource_type'] = 2;
                }
                
                $custom = array();
                $custom['type'] = 2;  //1:RTB  2:4G投屏
                $custom['data'] = $data;
                
                $type = 'listcast';
                $listcast = $obj->umeng_android($type);
                //设置属于哪个app
                $config_parm = 'boxclient';
                //设置app打开后选项
                $after_a = C('AFTER_APP');
                $listcast->setParam($config_parm);
                
                $fields = "d.id hotel_id , c.id room_id, a.id box_id,a.device_token";
                $where = array();
                $where['a.mac'] = $data['box_mac'];
                $ret = $m_box->getBoxInfo($fields, $where);
                if(empty($ret)) continue;   //如果没有该mac的机顶盒
                $info = $ret[0];
                if(empty($info['device_token']))  continue;   //如果该机顶盒的device_token为空 不发送推送
                
                
                $pam['device_tokens'] = $info['device_token'];
                $pam['time'] = time();
                $pam['ticker'] = '4G投屏';
                $pam['title'] = '4G投屏';
                $pam['text'] = '4G投屏';
                $pam['after_open'] = $after_a[3];
                $pam['production_mode'] = $this->production_mode;
                $pam['display_type'] = 'notification';
                
                $pam['custom'] = json_encode($custom);
                $listcast->sendAndroidListcast($pam);
                $m_push_log = new \Common\Model\PushLogModel();
                $push_list = array();
                $push_list['hotel_id'] = $info['hotel_id'];
                $push_list['room_id']  = $info['room_id'];
                $push_list['box_id']   = $info['box_id'];
                $push_list['push_info']= json_encode($custom);
                $push_list['push_time']= date('Y-m-d H:i:s');
                $push_list['push_type']= 2;
                $m_push_log->addInfo($push_list,1); 
            }    
        }
        
        $this->to_back(10000);
    }
}