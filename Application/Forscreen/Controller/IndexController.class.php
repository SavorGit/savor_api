<?php
/**
 * @desc 4G投屏接收客户端信息（接收开始投屏、结束投屏信息）
 * @since 2018-06-05
 * @author zhang.yingtao
 */

namespace Forscreen\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use Common\Lib\UmengNotice;
use \Common\Controller\BaseController as BaseController;
class IndexController extends BaseController{ 
    var $box_mac_arr;
    /**
     * 构造函数 
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'receiveStartInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>'1000','resource_url'=>'1001');
                break;
            case 'receiveStopInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>'1000');
                break;
            
        }
        parent::_init_(); 
        $this->box_mac_arr = array('00226D2FB217','00226D2FB21D','00226D2FB26D');
    }
    /**
     * @desc 接收手机推送的机顶盒mac和要投屏的资源
     */
    public function receiveStartInfo(){
        
        $box_mac = $this->params['box_mac'] ? $this->params['box_mac'] :$this->box_mac_arr;
        $resource_url = $this->params['resource_url'];
        if(!is_array($box_mac)){
            $box_mac_arr[] = $box_mac;
            $box_mac = $box_mac_arr;
        }
        
        $obj = new UmengNotice();
        $m_box = new \Common\Model\BoxModel();
        $m_push_log = new \Common\Model\PushLogModel();
        $device_token  = '';
        $push_box_mac = '';
        foreach($box_mac as $v){
            $data = array();
            $data['box_mac']      = $v;
            $data['resource_url'] = $resource_url;
            $fields = "d.id hotel_id , c.id room_id, a.id box_id,a.device_token";
            $where = array();
            $where['a.mac'] = $v;

            $ret = $m_box->getBoxInfo($fields, $where);
            $info = $ret[0];
            $push_box_mac .=$space .$v;
            $device_tokens .= $space1.$info['device_token'];
            $data_arr = pathinfo($data['resource_url']);
            $data_arr_1 = parse_url($data['resource_url']);
            $data['resource_url'] = substr($data_arr_1['path'], 1);
            $data['resource_name'] = $data_arr['basename'];
            $extension = strtolower($data_arr['extension']);
            if(in_array($extension, array('bmp','jpg','jpeg','png','gif'))){
                $data['resource_type']  = 1;
            }else if($extension == 'mp4'){
                $data['resource_type'] = 2;
            }
            $custom = array();
            $custom['type'] = 2;  //1:RTB  2:4G投屏
            $custom['action'] = 1; //1:投屏  0:结束投屏
            $custom['data'] = $data;
            
            $push_list = array();
            $push_list['hotel_id'] = $info['hotel_id'];
            $push_list['room_id']  = $info['room_id'];
            $push_list['box_id']   = $info['box_id'];
            $push_list['push_info']= json_encode($custom);
            $push_list['push_time']= date('Y-m-d H:i:s');
            $push_list['push_type']= 2;
            $m_push_log->addInfo($push_list,1);
            $space = $space1 = ',';
        }
       
        $data['box_mac'] = $push_box_mac;
        $custom = array();
        $custom['type'] = 2;  //1:RTB  2:4G投屏
        $custom['action'] = 1; //1:投屏  0:结束投屏
        $custom['data'] = $data;
        
        $type = 'listcast';
        $listcast = $obj->umeng_android($type);
        //设置属于哪个app
        $config_parm = 'boxclient';
        //设置app打开后选项
        $after_a = C('AFTER_APP');
        $listcast->setParam($config_parm);
        
        $pam['device_tokens'] = $device_tokens;
        $pam['time'] = time();
        $pam['ticker'] = '4G投屏';
        $pam['title'] = '4G投屏';
        $pam['text'] = '4G投屏';
        $pam['after_open'] = $after_a[3];
        $pam['production_mode'] = $this->production_mode;
        $pam['display_type'] = 'notification';
        $pam['custom'] = json_encode($custom);
        $listcast->sendAndroidListcast($pam);
        
        
        
        $this->to_back(10000);
    }
    /**
     * @desc 接收投屏结束指令
     */
    public function receiveStopInfo(){
        
        $box_mac = $this->params['box_mac'] ? $this->params['box_mac'] :$this->box_mac_arr;
        if(!is_array($box_mac)){
            $box_mac_arr[] =$box_mac;
            $box_mac = $box_mac_arr;
        }
        $obj = new UmengNotice();
        $m_box = new \Common\Model\BoxModel();
        $m_push_log = new \Common\Model\PushLogModel();
        $device_tokens  = '';
        $push_box_mac = '';
        
        foreach($box_mac as $v){
            $data = array();
            $fields = "d.id hotel_id , c.id room_id, a.id box_id,a.device_token";
            $where = array();
            $where['a.mac'] = $v;
            $ret = $m_box->getBoxInfo($fields, $where);
            $info = $ret[0];
            $push_box_mac .=$space .$v;
            $device_tokens .= $space1.$info['device_token'];
            $custom = array();
            $custom['type'] = 2;  //1:RTB  2:4G投屏
            $custom['action'] = 1; //1:投屏  0:结束投屏
            $custom['data'] = $data;
            
            //记录推送日志
            $push_list = array();
            $push_list['hotel_id'] = $info['hotel_id'];
            $push_list['room_id']  = $info['room_id'];
            $push_list['box_id']   = $info['box_id'];
            $push_list['push_info']= json_encode($custom);
            $push_list['push_time']= date('Y-m-d H:i:s');
            $push_list['push_type']= 2;
            $m_push_log->addInfo($push_list,1);
            $space = $space1 = ',';
        }
        $data['box_mac'] = $push_box_mac;
        $custom = array();
        $custom['type']   = 2;  //1:RTB  2:4G投屏
        $custom['action'] = 0;  //1:投屏  0:结束投屏
        $custom['data']   = $data;
        
        $type = 'listcast';
        $listcast = $obj->umeng_android($type);
        //设置属于哪个app
        $config_parm = 'boxclient';
        //设置app打开后选项
        $after_a = C('AFTER_APP');
        $listcast->setParam($config_parm);
        
        $pam['device_tokens'] = $device_tokens;
        $pam['time'] = time();
        $pam['ticker'] = '4G投屏';
        $pam['title'] = '4G投屏';
        $pam['text'] = '4G投屏';
        $pam['after_open'] = $after_a[3];
        $pam['production_mode'] = $this->production_mode;
        $pam['display_type'] = 'notification';
        
        $pam['custom'] = json_encode($custom);
        $listcast->sendAndroidListcast($pam);
        
        $this->to_back(10000);
    }
}