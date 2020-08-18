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
                $this->is_verify= 1;
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'data_id'=>1002,'box_id'=>1002);
                break;
            case 'isHaveCallBox':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'pop_eval'=>1002);
                break;
            case 'recordForScreenPics':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1000,
                    'imgs'=>1001,'mobile_brand'=>1000,
                    'mobile_model'=>1000,'action'=>1000,
                    'resource_type'=>1000,'resource_id'=>1000,
                    'is_pub_hotelinfo'=>1000,'is_share'=>1000,
                    'forscreen_id'=>1000,'public_text'=>1000,'res_nums'=>1000,
                    'serial_number'=>1000,
                );
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
                'forscreen_type'=>$forscreen_info['forscreen_type'],'forscreen_method'=>$forscreen_info['forscreen_method'],
                'intranet_ip'=>$intranet_ip,'is_open_popcomment'=>$forscreen_info['is_open_popcomment']);
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
                
                $hotel_info = array('box_id'=>$forscreen_info['box_id'],'box_type'=>$box_info['box_type'],'room_name'=>$room_info['name'],'hotel_name'=>$res_hotel['name'],'wifi_name'=>$box_info['wifi_name'],
                    'wifi_password'=>$box_info['wifi_password'],'wifi_mac'=>$box_info['wifi_mac'],'hotel_id'=>$room_info['hotel_id'],'room_id'=>$box_info['room_id']);
            }else{
                $map = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
                $rets = $m_box->getBoxInfo('d.id hotel_id,c.id room_id,a.id box_id,a.box_type,c.name room_name,d.name hotel_name,a.wifi_name,a.wifi_password,a.wifi_mac',$map);
                $hotel_info = $rets[0];
            }
            if($hotel_info['box_type']==6){
                $is_compress = 0;
            }else{
                $is_compress = 1;
            }
            $is_compress = 0;
            //$data['is_open_popcomment'] = 0;
            //是否打开开关     是否有服务员并且2小时内评价过
            if($data['is_open_popcomment']==1 && !empty($this->params['pop_eval'])){
                //1、是否有服务员
                $m_staff = new \Common\Model\Integral\StaffModel();
                $res_staff = $m_staff->getInfo(array('hotel_id'=>$hotel_info['hotel_id'],'room_id'=>$hotel_info['room_id'],'status'=>1));
                
                if(!empty($res_staff)){//如果有服务员并且两小时内是否有评价
                    $m_comment = new \Common\Model\Smallapp\CommentModel();
                    $comment_time = date('Y-m-d H:i:s',strtotime('-2 hours'));
                    $comment_count = $m_comment->isHaveComment($openid,$comment_time);
                    //print_r($comment_count);exit;
                    if(empty($comment_count)){
                       $data['is_open_popcomment'] = 1;
                       $staff_openid = $res_staff['openid'];
                       $where = array('openid'=>$staff_openid);
                       $m_user = new \Common\Model\Smallapp\UserModel();
                       $where = array('openid'=>$staff_openid);
                       $user_info = $m_user->getOne('avatarUrl,nickName',$where,'id desc');
                       $staffuser_info = array('staff_id'=>$res_staff['id'],'avatarUrl'=>$user_info['avatarUrl'],'nickName'=>$user_info['nickName']);
                       $data['staff_user_info'] = $staffuser_info;
                       $m_tags = new \Common\Model\Smallapp\TagsModel();
                       $fields = 'id,name';
                       $where = array('status'=>1,'category'=>1);
                       $where['hotel_id'] = array('in',array($hotel_info['hotel_id'],0));
                       $res_tags = $m_tags->getDataList($fields,$where,'type desc,id desc');
                       foreach ($res_tags as $v){
                           $tags[] = array('id'=>$v['id'],'value'=>$v['name'],'selected'=>false);
                       }
                       $data['tags'] = $tags;
                    }else {
                        $data['is_open_popcomment'] = 0;
                    }  
                    
                }else {
                    $data['is_open_popcomment'] = 0;
                }
            }
            
            $data['box_id'] = $hotel_info['box_id'];
            $data['is_compress'] = $is_compress;
            $data['hotel_name'] = $hotel_info['hotel_name'];
            $data['room_name'] = $hotel_info['room_name'];
            $data['wifi_name'] = $hotel_info['wifi_name'];
            $data['wifi_password'] = $hotel_info['wifi_password'];
        }else{
            $data = array('is_have'=>0);
        }
        $this->to_back($data);
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
        }
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
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['res_sup_time']= $res_sup_time;
        $data['res_eup_time']= $res_eup_time;
        $data['resource_size'] = $resource_size;
        $data['is_pub_hotelinfo'] = $is_pub_hotelinfo;
        $data['is_share']    = $is_share;
        $data['duration']    = $duration;
        if($serial_number) $data['serial_number'] = $serial_number;
        
        if($forscreen_id)  $data['forscreen_id'] = $forscreen_id;
    
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $history_cache_key = C('SAPP_HISTORY_SCREEN').$box_mac.":".$openid;
        if($action==4 || ($action==2 && $resource_type==2)) {
            $redis->rpush($history_cache_key, json_encode($data));
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
                $public_data['status'] =1;
                $m_public->add($public_data);
            }
            $pubdetail_data = array('forscreen_id'=>$forscreen_id,'resource_id'=>$resource_id,
                'res_url'=>$oss_addr,'duration'=>$duration,'resource_size'=>$resource_size);
            $m_publicdetail = new \Common\Model\Smallapp\PubdetailModel();
            $m_publicdetail->add($pubdetail_data);
        }else{
            $redis = SavorRedis::getInstance();
            $redis->select(5);
            $cache_key = C('SAPP_SCRREN').":".$box_mac;
            $redis->rpush($cache_key, json_encode($data));
        }
        $res = array('forscreen_id'=>$forscreen_id);
        $this->to_back($res);
    }
}