<?php
namespace Smallapp46\Controller;
use Common\Lib\Smallapp_api;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
use Common\Lib\Qrcode;
use Common\Lib\AliyunOss;
class IndexController extends CommonController{
    /**
     * @desc 构造函数
     */
    function _init_(){
        switch(ACTION_NAME){
            case 'getOssParams':
                $this->is_verify = 0;
                break;
            case 'getOpenid':
                $this->is_verify  =1;
                $this->valid_fields = array('code'=>1001);
                break;
            case 'gencode':
                $this->is_verify = 1;
                $this->valid_fields = array('box_id'=>1000,'box_mac'=>1000,'openid'=>1001,'type'=>1000,'data_id'=>1000,
                    'mobile_brand'=>1000,'mobile_model'=>1000);
                break;
            case 'getBoxQr':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'type'=>1001);
                break;
            case 'recOverQrcodeLog':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'openid'=>1001,'type'=>1000,'is_overtime'=>1001);
                break;
            case 'closeauthLog':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1000);
                break;
            case 'getConfig':
                $this->valid_fields = array('box_id'=>1002,'openid'=>1002);
                $this->is_verify = 0;
                break;
            case 'getQrcontent':
                $this->is_verify = 1;
                $this->valid_fields = array('content'=>1001);
                break;
            case 'recodeQrcodeLog':
                $this->is_verify= 1;
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'data_id'=>1002,'box_id'=>1002,'box_mac'=>1002,'mobile_brand'=>1002,'mobile_model'=>1002);
                break;
            case 'breakLink':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'openid'=>1001);
                break;
            case 'isHaveCallBox':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'pop_eval'=>1002,'action'=>1002,'mobile_brand'=>1002,'mobile_model'=>1002);
                break;
            case 'recordForScreenPics':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1000,
                    'imgs'=>1001,'mobile_brand'=>1000,
                    'mobile_model'=>1000,'action'=>1000,
                    'resource_type'=>1000,'resource_id'=>1000,
                    'is_pub_hotelinfo'=>1000,'is_share'=>1000,
                    'forscreen_id'=>1000,'public_text'=>1000,'res_nums'=>1000,
                    'serial_number'=>1000,'quality_type'=>1000,'create_time'=>1000,
                    'is_speed'=>1000,
                );
                break;
            case 'happylist':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }

    /**
     * 获取OSS资源上传的配置初始化参数
     *
     */
    public function getOssParams(){
        $id = C('OSS_ACCESS_ID');
        $key = C('OSS_ACCESS_KEY');
        //$host = 'http://'.C('OSS_BUCKET').'.'.C('OSS_HOST');
        $host = 'https://'.C('OSS_HOST');
        $callbackUrl = C('HOST_NAME').'/'.C('OSS_SYNC_CALLBACK_URL');
        $callback_param = array(
            'callbackUrl'=>$callbackUrl,
            'callbackBody'=>'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType'=>"application/x-www-form-urlencoded"
        );
        $callback_string = json_encode($callback_param);
        $base64_callback_body = base64_encode($callback_string);
        $now = time();
        $expire = 30; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = $this->_gmt_iso8601($end);

        $rand = rand(10,99);

        //资源空间的目录前缀
        $dir = C('OSS_FORSCREEN_ADDR_PATH');

        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition;

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0=>'starts-with', 1=>'$key', 2=>$dir);
        $conditions[] = $start;
        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response              = array();
        //$response['accessid']  = $id;
        $response['host']      = $host;
        $response['policy']    = $base64_policy;
        $response['signature'] = $signature;
        $response['expire']    = $end;
        $response['callback']  = $base64_callback_body;
        //这个参数是设置用户上传指定的前缀
        $response['dir']       = $dir;
        echo json_encode($response);
        exit;
    }

    /**
     *@desc 获取openid
     */
    public function getOpenid(){
        $code = $this->params['code'];
        $m_small_app = new Smallapp_api();
        $data  = $m_small_app->getSmallappOpenid($code);
        $data['official_account_article_url'] =C('OFFICIAL_ACCOUNT_ARTICLE_URL');
        $this->to_back($data);
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
            $this->to_back(90166);
        }
        $content = array('content'=>$res);
        $this->to_back($content);
    }

    public function isHaveCallBox(){
        $openid = $this->params['openid'];
        $pop_eval = $this->params['pop_eval'];
        $action = $this->params['action'];
        $mobile_brand = $this->params['mobile_brand'];
        $mobile_model = $this->params['mobile_model'];

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
                'forscreen_type'=>$forscreen_info['forscreen_type'],'forscreen_method'=>$forscreen_info['forscreen_method'],
                'intranet_ip'=>$intranet_ip,'is_open_popcomment'=>$forscreen_info['is_open_popcomment']);
            $hotel_info = array('hotel_name'=>'','room_name'=>'');
            if(isset($forscreen_info['box_id']) && $forscreen_info['box_id']>0){
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
                
                $hotel_info = array('box_id'=>$forscreen_info['box_id'],'box_type'=>$box_info['box_type'],'room_name'=>$room_info['name'],
                    'hotel_name'=>$res_hotel['name'],'wifi_name'=>$box_info['wifi_name'],'wifi_password'=>$box_info['wifi_password'],
                    'wifi_mac'=>$box_info['wifi_mac'],'hotel_id'=>$room_info['hotel_id'],'room_id'=>$box_info['room_id'],
                    'is_interact'=>$box_info['is_interact'],'is_4g'=>$box_info['is_4g'],'is_open_simple'=>$box_info['is_open_simple']);
            }
            if(empty($hotel_info['hotel_name']) || empty($hotel_info['room_name'])){
                $map = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
                $rets = $m_box->getBoxInfo('d.id hotel_id,c.id room_id,a.id box_id,a.box_type,a.is_interact,a.is_4g,a.is_open_simple,c.name room_name,d.name hotel_name,a.wifi_name,a.wifi_password,a.wifi_mac',$map);
                $hotel_info = $rets[0];
            }
            $m_heartlog = new \Common\Model\HeartLogModel();
            $res_heartlog = $m_heartlog->getInfo('apk_version',array('box_id'=>$hotel_info['box_id'],'type'=>2),'');
            $apk_version = '';
            if(!empty($res_heartlog)){
                $apk_version = $res_heartlog['apk_version'];
            }

            if($hotel_info['box_type']==6){
                $is_compress = 0;
            }else{
                $is_compress = 1;
            }
            $is_compress = 0;
            $tv_forscreen_type = 1;//1正常 2极简投屏
            if($hotel_info['box_type']==7 && $hotel_info['is_open_simple']==1 && $hotel_info['is_4g']==0){
                $tv_forscreen_type = 2;
            }
            $data['box_id'] = $hotel_info['box_id'];
            $data['is_compress'] = $is_compress;
            $data['hotel_name'] = $hotel_info['hotel_name'];
            $data['room_name'] = $hotel_info['room_name'];
            $data['box_type'] = $hotel_info['box_type'];
            $data['apk_version'] = $apk_version;
            $data['is_interact'] = 0;
            $data['wifi_name'] = $hotel_info['wifi_name'];
            $data['wifi_password'] = $hotel_info['wifi_password'];
            $data['chunkSize']  = 1024*1024*3;
            $data['maxConcurrency'] = 3;
            $data['limit_video_size'] = 10485760;
            $data['tail_lenth']   = 1024*1024;
            $data['max_video_size'] = 1024*1024*300;
            $data['max_user_forvideo_size'] = 1024*1024*5;
            $data['max_user_forimage_size'] = 1024*1024*2;
            $data['simple_forscreen_timeout_time'] = 120000;   //极简投屏超时时间
            $data['wifi_timeout_time'] = 20000;   //链接wifi超时时间
            $data['forscreen_timeout_time'] = 10000;   //投屏超时时间
            $data['image_quality'] = 10;
            $data['image_compress'] = 1024*1024*50;
            $data['tv_forscreen_type'] = $tv_forscreen_type;
        }else{
            $data = array('is_have'=>0);
        }
        $audit_key = C('SAPP_PUBLIC_AUDITNUM').$openid;
        $redis->select(5);
        $res_audit = $redis->get($audit_key);
        $audit_tips = '';
        if(!empty($action) && $action=='index' && !empty($res_audit) && $res_audit>0){
            $audit_tips = '您的内容已经通过审核，在小程序发现页面可以看到';
            $redis->remove($audit_key);
        }
        $forscreen_openids = C('COLLECT_FORSCREEN_OPENIDS');
        $is_test = 0;
        if(array_key_exists($openid,$forscreen_openids)){
            $is_test = 1;
        }
        $data['is_test'] = $is_test;
        $data['audit_tips'] = $audit_tips;
        $this->to_back($data);
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
            $redis->remove($cache_key);
        }
        $this->to_back(10000);
    }

    /*
     * type 1:小码2:大码(节目)3:手机小程序呼码5:大码（新节目）6:极简版7:主干版桌牌码8:小程序二维码9:极简版节目大码
     * 10:极简版大码11:极简版呼玛12:大二维码（节目）13:小程序呼二维码 15:大二维码（新节目）16：极简版二维码19:极简版节目大二维码
     * 20:极简版大二维码21:极简版呼二维码22购物二维码 23销售二维码 24菜品商家 25单个菜品 26海报分销售卖商品 27 商城商家 28商城商品大屏购买 29推广渠道投屏码 30投屏帮助视频
     */
    public function recodeQrcodeLog(){
        $openid = $this->params['openid'];
        $type = intval($this->params['type']);
        $data_id = intval($this->params['data_id']);
        $box_id = intval($this->params['box_id']);
        $box_mac = $this->params['box_mac'];
        $mobile_brand = !empty($this->params['mobile_brand']) ? $this->params['mobile_brand']:'';
        $mobile_model = !empty($this->params['mobile_model']) ? $this->params['mobile_model']:'';

        $data = array('openid'=>$openid,'type'=>$type,'is_overtime'=>0,'data_id'=>$data_id);
        if($box_id){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(15);
            $cache_key = 'savor_box_'.$box_id;
            $redis_box_info = $redis->get($cache_key);
            if(!empty($redis_box_info)){
                $box_info = json_decode($redis_box_info,true);
                $data['box_mac'] = $box_info['mac'];
            }
        }else{
            if(!empty($box_mac)){
                $data['box_mac'] = $box_mac;
            }
        }
        if(!empty($mobile_brand)){
            $data['mobile_brand'] = $mobile_brand;
        }
        if(!empty($mobile_model)){
            $data['mobile_model'] = $mobile_model;
        }
        $m_qrcode_log = new \Common\Model\Smallapp\QrcodeLogModel();
        $m_qrcode_log->addInfo($data);
        $this->to_back(10000);
    }

    /**
     * @desc 扫码链接电视
     */
    public function genCode(){
        $box_mac = $this->params['box_mac'];
        $openid  = $this->params['openid'];
        $type    = $this->params['type'];
        $box_id = $this->params['box_id'];
        $goods_id = $this->params['goods_id'];
        $box_mac = !empty($this->params['box_mac']) ? $this->params['box_mac']:'';
        $data_id = !empty($this->params['data_id']) ? $this->params['data_id']:'0';
        $mobile_brand = !empty($this->params['mobile_brand']) ? $this->params['mobile_brand']:'';
        $mobile_model = !empty($this->params['mobile_model']) ? $this->params['mobile_model']:'';

        if(!empty($box_id)){
            $redis= SavorRedis::getInstance();
            $redis->select(15);
            $cache_key = "savor_box_".$box_id;
            $box_redis_info = $redis->get($cache_key);
            $box_redis_info = json_decode($box_redis_info,true);
            $box_mac = $box_redis_info['mac'];
        }
        if($openid=='undefined') $this->to_back(10000);
        $info = array();
        if(!empty($box_mac)){
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
            $info['box_mac'] = $box_mac;
        }

        //记录日志
        $this->recodeScannCode($data_id,$box_mac,$openid,$type,0,$mobile_brand,$mobile_model);
        $this->to_back($info);
    }
    public function getConfig(){
        $box_id = intval($this->params['box_id']);
        $openid = $this->params['openid'];

        list($t1, $t2) = explode(' ', microtime());
        $sys_time = (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
        $file_exts = C('SAPP_FILE_FORSCREEN_TYPES');
        $share_file_exts = C('SHARE_FILE_TYPES');
        $exp_time   = 7200000;//扫码失效时间
        $redpacket_exp_time = 1800000;
        $data = array('sys_time'=>$sys_time,'exp_time'=>$exp_time,'redpacket_exp_time'=>$redpacket_exp_time,
            'file_exts'=>array_keys($file_exts),'share_file_exts'=>$share_file_exts);
        $data['file_max_size'] = 41943040;
        $data['polling_time']  = 120;  //文件投屏默认轮询时间60s
        $data['forscreen_call_code_filename']  = 're6bB4RHfC.mp4';
        $quality_types = C('QUALITY_TYPES');
        $quality_list = array();
        foreach ($quality_types as $k=>$v){
            $checked = false;
            if($k==3){
                $checked = true;
            }
            $quality_list[]=array('value'=>$k,'name'=>$v['name'],'quality'=>$v['value'],'checked'=>$checked);
        }
        $data['quality_list'] = $quality_list;

        $tags = $staffuser_info = $reward_money = $cacsi = array();
        $is_comment = 0;
        $is_open_reward = 1;
        $is_open_simplehistory = 0;
        $seckill_goods_id = 0;
        if($box_id){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(15);
            $cache_key = 'savor_box_'.$box_id;
            $redis_box_info = $redis->get($cache_key);
            if(!empty($redis_box_info)){
                $box_info = json_decode($redis_box_info,true);
                $is_comment = intval($box_info['is_open_popcomment']);

                $cache_key = 'savor_room_' . $box_info['room_id'];
                $redis_room_info = $redis->get($cache_key);
                $room_info = json_decode($redis_room_info, true);

                $cache_key = 'savor_hotel_' . $room_info['hotel_id'];
                $redis_hotel_info = $redis->get($cache_key);
                $res_hotel = json_decode($redis_hotel_info, true);

                $hotel_id = $room_info['hotel_id'];
                $room_id = $box_info['room_id'];

                $m_hotelext = new \Common\Model\HotelExtModel();
                $res_ext = $m_hotelext->getOnerow(array('hotel_id'=>$hotel_id));

                $m_staff = new \Common\Model\Integral\StaffModel();
                $staff_where = array('hotel_id'=>$hotel_id,'status'=>1);
                $staff_where['room_ids'] = array('like',"%,$room_id,%");
                $res_staff = $m_staff->getInfo($staff_where);

                $comment_str = '服务评分';
                $waiter_str = '服务专员';
                $service_str = '“很高兴为您服务，期待您对本次饭局的评价。您的评价将是我们前进的动力及导向！”';
                if(!empty($res_staff)){
                    $staff_openid = $res_staff['openid'];
                    $m_user = new \Common\Model\Smallapp\UserModel();
                    $where = array('openid'=>$staff_openid);
                    $user_info = $m_user->getOne('avatarUrl,nickName',$where,'id desc');
                    $staffuser_info = array('staff_id'=>$res_staff['id'],'avatarUrl'=>$user_info['avatarUrl'],'nickName'=>$user_info['nickName'],
                        'comment_str'=>$comment_str,'waiter_str'=>$waiter_str,'service_str'=>$service_str);
                }else{
                    $comment_str = '餐厅评分';
                    $waiter_str = '';

                    $m_merchant = new \Common\Model\Integral\MerchantModel();
                    $res_merchant = $m_merchant->getInfo(array('hotel_id'=>$hotel_id,'status'=>1));
                    $res_staff = array();
                    if(!empty($res_merchant)){
                        $m_staff = new \Common\Model\Integral\StaffModel();
                        $staff_where = array('merchant_id'=>$res_merchant['id'],'status'=>1,'level'=>1);
                        $res_staff = $m_staff->getDataList('*',$staff_where,'id asc');
                    }
                    if(!empty($res_staff)){
                        $staff_openid = $res_staff[0]['openid'];
                        $m_user = new \Common\Model\Smallapp\UserModel();
                        $where = array('openid'=>$staff_openid);
                        $user_info = $m_user->getOne('avatarUrl,nickName',$where,'id desc');
                        $avatarUrl = $user_info['avatarUrl'];
                        $nickName = $user_info['nickName'];
                    }else{
                        $m_media = new \Common\Model\MediaModel();
                        $res_media = $m_media->getMediaInfoById($res_ext['hotel_cover_media_id']);
                        $avatarUrl = 'http://oss.littlehotspot.com/media/resource/kS3MPQBs7Y.png';
                        if(!empty($res_media)){
                            $avatarUrl = $res_media['oss_addr'].'?x-oss-process=image/resize,p_20';
                        }
                        $nickName = $res_hotel['name'];
                    }

                    $staffuser_info = array('staff_id'=>0,'nickName'=>$nickName,'avatarUrl'=>$avatarUrl,
                        'comment_str'=>$comment_str,'waiter_str'=>$waiter_str,'service_str'=>$service_str);
                }
                $m_tags = new \Common\Model\Smallapp\TagsModel();
                $fields = 'id,hotel_id,satisfaction,name';
                $where = array('status'=>1,'category'=>1);
                $where['hotel_id'] = array('in',array($hotel_id,0));
                $res_tags = $m_tags->getDataList($fields,$where,'id desc');
                $hotel_satisfaction = array();
                $default_satisfaction = array();
                foreach ($res_tags as $v){
                    if($v['satisfaction']){
                        $info = array('id'=>$v['id'],'name'=>$v['name'],'selected'=>false);;
                        if($v['hotel_id']){
                            $hotel_satisfaction[$v['satisfaction']][] = $info;
                        }else{
                            $default_satisfaction[$v['satisfaction']][] = $info;
                        }
                    }
                }
                $reward_money_list = C('REWARD_MONEY_LIST');
                $reward_money = array();
                $oss_host = C('OSS_HOST');
                foreach ($reward_money_list as $v){
                    $v['image'] = 'http://'.$oss_host.'/'.$v['image'];
                    $v['selected'] = false;
                    $reward_money[]=$v;
                }
                if(isset($box_info['is_open_reward'])){
                    $is_open_reward = $box_info['is_open_reward'];
                }else{
                    $is_open_reward = 1;
                }
                $comment_cacsi = C('COMMENT_CACSI');
                foreach ($comment_cacsi as $k=>$v){
                    $label = isset($hotel_satisfaction[$k])?$hotel_satisfaction[$k]:$default_satisfaction[$k];
                    $comment_cacsi[$k]['label'] = $label;
                }
                $cacsi = $comment_cacsi;

                if($res_ext['is_comment']==0){
                    $is_comment = 0;
                }
                if($res_ext['is_reward']==0){
                    $is_open_reward = 0;
                }

                $seckill_goods_id = C('LAIMAO_SECKILL_GOODS_ID');
                $m_hotel_goods = new \Common\Model\Smallapp\HotelgoodsModel();
                $res_hgoods = $m_hotel_goods->getInfo(array('hotel_id'=>$hotel_id,'goods_id'=>$seckill_goods_id));
                if(empty($res_hgoods)){
                    $seckill_goods_id = 0;
                }
            }

            $data['is_open_popcomment'] = 0;
            $data['cacsi'] = $cacsi;
            $data['staff_user_info'] = $staffuser_info;
            $data['reward_money'] = $reward_money;

            $m_heart_log = new \Common\Model\HeartLogModel();
            $res_box_version = $m_heart_log->getInfo('apk_version',array('box_id'=>$box_id));
            $box_version = '';
            if(!empty($res_box_version)){
                $box_version = $res_box_version['apk_version'];
            }
            if($box_version>='2.1.4'){
                $is_open_simplehistory = 1;
            }
        }
        $syslottery_activity_id = 0;
        if(!empty($openid)){
            $fields = 'activity.id as activity_id,activity.start_time,activity.end_time,a.status';
            $where = array('a.openid'=>$openid,'activity.type'=>3);
            $order = 'a.id desc';
            $limit = '0,1';
            $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
            $res_activity_apply = $m_activityapply->getApplyDatas($fields,$where,$order,$limit,'');
            $now_time = date('Y-m-d H:i:s');
            if(!empty($res_activity_apply)){
                switch ($res_activity_apply[0]['status']){
                    case 4:
                        if($now_time>=$res_activity_apply[0]['start_time'] && $now_time<=$res_activity_apply[0]['end_time']){
                            $syslottery_activity_id = $res_activity_apply[0]['activity_id'];
                        }else{
                            $syslottery_activity_id = 0;
                        }
                        break;
                    case 5:
                        $syslottery_activity_id = $res_activity_apply[0]['activity_id'];
                        break;
                    default:
                        $syslottery_activity_id = 0;
                }
            }
        }
        $data['syslottery_activity_id'] = $syslottery_activity_id;
        $data['seckill_goods_id'] = $seckill_goods_id;
        $data['seckill_banner'] = 'http://'.C('OSS_HOST').'/WeChat/MiniProgram/pages/index/index/flash_sale_banner.png';
        $data['is_open_reward'] = $is_open_reward;
        $data['is_comment'] = $is_comment;
        $data['is_open_simplehistory'] = $is_open_simplehistory;
        $data['redpacket_content'] = '即刻分享视频照片，一键投屏，让饭局分享爽不停';
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

    /**
     * @desc 记录用户投屏的图片、视频
     */
    public function recordForScreenPics(){
        $forscreen_id = $this->params['forscreen_id'] ? intval($this->params['forscreen_id']) :0;
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
        $is_share      = $this->params['is_share'] ? intval($this->params['is_share']) : 0;
        $duration      = $this->params['duration'] ? $this->params['duration'] : 0.00;
        $res_nums = $this->params['res_nums']?intval($this->params['res_nums']):0;
        $serial_number = !empty($this->params['serial_number']) ? $this->params['serial_number'] : '';
        $quality_type = !empty($this->params['quality_type']) ? $this->params['quality_type'] : 0;
        $create_time = !empty($this->params['create_time']) ? $this->params['create_time'] : '';
        $is_speed    = !empty($this->params['is_speed']) ? $this->params['is_speed'] : 0;

        $data = array();
        $data['openid'] = $openid;
        $data['box_mac']= $box_mac;
        $data['action'] = $action;
        $data['resource_type'] = $resource_type;
        $data['resource_id']   = $resource_id;
        $data['mobile_brand'] = $mobile_brand;
        $data['mobile_model'] = $mobile_model;
        $data['imgs']   = $imgs;
        $data['forscreen_char'] = !empty($forscreen_char) ? $forscreen_char : '';
        if(!empty($create_time)){
            $data['create_time'] = date('Y-m-d H:i:s',strtotime($create_time));
        }else{
            $data['create_time'] = date('Y-m-d H:i:s');
        }
        $data['res_sup_time']= $res_sup_time;
        $data['res_eup_time']= $res_eup_time;
        $data['resource_size'] = $resource_size;
        $data['is_pub_hotelinfo'] = $is_pub_hotelinfo;
        $data['is_share']    = $is_share;
        $data['duration']    = $duration;
        $data['quality_type'] = $quality_type;
        if($serial_number) $data['serial_number'] = $serial_number;
        if($forscreen_id)  $data['forscreen_id'] = $forscreen_id;

    
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $history_cache_key = C('SAPP_HISTORY_SCREEN').$box_mac.":".$openid;
        if($action==4 || ($action==2 && $resource_type==2)) {
            $history_arr = $data;
            $history_arr['is_speed'] = $is_speed;
            $redis->rpush($history_cache_key, json_encode($history_arr));
        }
    
        if($is_share){
            $forscreen_res = json_decode($imgs,true);
            $oss_addr = $forscreen_res[0];
            $tempInfo = pathinfo($oss_addr);
            $surfix = $tempInfo['extension'];
            if($surfix){
                $surfix = strtolower($surfix);
            }
            $typeinfo = C('RESOURCE_TYPEINFO');
            if(isset($typeinfo[$surfix])){
                $type = $typeinfo[$surfix];
            }else{
                $type = 3;
            }
            $accessKeyId = C('OSS_ACCESS_ID');
            $accessKeySecret = C('OSS_ACCESS_KEY');
            $endpoint = 'oss-cn-beijing.aliyuncs.com';
            $bucket = C('OSS_BUCKET');
            $aliyunoss = new AliyunOss($accessKeyId, $accessKeySecret, $endpoint);
            $aliyunoss->setBucket($bucket);
    
            if(empty($resource_size) || $resource_size=='undefined'){
                $this->to_back(90103);
            }
            if($type==1){//视频
                $range = '0-199';
                $bengin_info = $aliyunoss->getObject($oss_addr,$range);
                $last_size = $resource_size-1;
                $last_range = $last_size - 199;
                $last_range = $last_range.'-'.$last_size;
                $end_info = $aliyunoss->getObject($oss_addr,$last_range);
                $file_str = md5($bengin_info).md5($end_info);
                $fileinfo = strtoupper($file_str);
            }else{
                $fileinfo = $aliyunoss->getObject($oss_addr,'');
            }
            if(empty($fileinfo)){
                $this->to_back(90104);
            }
            if($fileinfo){
                $data['md5_file'] = md5($fileinfo);
            }
            if($type==1){
                $data['duration'] = $duration;
            }else{
                $data['duration'] = 0;
            }
            $m_box = new \Common\Model\BoxModel();
            $box_info = $m_box->getHotelInfoByBoxMacNew($box_mac);
            $data['area_id']    = $box_info['area_id'];
            $data['area_name']  = $box_info['area_name'];
            $data['hotel_id']   = $box_info['hotel_id'];
            $data['hotel_name'] = $box_info['hotel_name'];
            $data['room_id']    = $box_info['room_id'];
            $data['room_name']  = $box_info['room_name'];
            $data['box_id']     = $box_info['box_id'];
            $data['is_4g']      = $box_info['is_4g'];
            $data['box_type']   = $box_info['box_type'];
            $data['hotel_box_type'] = $box_info['hotel_box_type'];
            $data['hotel_is_4g']= $box_info['hotel_is_4g'];
            $data['box_name']   = $box_info['box_name'];

            $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
            $m_forscreen->add($data);
    
            $m_public = new \Common\Model\Smallapp\PublicModel();
            $res_public = $m_public->getOne('id',array('forscreen_id'=>$forscreen_id,'openid'=>$openid),'id desc');
            if(empty($res_public)){
                $public_data = array();
                $public_data['forscreen_id'] = $forscreen_id;
                $public_data['openid'] = $openid;
                $public_data['box_mac']= $box_mac;
                $public_data['public_text'] = $public_text;
                if($action==4){
                    $public_data['res_type'] = 1;
                }else if($action==2 && $resource_type==2){
                    $public_data['res_type'] = 2;
                    $public_data['duration'] = $duration;
                }
                $public_data['res_url']   = $oss_addr;
                $public_data['is_pub_hotelinfo'] =$is_pub_hotelinfo;
                if($res_nums){
                    $public_data['res_nums'] = $res_nums;
                }

                $m_invalidlist = new \Common\Model\Smallapp\ForscreenInvalidlistModel();
                $res_invalid = $m_invalidlist->getInfo(array('invalidid'=>$openid,'type'=>2));
                if(empty($res_invalid)){
                    $sms_config = C('ALIYUN_SMS_CONFIG');
                    $alisms = new \Common\Lib\AliyunSms();
                    $template_code = $sms_config['public_audit_templateid'];
                    $send_mobiles = C('PUBLIC_AUDIT_MOBILE');
                    if(!empty($send_mobiles)){
                        foreach ($send_mobiles as $v){
                            $alisms::sendSms($v,'',$template_code);
                        }
                    }
                    $public_data['status'] =1;
                }else{
                    $public_data['status'] =0;
                }

                $m_public->add($public_data);
            }
            $pubdetail_data = array('forscreen_id'=>$forscreen_id,'resource_id'=>$resource_id,
                'res_url'=>$oss_addr,'duration'=>$duration,'resource_size'=>$resource_size);
            $m_publicdetail = new \Common\Model\Smallapp\PubdetailModel();
            $m_publicdetail->add($pubdetail_data);
        }else{
            if($is_speed==0){
                $redis = SavorRedis::getInstance();
                $redis->select(5);
                $cache_key = C('SAPP_SCRREN').":".$box_mac;
                $redis->rpush($cache_key, json_encode($data));
            }
        }
        $res = array('forscreen_id'=>$forscreen_id);
        //完成系统用户抽奖任务
        $m_activity_apply = new \Common\Model\Smallapp\ActivityapplyModel();
        $m_activity_apply->finishPrizeTask($openid,$action);
        $this->to_back($res);
    }

    /**
     * @desc 生日歌列表
     */
    public function happylist(){
        $m_ads = new \Common\Model\AdsModel();
        $where = array();
        $oss_host = "http://".C('OSS_HOST').'/';
        $where['a.id'] = array('in','8855,5246,5245,5244');

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

    /**
     * @desc 记录扫码日志
     * @param varchar $box_mac  盒子mac
     * @param varchar $openid   openid
     * @param tinyint $type     1:小码2:大码3:手机小程序呼码
     */
    private function recodeScannCode($data_id,$box_mac,$openid,$type,$is_overtime=0,$mobile_brand='',$mobile_model=''){
        $data = array();
        $data['data_id']= $data_id;
        $data['box_mac'] = $box_mac;
        $data['openid']  = $openid;
        $data['type']    = !empty($type) ? $type :1;
        $data['is_overtime'] = $is_overtime ? $is_overtime :0;
        if(!empty($mobile_brand))   $data['mobile_brand'] = $mobile_brand;
        if(!empty($mobile_model))   $data['mobile_model'] = $mobile_model;
        $m_qrcode_log = new \Common\Model\Smallapp\QrcodeLogModel();
        $m_qrcode_log->addInfo($data);
        if($type==33){
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
            if($meal_type){
                $redis = SavorRedis::getInstance();
                $redis->select(1);
                $key = C('SAPP_SCAN_BOX_CODE');
                $cache_key = $key.':'.$box_mac.':'.date('Ymd');
                $res_redis = $redis->get($cache_key);
                if(empty($res_redis)){
                    $data = array();
                }else{
                    $data = json_decode($res_redis,true);
                }
                if(!isset($data[$meal_type])){
                    $data[$meal_type] = array('openid'=>$openid,'time'=>date('Y-m-d H:i:s'));
                    $redis->set($cache_key,json_encode($data),86400);
                }
            }
        }
        return true;
    }

    private function _gmt_iso8601($time){
        $dtStr = date("c", $time);
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }
}