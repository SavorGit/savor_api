<?php
namespace Smallappsimple\Controller;
use Think\Controller;
use Common\Lib\Smallapp_api;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class ForscreenLogController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            
            case 'recordForScreen':
               $this->is_verify = 1;
               $this->valid_fields = array('openid'=>1001,'box_mac'=>1000,
                   'imgs'=>1000,'mobile_brand'=>1000,
                   'mobile_model'=>1000,'action'=>1001,
                   'resource_type'=>1000,'resource_id'=>1000,
                   'is_pub_hotelinfo'=>1000,'is_share'=>1000,
                   'forscreen_id'=>1000,'small_app_id'=>1000,
                   'small_app_id'=>1001,'create_time'=>1000,
                   'res_sup_time'=>1002,'res_eup_time'=>1002
               );
               break;
            case 'updateForscreen':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'forscreen_id'=>1001,'resource_id'=>1001,
                                            'resource_addr'=>1001);
               break;
            case 'updateForscreenPlaytime':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'forscreen_id'=>1001,'resource_id'=>1001,
                    'box_playstime'=>1002,'box_playetime'=>1002);
                break;
        }
        parent::_init_();
    }
    public function recordForScreen(){
        $forscreen_id = $this->params['forscreen_id'] ? $this->params['forscreen_id'] :0;            //投屏id
        $openid = $this->params['openid'];                                                           //openid
        $box_mac = $this->params['box_mac'];                                                         //机顶盒mac
        $mobile_brand = $this->params['mobile_brand'];                                               //手机品牌
        $mobile_model = $this->params['mobile_model'];                                               //手机型号
        $forscreen_char = $this->params['forscreen_char'];                                           //投屏文字
        $imgs    = str_replace("\\", '', $this->params['imgs']);                                     //投屏资源
        $action  = $this->params['action'] ? $this->params['action'] : 0;                            //投屏动作 图片：4 滑动：2 视频：2
        $resource_type = $this->params['resource_type'] ? $this->params['resource_type'] : 0;        //1：滑动   2：视频
        $resource_id   = $this->params['resource_id'] ? $this->params['resource_id'] : 0;            //filename
        $resource_size = $this->params['resource_size'] ? $this->params['resource_size'] :0;         //资源大小
        $res_sup_time  = $this->params['res_sup_time'] ? $this->params['res_sup_time'] : 0;     
        $res_eup_time  = $this->params['res_eup_time'] ? $this->params['res_eup_time'] : 0;    
        $is_pub_hotelinfo = $this->params['is_pub_hotelinfo'] ?$this->params['is_pub_hotelinfo']:0;  //是否显示酒楼
        $is_share      = $this->params['is_share'] ? $this->params['is_share'] : 0;                  //是否公开
        $duration      = $this->params['duration'] ? $this->params['duration'] : 0.00;               //视频时长
        $small_app_id  = $this->params['small_app_id'] ? $this->params['small_app_id'] :1;
        $create_time   = $this->params['create_time'] ? date('Y-m-d H:i:s',$this->params['create_time']/1000) :  date('Y-m-d H:i:s');
        $serial_number = $this->params['serial_number'] ? $this->params['serial_number'] :'';
        $data = array();
        $data['forscreen_id'] = $forscreen_id;
        $data['openid'] = $openid;
        $data['box_mac']= $box_mac;
        $data['action'] = $action;
        $data['resource_type'] = $resource_type;
        $data['resource_id']   = intval($resource_id);
        $data['mobile_brand'] = $mobile_brand;
        $data['mobile_model'] = $mobile_model;
        $data['imgs']   = $imgs ? $imgs :'[]';
        $data['forscreen_char'] = !empty($forscreen_char) ? $forscreen_char : '';
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['res_sup_time']= $res_sup_time;
        $data['res_eup_time']= $res_eup_time;
        $data['resource_size'] = $resource_size;
        $data['is_pub_hotelinfo'] = $is_pub_hotelinfo;
        $data['is_share']    = $is_share;
        $data['duration']    = $duration;
        $data['small_app_id']= $small_app_id;
        if(!empty($serial_number)) $data['serial_number'] = $serial_number;
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_SCRREN').":".$box_mac;
        $redis->rpush($cache_key, json_encode($data));
        
        
        //模拟机顶盒上报下载数据  防止小程序提示打断
        $map = array();
        $map['forscreen_id'] = $forscreen_id;
        $map['resource_id']  = $resource_id;
        $map['openid']       = $openid;
        $map['box_mac']      = $box_mac;
        $map['is_exist']     = 0;
        $map['is_break']     = 0;
        $map['used_time'] =   0;
        $now_time = getMillisecond();
        $map['box_res_sdown_time'] = 0;
        $map['box_res_edown_time'] = 0;
        $cache_key = C('SAPP_BOX_FORSCREEN_NET').$box_mac;
        $redis->rpush($cache_key, json_encode($map));
        
        $this->to_back(10000);
    }
    public function updateForscreen(){
        $data = array();
        $box_mac = $this->params['box_mac'];
        $forscreen_id = $this->params['forscreen_id'] ? $this->params['forscreen_id'] :0;
        $imgs    = str_replace("\\", '', $this->params['resource_addr']);
        $resource_id   = $this->params['resource_id'] ? $this->params['resource_id'] : 0;
        
        $data['box_mac']      = $box_mac;
        $data['forscreen_id'] = $forscreen_id;
        $data['imgs']         = $imgs;
        $data['resource_id']  = intval($resource_id);
        
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_SIMPLE_UPLOAD_RESOUCE').$box_mac;
        $redis->rpush($cache_key, json_encode($data));
        
        $this->to_back(10000);
        
    }

    public function updateForscreenPlaytime(){
        $box_mac = $this->params['box_mac'];
        $forscreen_id = $this->params['forscreen_id'] ? $this->params['forscreen_id'] :0;
        $resource_id   = $this->params['resource_id'] ? $this->params['resource_id'] : 0;
        $box_playstime = $this->params['box_playstime'];
        $box_playetime = $this->params['box_playetime'];
        if(empty($box_playstime) && empty($box_playetime)){
            $this->to_back(1001);
        }
        $data = array('box_mac'=>$box_mac,'forscreen_id'=>$forscreen_id,'resource_id'=>intval($resource_id));
        if(!empty($box_playstime))  $data['box_playstime'] = $box_playstime;
        if(!empty($box_playetime))  $data['box_playetime'] = $box_playetime;
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_SIMPLE_UPLOAD_PLAYTIME').$box_mac;
        $redis->rpush($cache_key, json_encode($data));

        $this->to_back(10000);

    }
}