<?php
namespace Smallapp21\Controller;
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
            case 'genCode':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'openid'=>1001,'type'=>1000);
                break;
            case 'getBoxQr':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'type'=>1001);
                break;
            case 'recOverQrcodeLog':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'openid'=>1001,'type'=>1000,'is_overtime'=>1001);
                break;
            case 'breakLink':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'openid'=>1001);
                break;
            case 'getBoxType':
               $this->is_verify = 1;
               $this->valid_fields = array('box_mac'=>1001);
               break;
           case 'getTestBoxQr':
               $this->is_verify = 1;
               $this->valid_fields = array('box_mac'=>1001,'type'=>1001);
               break;
           case 'closeauthLog':
               $this->is_verify =1;
               $this->valid_fields = array('openid'=>1001,'box_mac'=>1000);
               break;
           case 'getConfig':
               $this->is_verify = 0;
               break;
           case 'recordForScreenPics':
               $this->is_verify = 1;
               $this->valid_fields = array('openid'=>1001,'box_mac'=>1000,
                   'imgs'=>1001,'mobile_brand'=>1000,
                   'mobile_model'=>1000,'action'=>1000,
                   'resource_type'=>1000,'resource_id'=>1000,
                   'is_pub_hotelinfo'=>1000,'is_share'=>1000,
                   'forscreen_id'=>1000,'public_text'=>1000,
               );
               break;
           case 'isFind':
               $this->is_verify = 0;
               break;
           case 'isOpenFind':
               $this->is_verify = 0;
               break;
           case 'setOpenFind':
               $this->is_verify = 0;
               break;
           case 'happylist':
               $this->is_verify = 0;
               break;
           case 'getQrCode':
               $this->is_verify = 0;
               break;
           case 'getIndexBoxQr':
               $this->is_verify = 1;
               $this->valid_fields = array('type'=>1001);
               break;
           case 'getBoxQrcode'://主干版小程序盒子请求的二维码 小程序码接口
               $this->is_verify = 1;
               $this->valid_fields = array('box_mac'=>1001,'type'=>1001);
               break;
            case 'getQrcontent':
                $this->is_verify = 1;
                $this->valid_fields = array('content'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getIndexBoxQr(){
        $box_mac = 0;
        $type    = $this->params['type'];
        $r = $this->params['r'] !='' ? $this->params['r'] : 255;
        $g = $this->params['g'] !='' ? $this->params['g'] : 255;
        $b = $this->params['b'] !='' ? $this->params['b'] : 255;
        $m_small_app = new Smallapp_api();
        $tokens  = $m_small_app->getWxAccessToken();
        header('content-type:image/png');
        $data = array();
        
        $data['scene'] = $box_mac.'_'.$type;//自定义信息，可以填写诸如识别用户身份的字段，注意用中文时的情况
        $data['page'] = "pages/forscreen/forscreen";//扫描后对应的path
        $data['width'] = "560";//自定义的尺寸
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
        if(!array_key_exists($type,C('SMALLAPP_ERWEI_CODE_TYPES'))){
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
        $times = getMillisecond();
        $scene = $box_mac.'_'.$type.'_'.$times;
        $cache_key = C('SAPP_QRCODE').$encode_key;
        $redis->set($cache_key,$scene,86400);

        $hash_ids_key = C('HASH_IDS_KEY');
        $hashids = new \Common\Lib\Hashids($hash_ids_key);
        $s = $hashids->encode($encode_key);

        $content ="http://rd0.cn/p?s=$s";
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
            $times = getMillisecond();
            $scene = $box_mac.'_'.$type.'_'.$times;
            $cache_key = C('SAPP_QRCODE').$encode_key;
            $redis->set($cache_key,$scene,86400);

            $hash_ids_key = C('HASH_IDS_KEY');
            $hashids = new \Common\Lib\Hashids($hash_ids_key);
            $s = $hashids->encode($encode_key);

            $content ="http://rd0.cn/p?s=$s";
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
            $m_box = new \Common\Model\BoxModel();
            $map = array();
            $map['a.mac'] = $box_mac;
            $map['a.flag']=0;
            $map['a.state'] =1;
            $map['d.flag'] =0;
            $map['d.state'] = 1;
            $rets = $m_box->getBoxInfo('c.name room_name,d.name hotel_name', $map);
            $hotel_info = $rets[0];
            $code_info = $redis->get($keys);
            $code_info = json_decode($code_info,true);
            $this->to_back(array('is_have'=>$code_info['is_have'],
                'box_mac'=>$box_mac,'hotel_name'=>$hotel_info['hotel_name'],
                'room_name'=>$hotel_info['room_name']
            )
            );
        }else {
            $this->to_back(array('is_have'=>0,'openid'=>$openid));
        }
    
    }
    /**
     * @desc 发送随机验证码给电视
     */
    public function genCode(){
        $box_mac = $this->params['box_mac'];
        $openid  = $this->params['openid'];
        $type    = $this->params['type'];
        $code = rand(100, 999);
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SMALLAPP_CHECK_CODE');
        $cache_key .= $box_mac.':'.$openid;
        $info = $redis->get($cache_key);
        if(empty($info)){
            $info = array();
            /* $m_box = new \Common\Model\BoxModel();
            $maps['a.mac'] = $box_mac;
            $maps['a.state'] = 1;
            $maps['a.flag']  = 0;
            $box_info = $m_box->alias('a')
            ->join('savor_room room on a.room_id= room.id','left')
            ->field('room.type type')->where($maps)->find();
            
            if($box_info['type']==1){
                $info['is_have'] = 1;
            }else {
                $info['is_have'] = 0;
            } */
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
        //记录日志
        $this->recodeScannCode($box_mac,$openid,$type);
        $this->to_back($info);
    }
    public function recOverQrcodeLog(){
        $box_mac = $this->params['box_mac'];
        $openid  = $this->params['openid'];
        $type    = $this->params['type'];
        $is_overtime = $this->params['is_overtime'];
        $this->recodeScannCode($box_mac,$openid,$type,$is_overtime);
        $this->to_back(10000);
    }
    //断开连接
    public function breakLink(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SMALLAPP_CHECK_CODE');
        $cache_key .= $box_mac.':'.$openid;
        $info = $redis->get($cache_key);
        if(!empty($info)){
            $ret = $redis->remove($cache_key);
            if($ret) $this->to_back(10000);
            else $this->to_back(90108);
        }else {
            $this->to_back(90108);
        }
    }
    public function getBoxType(){
        $box_mac = $this->params['box_mac'];
        $m_box = new \Common\Model\BoxModel();
        $where = array();
        $where['a.mac'] = $box_mac;
        $where['a.state'] = 1;
        $where['a.flag']  = 0;
        $box_info = $m_box->alias('a')
              ->join('savor_room room on a.room_id= room.id','left')
              ->field('room.type')->where($where)->find();
        list($t1, $t2) = explode(' ', microtime());
        $nowtime = (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
        $data['box_type'] = $box_info['type'];
        $data['nowtime']  = $nowtime;
        $this->to_back($data);
        
    }
    public function closeauthLog(){
        $openid  = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $data = array();
        $data['openid'] = $openid;
        $data['box_mac']= $box_mac;
        $m_closeauth_log = new \Common\Model\Smallapp\CloseauthLogModel();
        $m_closeauth_log->addInfo($data);
        $this->to_back(10000);
    }
    public function getConfig(){
        list($t1, $t2) = explode(' ', microtime());
        $sys_time = (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
        $exp_time = 7200000;
        //$exp_time = 14400000;
        //$exp_time = 36000000;
        $data['sys_time'] = $sys_time;
        $data['exp_time'] = $exp_time;
        $this->to_back($data);
    }
    /**
     * @desc 记录用户投屏的图片、视频
     */
    public function recordForScreenPics(){
        $forscreen_id = $this->params['forscreen_id'] ? $this->params['forscreen_id'] :0;
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $mobile_brand = $this->params['mobile_brand'];
        $mobile_model = $this->params['mobile_model'];
        $forscreen_char = $this->params['forscreen_char'];
        $public_text   = $this->params['public_text'];
        $imgs    = str_replace("\\", '', $this->params['imgs']);
        $action  = $this->params['action'] ? $this->params['action'] : 0;
        $resource_type = $this->params['resource_type'] ? $this->params['resource_type'] : 0;
        $resource_id   = $this->params['resource_id'] ? $this->params['resource_id'] : 0;
        $resource_size = $this->params['resource_size'] ? $this->params['resource_size'] :0;
        $res_sup_time  = $this->params['res_sup_time'] ? $this->params['res_sup_time'] : 0;
        $res_eup_time  = $this->params['res_eup_time'] ? $this->params['res_eup_time'] : 0;
        $is_pub_hotelinfo = $this->params['is_pub_hotelinfo'] ?$this->params['is_pub_hotelinfo']:0;
        $is_share      = $this->params['is_share'] ? $this->params['is_share'] : 0;
        $duration      = $this->params['duration'] ? $this->params['duration'] : 0.00;
        $data = array();
        $data['forscreen_id'] = $forscreen_id;
        $data['openid'] = $openid;
        $data['box_mac']= $box_mac;
        $data['action'] = $action;
        $data['resource_type'] = $resource_type;
        $data['resource_id']   = $resource_id;
        $data['mobile_brand'] = $mobile_brand;
        $data['mobile_model'] = $mobile_model;
        $data['imgs']   = $imgs;
        $data['forscreen_char'] = !empty($forscreen_char) ? $forscreen_char : '';
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['res_sup_time']= $res_sup_time;
        $data['res_eup_time']= $res_eup_time;
        $data['resource_size'] = $resource_size;
        $data['is_pub_hotelinfo'] = $is_pub_hotelinfo;
        $data['is_share']    = $is_share;
        $data['duration']    = $duration;
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_SCRREN').":".$box_mac;
    
        $redis->rpush($cache_key, json_encode($data));
        $history_cache_key = C('SAPP_HISTORY_SCREEN').$box_mac.":".$openid;
        if($action==4 || ($action==2 && $resource_type==2)){
            $redis->rpush($history_cache_key, json_encode($data));
        }
        
    
        if(!empty($is_share)){
            $map = array();
            $map['forscreen_id'] = $forscreen_id;
            $map['openid'] = $openid;
            $map['box_mac']= $box_mac;
            $map['public_text'] = $public_text;
            if($action==4){
                $map['res_type'] = 1;
            }else if($action==2 && $resource_type==2){
                $map['res_type'] = 2;
                $map['duration'] = $duration;
            }
            $map['resource_size'] = $resource_size;
            $map['resource_id']   = $resource_id;
            $forscreen_res = json_decode($imgs,true);
    
            $map['res_url']   = $forscreen_res[0];
            $map['is_pub_hotelinfo'] =$is_pub_hotelinfo;
    
            $cache_key = C('SAPP_SCRREN_SHARE').$box_mac.':'.$openid.":".$forscreen_id;
            $redis->rpush($cache_key, json_encode($map));
        }
        $this->to_back(10000);
    }
    public function isFind(){
        $data = array();
        $data['is_open'] = 1;
        $this->to_back($data);
    }
    public function isOpenFind(){
        $data = array();
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $is_open = $redis->get('small_app_is_open_find');
        
        $data['is_open'] = $is_open;
        $this->to_back($data);
    }
    public function setOpenFind(){
        $is_open_find = $this->params['is_open_find'] ? $this->params['is_open_find'] :0;
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $redis->set('small_app_is_open_find', $is_open_find);
        $this->to_back(10000);
    }
    /**
     * @desc 生日歌列表
     */
    public function happylist(){
        $m_ads = new \Common\Model\AdsModel();
        $where = array();
        $oss_host = "http://".C('OSS_HOST').'/';
        
        //$where['a.id'] = array('in','4803,4795,4794,4793');
        //$where['a.id'] = array('in','4803,4795,4794,5233');
        //$where['a.id'] = array('in','5288,5246,5245,5244');
        $where['a.id'] = array('in','5514,5246,5245,5244');
        
        $fields =  "a.name, CONCAT('".$oss_host."',a.img_url) img_url,
                    CONCAT('".$oss_host."',media.oss_addr) res_url,substring(media.oss_addr,16) as file_name";
        
        $data = $m_ads->alias('a')
                      ->join('savor_media media on a.media_id = media.id','left')
                      ->field($fields)
                      ->where($where)
                      ->order('a.sort_num asc')->select();
        $result = array();
        foreach ($data as $v){
            $name_arr = explode('-',$v['name']);
            $v['title'] = $name_arr[0];
            $v['sub_title'] = $name_arr[1];
            $result[] = $v;
        }
        $this->to_back($result);
    }
    public function getQrCode(){
        
        $r = $this->params['r'] !='' ? $this->params['r'] : 255;
        $g = $this->params['g'] !='' ? $this->params['g'] : 255;
        $b = $this->params['b'] !='' ? $this->params['b'] : 255;
        $m_small_app = new Smallapp_api();
        $tokens  = $m_small_app->getWxAccessToken();
        header('content-type:image/png');
        $data = array();
        $times = getMillisecond();
        $data['scene'] ='_';
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
    
    
    /**
     * @desc 记录扫码日志
     * @param varchar $box_mac  盒子mac
     * @param varchar $openid   openid
     * @param tinyint $type     1:小码2:大码3:手机小程序呼码
     */
    private function recodeScannCode($box_mac,$openid,$type,$is_overtime){
        $data = array();
        $data['box_mac'] = $box_mac;
        $data['openid']  = $openid;
        $data['type']    = !empty($type) ? $type :1;
        $data['is_overtime'] = $is_overtime ? $is_overtime :0;
        $m_qrcode_log = new \Common\Model\Smallapp\QrcodeLogModel();
        $m_qrcode_log->addInfo($data);
    }
}