<?php
namespace Smallapp44\Controller;
use Common\Lib\Smallapp_api;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
use Common\Lib\Qrcode;
class IndexController extends CommonController{
    /**
     * @desc 构造函数
     */
    function _init_(){
        switch(ACTION_NAME){
            case 'gencode':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'openid'=>1001,'type'=>1000);
                break;
            case 'getBoxQr':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'type'=>1001);
            case 'getConfig':
                $this->valid_fields = array('box_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'recodeQrcodeLog':
                $this->is_verify= 0;
                $this->valid_fields = array('openid'=>1001,'type'=>1001);
                break;
            case 'isHaveCallBox':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
        }
        parent::_init_();
    }

    /**
     * @des  获取当前机顶盒小程序码
     */
    public function getBoxQr(){
        $box_mac = $this->params['box_mac'];
        $type    = $this->params['type'];//1:小码2:大码(节目)3:手机小程序呼码5:大码（新节目）6:极简版7:主干版桌牌码 8小程序主干版本二维码
        $small_erwei_code_arr = C('SMALLAPP_ERWEI_CODE_TYPES');
        $small_erwei_code_arr = array_keys($small_erwei_code_arr);

        if(in_array($type, $small_erwei_code_arr)){
            $m_box = new \Common\Model\BoxModel();
            $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);//1外网(主干) 2直连(极简)
            if(empty($forscreen_info['box_id'])){
                $this->to_back(70001);
            }
            $now_time = date('zH');
            $encode_key = "$type{$forscreen_info['box_id']}$now_time";
            $redis  =  \Common\Lib\SavorRedis::getInstance();
            $redis->select(5);
            $times = getMillisecond();
            $scene = $box_mac.'_'.$type.'_'.$times;
            switch ($type){
                case 3:
                case 8:
                    $scene.='_'.$forscreen_info['forscreen_type'];
                    break;
                case 16:
                    $forscreen_type = 2;//1外网(主干) 2直连(极简)
                    $scene.='_'.$forscreen_type;
                    break;
            }

            $cache_key = C('SAPP_QRCODE').$encode_key;
            $redis->set($cache_key,$scene,3600*3);

            $hash_ids_key = C('HASH_IDS_KEY');
            $hashids = new \Common\Lib\Hashids($hash_ids_key);
            $s = $hashids->encode($encode_key);

            $short_urls = C('SHORT_URLS');
            $content = $short_urls['BOX_QR'].$s;
            $errorCorrectionLevel = 'L';//容错级别
            $matrixPointSize = 5;//生成图片大小
            //生成二维码图片
            if(in_array($type,array(12,13))){
                // generating frame
                $frame = QRcode::text($content,false,$errorCorrectionLevel, $matrixPointSize, 0);
                $outerFrame = 0;
                $pixelPerPoint = $matrixPointSize;
                $h = count($frame);
                $w = strlen($frame[0]);
                $imgW = $w + 2*$outerFrame;
                $imgH = $h + 2*$outerFrame;
                $base_image = imagecreate($imgW, $imgH);

//                $col[0] = imagecolorallocate($base_image,255,255,255);//BG, white
                $col[0] = imagecolorallocatealpha($base_image, 255, 255, 255, 127);
                $col[1] = imagecolorallocate($base_image,94,84,77);

                imagefill($base_image, 0, 0, $col[0]);
                for($y=0; $y<$h; $y++) {
                    for($x=0; $x<$w; $x++) {
                        if ($frame[$y][$x] == '1') {
                            imagesetpixel($base_image,$x+$outerFrame,$y+$outerFrame,$col[1]);
                        }
                    }
                }
                $target_image = imagecreate($imgW * $pixelPerPoint, $imgH * $pixelPerPoint);
                imagecopyresized(
                    $target_image,
                    $base_image,
                    0, 0, 0, 0,
                    $imgW * $pixelPerPoint, $imgH * $pixelPerPoint, $imgW, $imgH
                );
                imagedestroy($base_image);
                header('content-type:image/png');
                imagepng($target_image);
                imagedestroy($target_image);
                exit;
            }else{
                Qrcode::png($content,false,$errorCorrectionLevel, $matrixPointSize, 0);
                exit;
            }
        }
        $r = $this->params['r'] !='' ? $this->params['r'] : 255;
        $g = $this->params['g'] !='' ? $this->params['g'] : 255;
        $b = $this->params['b'] !='' ? $this->params['b'] : 255;
        $m_small_app = new Smallapp_api();
        $tokens  = $m_small_app->getWxAccessToken();
        header('content-type:image/png');
        $data = array();
        $times = getMillisecond();
        $data['scene'] = $box_mac.'_'.$type.'_'.$times;//自定义信息，可以填写诸如识别用户身份的字段，注意用中文时的情况
        $data['page'] = "pages/forscreen/forscreen";//扫描后对应的path
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

    public function isHaveCallBox(){
        $openid = $this->params['openid'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $key = C('SMALLAPP_CHECK_CODE')."*".$openid;
        $keys = $redis->keys($key);
        if(!empty($keys)){
            $keys = $keys[0];
            $key_arr = explode(':', $keys);
            $box_mac = $key_arr['2'];
            $code_info = $redis->get($keys);
            $code_info = json_decode($code_info,true);

            $redis->select(13);
            $cache_key = 'heartbeat:2:'.$box_mac;
            $res_cache = $redis->get($cache_key);
            $intranet_ip = '';
            if(!empty($res_cache)){
                $res_cache = json_decode($res_cache,true);
                $intranet_ip = $res_cache['intranet_ip'];
            }

            $m_box = new \Common\Model\BoxModel();
            $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);
            $data = array('is_have'=>$code_info['is_have'],'box_mac'=>$box_mac,
                'forscreen_type'=>$forscreen_info['forscreen_type'],'intranet_ip'=>$intranet_ip);
            if(isset($forscreen_info['box_id'])){
                $redis->select(15);
                $cache_key = 'savor_box_'.$forscreen_info['box_id'];
                $redis_box_info = $redis->get($cache_key);
                $box_info = json_decode($redis_box_info,true);
                $cache_key = 'savor_room_' . $box_info['room_id'];
                $redis_room_info = $redis->get($cache_key);
                $room_info = json_decode($redis_room_info, true);
                $cache_key = 'savor_hotel_' . $room_info['hotel_id'];
                $redis_hotel_info = $redis->get($cache_key);
                $res_hotel = json_decode($redis_hotel_info, true);

                $hotel_info = array('box_id'=>$forscreen_info['box_id'],'room_name'=>$room_info['name'],'hotel_name'=>$res_hotel['name'],'wifi_name'=>$box_info['wifi_name'],
                    'wifi_password'=>$box_info['wifi_password'],'wifi_mac'=>$box_info['wifi_mac']);
            }else{
                $map = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
                $rets = $m_box->getBoxInfo('a.id box_id,c.name room_name,d.name hotel_name,a.wifi_name,a.wifi_password,a.wifi_mac',$map);
                $hotel_info = $rets[0];
            }
            $data['box_id'] = $hotel_info['box_id'];
            $data['hotel_name'] = $hotel_info['hotel_name'];
            $data['room_name'] = $hotel_info['room_name'];
            $data['wifi_name'] = $hotel_info['wifi_name'];
            $data['wifi_password'] = $hotel_info['wifi_password'];
        }else{
            $data = array('is_have'=>0);
        }
        $this->to_back($data);
    }


    public function recodeQrcodeLog(){
        $openid = $this->params['openid'];
        $type   = intval($this->params['type']);
        $data = array();
        $data['box_mac'] = '';
        $data['openid']  = $openid;
        $data['type']    = $type;
        $data['is_overtime'] = 0;
        $m_qrcode_log = new \Common\Model\Smallapp\QrcodeLogModel();
        $m_qrcode_log->addInfo($data);
        $this->to_back(10000);
    }
    /**
     * @desc 扫码链接电视
     */
    public function gencode(){
        $box_mac = $this->params['box_mac'];
        $openid  = $this->params['openid'];
        
        $code = rand(100, 999);
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SMALLAPP_CHECK_CODE');
        $cache_key .= $box_mac.':'.$openid;
        $info = $redis->get($cache_key);
        if(empty($info)){
            $info = array();
            $info['is_have'] = 1;
            $info['code'] = $code;
            $redis->set($cache_key, json_encode($info),7200);
            
            $key = C('SMALLAPP_CHECK_CODE')."*".$openid;
            $keys = $redis->keys($key);
            foreach($keys as $v){
                $key_arr = explode(':', $v);
                if($key_arr[2]!=$box_mac){
                    $redis->remove($v);
                }
            }       
        }else {
            $key = C('SMALLAPP_CHECK_CODE')."*".$openid;
            $keys = $redis->keys($key);
            foreach($keys as $v){
                $key_arr = explode(':', $v);
                if($key_arr[2]!=$box_mac){
                    $redis->remove($v);
                }
            }   
            $info = json_decode($info,true);
        }
        $this->to_back($info);
    }
    public function getConfig(){
        $box_id = intval($this->params['box_id']);

        list($t1, $t2) = explode(' ', microtime());
        $sys_time = (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
        $file_exts = C('SAPP_FILE_FORSCREEN_TYPES');
        $exp_time   = 7200000;//扫码失效时间
        $redpacket_exp_time = 1800000;
        $data = array('sys_time'=>$sys_time,'exp_time'=>$exp_time,'redpacket_exp_time'=>$redpacket_exp_time,
            'file_exts'=>array_keys($file_exts));
        $data['file_max_size'] = 41943040;
        $data['polling_time']  = 120;  //文件投屏默认轮询时间60s
        $is_comment = 0;
        if($box_id){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(15);
            $cache_key = 'savor_box_'.$box_id;
            $redis_box_info = $redis->get($cache_key);
            if(!empty($redis_box_info)){
                $box_info = json_decode($redis_box_info,true);
                $cache_key = 'savor_room_' . $box_info['room_id'];
                $redis_room_info = $redis->get($cache_key);
                $room_info = json_decode($redis_room_info, true);

                $hotel_id = $room_info['hotel_id'];
                $room_id = $box_info['room_id'];
                $m_staff = new \Common\Model\Integral\StaffModel();
                $res_staff = $m_staff->getInfo(array('hotel_id'=>$hotel_id,'room_id'=>$room_id));
                if(!empty($res_staff)){
                    $is_comment = 1;
                }
            }
        }
        $data['is_comment'] = $is_comment;
        $this->to_back($data);
    }
}