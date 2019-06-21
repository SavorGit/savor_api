<?php
namespace Smallappsimple\Controller;
use Think\Controller;
use Common\Lib\Smallapp_api;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
use Common\Lib\Qrcode;
class IndexController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            
            case 'getOpenid':
                $this->is_verify  =1;
                $this->valid_fields = array('code'=>1001);
            break;
            case 'getJjOpenid': //极简版openid
                $this->is_verify  =1;
                $this->valid_fields = array('code'=>1001);
                break;
            case 'getHotelInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
            break;
            case 'getBoxWifi':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
            break;
            case 'getInnerIp':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
            break;
            case 'getBoxQr':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
            case 'recordWifiErr':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
        }
        parent::_init_();
    }
    
    /**
     *@desc 获取openid
     */
    public function getOpenid(){
        $code = $this->params['code'];
        $m_small_app = new Smallapp_api($flag = 2);
        $data  = $m_small_app->getSmallappOpenid($code);
        $this->to_back($data);
    }
    /**
     *@desc 获取新极简版openid
     */
    public function getJjOpenid(){
        $code = $this->params['code'];
        $m_small_app = new Smallapp_api($flag = 3);
        $data  = $m_small_app->getSmallappOpenid($code);
        $this->to_back($data);
    }
    /**
     * @desc 获取酒楼信息
     */
    public function getHotelInfo(){
        $box_mac = $this->params['box_mac'];
        $m_box = new \Common\Model\BoxModel();
        $info = array();
        
        $fields  = 'd.name hotel_name,c.name room_name,a.wifi_name,a.wifi_password,a.wifi_mac,a.is_open_simple';
        $where = array();
        $where['d.state'] = 1;
        $where['d.flag']  = 0;
        $where['a.state'] = 1;
        $where['a.flag']  = 0;
        $where['a.mac']   = $box_mac;
        $info = $m_box->getBoxInfo($fields,$where);
        if(empty($info)){
            $this->to_back(70001);
        }else {
            $redis = SavorRedis::getInstance();
            $redis->select(13);
            $cache_key = 'heartbeat:2:'.$box_mac;
            $data = $redis->get($cache_key);
            $intranet_ip = '';
            if(!empty($data)){
                $data = json_decode($data,true);
                $intranet_ip = $data['intranet_ip'];
            }
            $info = $info[0];
            $info['intranet_ip'] = $intranet_ip;
            $this->to_back($info);
        }
    }

    public function getInnerIp(){
        $box_mac = $this->params['box_mac'];
        
        $redis = SavorRedis::getInstance();
        $redis->select(13);
        $cache_key = 'heartbeat:2:'.$box_mac;
        $data = $redis->get($cache_key);
        if(!empty($data)){
            $data = json_decode($data,true);
            $this->to_back($data);
        }else {
            $m_heart_log =  new \Common\Model\HeartLogModel();
            
        }
    }

    /**
     * @des  获取当前机顶盒小程序码
     */
    public function getBoxQr(){
        $box_mac = $this->params['box_mac'];
        $type    = $this->params['type'] ? intval($this->params['type']) : 6;
        $small_jj_erwei_code_arr = C('SMALLAPP_JJ_ERWEI_CODE_TYPES');
        $small_jj_erwei_code_arr = array_keys($small_jj_erwei_code_arr);
        
        if(in_array($type, $small_jj_erwei_code_arr)){
            $m_box = new \Common\Model\BoxModel();
            $map = array();
            $map['a.mac'] = $box_mac;
            $map['a.state'] = 1;
            $map['a.flag']  = 0;
            $map['d.state'] = 1;
            $map['d.flag']  = 0;
            $box_info = $m_box->getBoxInfo('a.id as box_id', $map);
            if(empty($box_info)){
                $this->to_back(70001);
            }
            $encode_key = "$type{$box_info[0]['box_id']}";
            $redis  =  \Common\Lib\SavorRedis::getInstance();
            $redis->select(5);
            $scene = $box_mac.'_'.$type;
            $cache_key = C('SAPP_QRCODE').$encode_key;
            $redis->set($cache_key,$scene,86400);

            $hash_ids_key = C('HASH_IDS_KEY');
            $hashids = new \Common\Lib\Hashids($hash_ids_key);
            $s = $hashids->encode($encode_key);

            $content ="http://rd0.cn/e?s=$s";
            $errorCorrectionLevel = 'L';//容错级别
            $matrixPointSize = 5;//生成图片大小
            //生成二维码图片
            if(in_array($type,array(20,21))){
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
        $m_small_app = new Smallapp_api(3);
        $tokens  = $m_small_app->getWxAccessToken();
        header('content-type:image/png');
        $data = array();
        $data['scene'] = $box_mac."_".$type;//自定义信息，可以填写诸如识别用户身份的字段，注意用中文时的情况
        $data['page'] = "pages/index/index";//扫描后对应的path
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

    public function getBoxQrcode(){
        $box_mac = $this->params['box_mac'];
        $type    = $this->params['type'];
        if(!array_key_exists($type,C('SMALLAPP_JJ_ERWEI_CODE_TYPES'))){
            $this->to_back(90100);
        }
        $m_box = new \Common\Model\BoxModel();
        $map = array();
        $map['a.mac'] = $box_mac;
        $map['a.state'] = 1;
        $map['a.flag']  = 0;
        $map['d.state'] = 1;
        $map['d.flag']  = 0;
        $box_info = $m_box->getBoxInfo('a.id as box_id', $map);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $encode_key = "$type{$box_info[0]['box_id']}";
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $scene = $box_mac.'_'.$type;
        $cache_key = C('SAPP_QRCODE').$encode_key;
        $redis->set($cache_key,$scene,86400);

        $hash_ids_key = C('HASH_IDS_KEY');
        $hashids = new \Common\Lib\Hashids($hash_ids_key);
        $s = $hashids->encode($encode_key);

        $content ="http://rd0.cn/e?s=$s";
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 5;//生成图片大小
        //生成二维码图片
        if(in_array($type,array(20,21))){
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

    public function getQrcontent(){
        $content = $this->params['content'];
        $hash_ids_key = C('HASH_IDS_KEY');
        $hashids = new \Common\Lib\Hashids($hash_ids_key);
        $decode_info = $hashids->decode($content);
        if(empty($decode_info)){
            $this->to_back(90101);
        }
        $cache_key = C('SAPP_QRCODE').$decode_info[0];
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $res = $redis->get($cache_key);
        if(empty($res)){
            $this->to_back(90101);
        }
        $content = array('content'=>$res);
        $this->to_back($content);
    }


    public function recordWifiErr(){
        $box_mac = $this->params['box_mac'];
        $err_info = str_replace('\\', '', $this->params['err_info']);
        $m_err_info = new \Common\Model\Smallapp\WifiErrModel();
        $data['box_mac'] = $box_mac;
        $data['err_info'] = $err_info;
        $m_err_info->addInfo($data);
    }
}