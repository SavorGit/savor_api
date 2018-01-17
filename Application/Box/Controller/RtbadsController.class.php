<?php
namespace Box\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use Common\Lib\UmengNotice;
use \Common\Controller\CommonController as CommonController;
class RtbadsController extends CommonController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'pushToBox':
                $this->is_verify = 1;
                $this->valid_fields = array('probe'=>'1001');
                break;
           
        }
        parent::_init_();
       
    }
    public function pushToBox(){
        $probe = $this->params['probe'];
        $probe = str_replace('\\', '', $probe);
        $probe = json_decode($probe,true);
        
        
        $m_room = new \Common\Model\RoomModel();
        $m_box = new \Common\Model\BoxModel();
        $device_tokens = '';
        foreach($probe as $key=>$v){
            $where = array();
            $tmp = explode(':', $v);
            $probe_str = $tmp[2];
            $where['probe'] = $probe_str;
            $where['flag']  = 0;
            $room_info = $m_room->getOne('id',$where);
            if(empty($room_info)){
                continue;
            }
            //获取包间下的机顶盒
            
            $where = array();
            $where['room_id'] = $room_info['id'];
            $where['flag']    = '0';
            $where['state']   = 1;
            $where['device_token'] = array('neq','');
            
            $box_list = $m_box->field('id,device_token')->where($where)->select();
            //print_r($box_list);exit;
            foreach($box_list as $ks=>$vs){
                if(!empty($vs['device_token'])){
                    $device_tokens  .= $space . $vs['device_token'];
                    $space = ",";
                }
            }
        }
        if(empty($device_tokens)){
            $this->to_back(70005);
        }

        $redis = new SavorRedis();
        $redis->select(11);
        $cacke_key = $probe[0];
        $ads_list = $redis->get($cacke_key);
        $ads_list = json_decode($ads_list,true);
        $custom = array();
        $custom['type'] = 1;
        $custom['data'] = $ads_list;
        
        
        $obj = new UmengNotice();
        $type = 'listcast';
        $listcast = $obj->umeng_android($type);
        //设置属于哪个app
        $config_parm = 'boxclient';
        //设置app打开后选项
        $after_a = C('AFTER_APP');
        $listcast->setParam($config_parm);
        //$pam['device_tokens'] = 'AqWNvmADF_1bqndJXoPF6ZqPBSNz--iRzfGQMy-E_n9P,AtBHUz8wGEqACpVAX8iZ5m1O-HkiWvqFviS09x8aYd6A';
        $pam['device_tokens'] = $device_tokens;
        $pam['time'] = time();
        $pam['ticker'] = 'RTB广告推送';
        $pam['title'] = 'RTB广告推送';
        $pam['text'] = 'RTB广告推送';
        $pam['after_open'] = $after_a[3];
        $pam['production_mode'] = 'false';
        
        $pam['custom'] = json_encode($custom);
        $listcast->sendAndroidListcast($pam);
        $this->to_back(10000);
    }
}