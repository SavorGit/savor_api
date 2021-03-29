<?php
namespace Box\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
class BoxLogController extends CommonController{ 
    var $box_log_arr;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'isUploadLog':
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
            case 'adsPlaylog':
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1001,'ads_id'=>1001);
                break;
            case 'welcomePlaylog':
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1001,'welcome_id'=>1001);
                break;
        }
        parent::_init_();
        //log_type 0：关闭  1：日志文件 2:文件下载情况 3：异常  4：遥控器按键日志 5：重启日志
        $this->box_log_arr = array(array('box_mac'=>'00226D655202','log_type'=>0),
                                   array('box_mac'=>'FCD5D900B44A','log_type'=>0),
                                   array('box_mac'=>'00226D584178','log_type'=>0),
                                   array('box_mac'=>'FCD5D900B3BD','log_type'=>0),
                                   array('box_mac'=>'00226D583D92','log_type'=>0),
                                   array('box_mac'=>'00226D583CF4','log_type'=>2),
                                   array('box_mac'=>'00226D5841E7','log_type'=>2),
        ); 
    }
    public function isUploadLog(){
        $box_mac = $this->params['box_mac'];
        $box_arr = array_column($this->box_log_arr, 'box_mac');
        $data = array();
        if(in_array($box_mac, $box_arr)){
            foreach($this->box_log_arr as $key=>$v){
                if($box_mac == $v['box_mac']){
                    $data['log_type'] = $v['log_type'];
                    break;
                }
            }
            $this->to_back($data);
        }else {
            $this->to_back(70001);
        }
        
    }

    public function adsPlaylog(){
        $box_mac = $this->params['box_mac'];
        $ads_id = intval($this->params['ads_id']);
        $m_box = new \Common\Model\BoxModel();
        $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);

        if(isset($forscreen_info['box_id'])){
            $redis = new \Common\Lib\SavorRedis();
            $box_id = intval($forscreen_info['box_id']);
            $redis->select(15);
            $cache_key = 'savor_box_'.$box_id;
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info,true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $cache_key = 'savor_hotel_' . $room_info['hotel_id'];
            $redis_hotel_info = $redis->get($cache_key);
            $res_hotel = json_decode($redis_hotel_info, true);

            $data = array('ads_id'=>$ads_id,'hotel_id'=>$room_info['hotel_id'],'hotel_name'=>$res_hotel['name'],'hotel_box_type'=>$res_hotel['hotel_box_type'],
                'room_id'=>$box_info['room_id'],'room_name'=>$room_info['name'],'box_id'=>$box_id,'box_mac'=>$box_info['mac'],'box_type'=>$box_info['box_type'],
                'area_id'=>$res_hotel['area_id']
            );
            $m_area = new \Common\Model\AreaModel();
            $res_area = $m_area->find($data['area_id']);
            $data['area_name'] = $res_area['region_name'];
            $m_adsplaylog = new \Common\Model\Smallapp\AdsplaylogModel();
            $m_adsplaylog->add($data);

            $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
            $res_play = $m_play_log->getOne('*',array('res_id'=>$ads_id,'type'=>3),'id desc');
            if(empty($res_play)){
                $data = array('res_id'=>$ads_id,'type'=>3,'nums'=>1);
                $m_play_log->addInfo($data);
            }else{
                $m_play_log->where(array('id'=>$res_play[0]['id']))->setInc('nums',1);
            }
        }
        $this->to_back(array());
    }

    public function welcomePlaylog(){
        $box_mac = $this->params['box_mac'];
        $welcome_id = intval($this->params['welcome_id']);
        $m_welcomerecord = new \Common\Model\Smallapp\WelcomePlayrecordModel();
        $res_welcome = $m_welcomerecord->getInfo(array('welcome_id'=>$welcome_id,'box_mac'=>$box_mac));
        if(!empty($res_welcome)){
            $m_welcomerecord->updateData(array('id'=>$res_welcome['id']),array('status'=>2,'update_time'=>date('Y-m-d H:i:s')));
        }
        $this->to_back(array());
    }

}