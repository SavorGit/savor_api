<?php
namespace Box\Controller;
use \Common\Controller\CommonController as CommonController;
class ForscreenController extends CommonController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getConfig':
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1001,'versionCode'=>1000);
                break;
            case 'addPlaylog':
                $this->is_verify =1;
                $this->valid_fields = array('vid'=>1001,'type'=>1001);
                break;
        }
        parent::_init_(); 
    }
    public function getConfig(){
        $box_min_version_code = C('SAPP_FORSCREEN_VERSION_CODE');
        $box_mac = $this->params['box_mac'];
        $versionCode = intval($this->params['versionCode']);
        $data = array();
        
        if(empty($versionCode) || $versionCode <$box_min_version_code){
            $data['is_sapp_forscreen'] = 0;
            $data['is_simple_sapp_forscreen'] = 0;
            $data['is_open_interactscreenad'] = 0;
            $data['is_open_netty'] = 0;
            $this->to_back($data);
        }else if($versionCode>=$box_min_version_code){
            $m_box = new \Common\Model\BoxModel();
            $where = array();
            $where['mac'] = $box_mac;
            $where['state'] = 1;
            $where['flag']  = 0 ;
            $box_info = $m_box->getOnerow($where);
            if(empty($box_info)){
                $this->to_back(70001);
            }
            $m_sys_config = new \Common\Model\SysConfigModel();
            $sys_info = $m_sys_config->getAllconfig();
            $oss_host = 'http://'.C('OSS_HOST');
            $data['is_open_netty']             = intval($box_info['is_open_netty']);
            $data['is_sapp_forscreen']         = intval($box_info['is_sapp_forscreen']);
            $data['is_simple_sapp_forscreen']  = intval($box_info['is_open_simple']);
            $data['is_open_interactscreenad']  = intval($box_info['is_open_interactscreenad']);
            $data['system_sapp_forscreen_nums']= intval($sys_info['system_sapp_forscreen_nums']);
            $data['qrcode_type']               = intval($box_info['qrcode_type']);
            $data['is_open_signin']            = intval($box_info['is_open_signin']);
            $data['activity_adv_playtype']     = intval($sys_info['activity_adv_playtype']);//1替换 2队列
            $data['simple_upload_size']        = intval(C('SMALLAPP_JJ_UPLOAD_SIZE'));      //极简版投屏上传资源大小限制
            $data['qrcode_gif']                = $oss_host.'/media/resource/QKHYcD5wiT.gif';
            $data['qrcode_gif_filename']       = 'QKHYcD5wiT.gif';
            $data['qrcode_gif_md5']            = '49a11f843ecd6cd81659eec27f05e8e3';
            $data['qrcode_takttime']           = 30;
            $data['qrcode_showtime']           = 60;
            $data['scenceadv_show_num'] = C('SCENCE_ADV_FILEFORSCREEN_NUM');
            $is_show = C('FORSCREEN_GUIDE_IMAGE_SWITCH');
            $forscreen_help_images = array(
                'is_show'=>$is_show,'show_time'=>5,'forscreen_num'=>6,
                'image_url'=>$oss_host.'/media/resource/yYN5a33w3k.jpeg',
                'image_filename'=>'yYN5a33w3k.jpg',
                'video_url'=>$oss_host.'/media/resource/epkAdaGzSh.jpeg',
                'video_filename'=>'epkAdaGzSh.jpg',
                'file_url'=>$oss_host.'/media/resource/kCtzCQFH8M.jpeg',
                'file_filename'=>'kCtzCQFH8M.jpeg',
                'forscreen_box_url'=>$oss_host.'/media/resource/6zcQr5iZBJ.jpeg',
                'forscreen_box_filename'=>'6zcQr5iZBJ.jpeg',
                'bonus_forscreen_url'=>$oss_host.'/media/resource/6zcQr5iZBJ.jpeg',
                'bonus_forscreen_filename'=>'6zcQr5iZBJ.jpeg',
            );
            $data['forscreen_help_images'] = $forscreen_help_images;
            $forscreen_call_code = array(
                'url'=>$oss_host.'/media/resource/re6bB4RHfC.mp4',
                'filename'=>'re6bB4RHfC.mp4',
                'md5'=>'b7c1c5fd2962c2f49af36ffabb4c3fd7',
            );
            $data['forscreen_call_code'] = $forscreen_call_code;
            $test_wechat = '';
            if($box_info['is_4g']==1){
                $test_wechat = 'https://api.weixin.qq.com/wxa/getwxacode?access_token=820';
            }
            $data['test_wechat'] = $test_wechat;

            $redis = \Common\Lib\SavorRedis::getInstance();
            /*
            $redis->select(1);
            $key = C('SAPP_SCAN_BOX_CODE');
            $cache_key = $key.':'.$box_mac.':'.date('Ymd');
            $res_redis = $redis->get($cache_key);
            $isShowAnimQRcode = true;
            if(!empty($res_redis)){
                $now_time = date('Y-m-d H:i:s');
                $meal_time = C('MEAL_TIME');
                $lunch_stime = date("Y-m-d {$meal_time['lunch'][0]}:00");
                $lunch_etime = date("Y-m-d {$meal_time['lunch'][1]}:00");
                $dinner_stime = date("Y-m-d {$meal_time['dinner'][0]}:00");
                $dinner_etime = date("Y-m-d {$meal_time['dinner'][1]}:59");
                $meal_type = '';
                $now_day_time = date('Y-m-d 00:00:00');
                if($now_time>$now_day_time && $now_time<$lunch_stime){
                    $meal_type = 'before_lunch';
                }elseif($now_time>=$lunch_stime && $now_time<=$lunch_etime){
                    $meal_type = 'lunch';
                }elseif($now_time>$lunch_etime && $now_time<$dinner_stime){
                    $meal_type = 'after_lunch';
                }elseif($now_time>=$dinner_stime && $now_time<=$dinner_etime){
                    $meal_type = 'dinner';
                }
                $scan_data = json_decode($res_redis,true);
                if(!empty($meal_type) && isset($scan_data[$meal_type])){
                    $isShowAnimQRcode = false;
                }
            }
            */
            $data['isShowAnimQRcode'] = false;

            $data['is_wifi_hotel'] = 0;
            $redis->select(15);
            $cache_key = 'savor_box_'.$box_info['id'];
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info,true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $left_pop_wind = 1;
            $marquee = 1;
            if(!empty($room_info)){
                $hotel_id = $room_info['hotel_id'];
                $wifi_hotel = C('RD_WIFI_HOTEL');
                if(isset($wifi_hotel[$hotel_id])){
                    $data['is_wifi_hotel'] = 1;
                    $data['qrcode_name']                = '扫码上网';
                    $data['qrcode_gif']                = $oss_host.'/media/resource/hAeFm2cKyx.gif';
                    $data['qrcode_gif_filename']       = 'hAeFm2cKyx.gif';
                    $data['qrcode_gif_md5']            = '56b18556d2d79e111f4bffcbc7d4defa';
                    $data['isShowAnimQRcode'] = true;
                }
                $cache_key = 'savor_hotel_ext_' . $hotel_id;
                $redis_hotel_ext = $redis->get($cache_key);
                $ext_info = json_decode($redis_hotel_ext,true);
                $left_pop_wind = intval($ext_info['is_goods_leftpop_wind']);
                $marquee = intval($ext_info['is_goods_roll_content']);
                /*
                $seckill_goods_id = C('LAIMAO_SECKILL_GOODS_ID');
                $m_hotel_goods = new \Common\Model\Smallapp\HotelgoodsModel();
                $res_hgoods = $m_hotel_goods->getInfo(array('hotel_id'=>$hotel_id,'goods_id'=>$seckill_goods_id));
                if(!empty($res_hgoods)){
                    $data['qrcode_gif']                = $oss_host.'/media/resource/GGETTftdZ7.gif';
                    $data['qrcode_gif_filename']       = 'GGETTftdZ7.gif';
                    $data['qrcode_gif_md5']            = '04c10c5c6076569e62761ced8d570353';
                    $data['isShowAnimQRcode'] = true;
                }
                */
            }
			$data['left_pop_wind'] = $left_pop_wind;
			$data['marquee']       = $marquee;
			$hotel_id = $room_info['hotel_id'];
			$m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
            $nowtime = date('Y-m-d H:i:s');
            $fields = 'g.id as goods_id,g.model_media_id,g.price,g.line_price,g.roll_content,g.end_time';
            $where = array('h.hotel_id'=>$hotel_id,'g.type'=>43,'g.is_seckill'=>1,'g.status'=>1);
            $where['g.start_time'] = array('elt',$nowtime);
            $where['g.end_time'] = array('egt',$nowtime);
            $res_goods = $m_hotelgoods->getGoodsList($fields,$where,'g.id desc','0,1');
			if(!empty($res_goods)){
				$data['is_sale_hotel'] = 1;    //是否为售酒酒楼
			}else {
				$data['is_sale_hotel'] = 0;    //是否为售酒酒楼
			}
			//$data['qrcode_tip']  = array('扫码投屏','扫码有礼','扫码抽奖');
			$data['qrcode_tip']  = array('扫码投屏','扫码有礼');

            $sellwine_activity = C('SELLWINE_ACTIVITY');
            $where = array('a.hotel_id'=>$hotel_id,'activity.type'=>6,'activity.status'=>1);
            $where['activity.start_time'] = array('elt',date('Y-m-d H:i:s'));
            $where['activity.end_time'] = array('egt',date('Y-m-d H:i:s'));
            $m_acticity_hotel = new \Common\Model\Smallapp\ActivityhotelModel();
            $res_activityhotel = $m_acticity_hotel->getActivityhotelDatas('a.id',$where,'a.id desc','0,1','');
            if(!empty($res_activityhotel[0]['id'])){
                $offline_filename = $sellwine_activity['filename'];
                $sellwine_activity = C('SELL_TASTE_WINE_ACTIVITY');
                $sellwine_activity['offline_filename'] = $offline_filename;
            }

            $sellwine_activity['url'] = $oss_host.'/'.$sellwine_activity['url'];
            $data['sellwine_activity'] = $sellwine_activity;

            $this->to_back($data);
        }
    }

    public function addPlaylog(){
        $vid = $this->params['vid'];
        $type = $this->params['type'];

        $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
        $res_play = $m_play_log->getOne('id,create_time',array('res_id'=>$vid,'type'=>4),'id desc');
        if(!empty($res_play)){
            $m_config = new \Common\Model\SysConfigModel();
            $res_config = $m_config->getAllconfig();
            $play_time = intval($res_config['content_play_time'])*3600;
            $last_time = strtotime($res_play['create_time'])+$play_time;
            $now_time = time();
            if($now_time>$last_time){
                $redis  =  \Common\Lib\SavorRedis::getInstance();
                $redis->select(5);
                $allkeys  = $redis->keys('smallapp:selectcontent:program:*');
                foreach ($allkeys as $program_key){
                    $period = getMillisecond();
                    $redis->set($program_key,$period);
                }

                $content_key = C('SAPP_SELECTCONTENT_CONTENT');
                $redis->select(5);
                $res_cache = $redis->get($content_key);
                if(!empty($res_cache)) {
                    $help_id = 0;
                    $help_forscreen = json_decode($res_cache, true);
                    foreach ($help_forscreen as $k=>$v){
                        if($v['id']==$vid){
                            unset($help_forscreen[$k]);
                            $help_id = $v['help_id'];
                            break;
                        }
                    }
                    if($help_id){
                        $redis->set($content_key,json_encode($help_forscreen));
                        $m_help = new \Common\Model\Smallapp\ForscreenHelpModel();
                        $m_help->updateData(array('id'=>$help_id),array('status'=>4));
                    }
                }

            }else{
                $update_data = array('update_time'=>date('Y-m-d H:i:s'));
                $update_data['nums'] = array('exp','nums+1');
                $m_play_log->updateInfo(array('id'=>$res_play['id']),$update_data);

                $res_num = $m_play_log->getOne('nums',array('id'=>$res_play['id']),'id desc');
                if($res_num['nums']==10000){
                    $push_key = C('SAPP_SELECTCONTENT_PUSH').':playtv';
                    $redis  =  \Common\Lib\SavorRedis::getInstance();
                    $redis->select(5);
                    $data = json_encode(array('id'=>$res_play['id'],'nums'=>$res_num['nums']));
                    $redis->rpush($push_key,$data);
                }
            }
        }
        $this->to_back(array());
    }

}