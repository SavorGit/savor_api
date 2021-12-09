<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class ForscreenController extends CommonController{

    function _init_() {
        switch(ACTION_NAME) {
            case 'collectforscreen':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'type'=>1001);
                break;
            case 'helpimage':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001);
                break;
            case 'cancelforscreen':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'forscreen_id'=>1001,'is_share'=>1001);
                break;
            case 'recordDisplaynum':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'id'=>1001,'type'=>1001);
                break;
            case 'musiclist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001);
                break;
        }
        parent::_init_();
    }

    public function collectforscreen(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $type = $this->params['type'];//1视频 2图片
        $forscreen_openids = C('COLLECT_FORSCREEN_OPENIDS');
        if(!array_key_exists($openid,$forscreen_openids)){
            $this->to_back(array());
        }
        $now_timestamps = getMillisecond();
        $serial_number = "Y_{$openid}_{$now_timestamps}";

        $message_data = array('openid'=>$openid,'forscreen_char'=>'','mobile_brand'=>'HUAWEI','mobile_model'=>'ANA-AN00','serial_number'=>$serial_number,
            'forscreen_id'=>$now_timestamps,'box_mac'=>$box_mac,'resource_id'=>$now_timestamps,'res_sup_time'=>$now_timestamps,'res_eup_time'=>$now_timestamps,
            'create_time'=>date('Y-m-d H:i:s')
            );
        if($type==1){
            $message_data['action']=2;
            $message_data['resource_type']=2;
            $message_data['resource_size']=78299193;
            $message_data['imgs']='["media/resource/exSsMBwBaG.MOV"]';

            $netty_data = array('action'=>2,'resource_type'=>2,'url'=>'media/resource/exSsMBwBaG.MOV','filename'=>"$now_timestamps.mp4",
                'openid'=>$openid,'video_id'=>$now_timestamps,'forscreen_id'=>$now_timestamps
            );
        }else{
            $message_data['action']=4;
            $message_data['resource_type']=1;
            $message_data['resource_size']=239687;
            $message_data['imgs']='["forscreen/resource/1597891208968.jpg"]';

            $img = array('url'=>'forscreen/resource/1597891208968.jpg','filename'=>"$now_timestamps.jpg",'order'=>0,'img_id'=>$now_timestamps,'resource_size'=>239687);
            $netty_data = array('action'=>4,'resource_type'=>1,'openid'=>$openid,'forscreen_id'=>$now_timestamps);
            $netty_data['img_list'] = array($img);
        }

        $netty_url = 'https://mobile.littlehotspot.com/Netty/index/pushnetty';
        $msg = json_encode($netty_data,JSON_UNESCAPED_SLASHES);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $netty_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => array('box_mac'=>$box_mac,'msg'=>"$msg"),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($response,true);
        if(is_array($res) && isset($res['code'])){
            $cache_key = 'smallapp:forscreen:'.$box_mac;
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(5);
            $redis->rpush($cache_key, json_encode($message_data));
        }
        $this->to_back($res);
    }

    public function helpimage(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid,avatarUrl,nickName',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $now_time = date('Y-m-d H:i:s');
        $meal_time = C('MEAL_TIME');
        $lunch_stime = date("Y-m-d {$meal_time['lunch'][0]}:00");
        $lunch_etime = date("Y-m-d {$meal_time['lunch'][1]}:00");
        $dinner_stime = date("Y-m-d {$meal_time['dinner'][0]}:00");
        $dinner_etime = date("Y-m-d {$meal_time['dinner'][1]}:59");
        $meal_type = '';
        if($now_time>=$lunch_stime && $now_time<=$lunch_etime){
            $meal_type = 'lunch';
        }elseif($now_time>=$dinner_stime && $now_time<=$dinner_etime){
            $meal_type = 'dinner';
        }
        $is_show = 1;
        $m_box = new \Common\Model\BoxModel();
        $fields = 'box.id as box_id,hotel.id as hotel_id';
        $bwhere = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
        $res_box = $m_box->getBoxByCondition($fields,$bwhere);
        $hotel_id = $res_box[0]['hotel_id'];
        $m_hotel_goods = new \Common\Model\Smallapp\HotelgoodsModel();
        $seckill_goods_id = C('LAIMAO_SECKILL_GOODS_ID');
        $res_hgoods = $m_hotel_goods->getInfo(array('hotel_id'=>$hotel_id,'goods_id'=>$seckill_goods_id));
        $is_laimao_seckill = 0;
        if(!empty($res_hgoods)){
            $meal_type = '';
            $is_laimao_seckill = 1;
        }

        if(!empty($meal_type)){
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(1);
            $key = C('SAPP_HELPIMAGE');
            $cache_key = $key.':'.$box_mac.':'.date('Ymd');
            $res_redis = $redis->get($cache_key);
            if(!empty($res_redis)){
                $help_data = json_decode($res_redis,true);
                if(isset($help_data[$meal_type])){
                    $is_show = 0;
                }else{
                    $help_data[$meal_type] = date('Y-m-d H:i:s');
                    $redis->set($cache_key,json_encode($help_data),86400);
                }
            }else{
                $help_data = array("$meal_type"=>date('Y-m-d H:i:s'));
                $redis->set($cache_key,json_encode($help_data),86400);
            }
        }

        if($is_show==1){
            $headPic = $nickName = '';
            if(!empty($user_info['avatarUrl'])){
                $headPic = base64_encode($user_info['avatarUrl']);
            }
            if(!empty($user_info['nickName'])){
                $nickName = $user_info['nickName'];
            }
            if($is_laimao_seckill==1){
                $host_name = 'https://'.$_SERVER['HTTP_HOST'];
                $code_url = $host_name."/smallapp46/qrcode/getBoxQrcode?box_mac={$box_mac}&box_id={$res_box[0]['box_id']}&data_id=$seckill_goods_id&type=24";
                $netty_data = array('action'=>40,'goods_id'=>$seckill_goods_id,'qrcode_url'=>$code_url);
            }else{
                $netty_data = array('action'=>150,'headPic'=>$headPic,'nickName'=>$nickName);
            }
            $m_netty = new \Common\Model\NettyModel();
            $res = $m_netty->pushBox($box_mac,json_encode($netty_data));
            $this->to_back($res);
        }else{
            $this->to_back(array());
        }

    }

    public function cancelforscreen(){
        $openid = $this->params['openid'];
        $forscreen_id = $this->params['forscreen_id'];
        $is_share = intval($this->params['is_share']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid,avatarUrl,nickName',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        if($is_share) {
            $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
            $where = array('openid'=>$openid,'forscreen_id'=>$forscreen_id);
            $res_forscreen = $m_forscreen->getWhere('*',$where,'id desc','0,1','');
            if(!empty($res_forscreen)){
                $id = $res_forscreen[0]['id'];
                $m_forscreen->updateInfo(array('id'=>$id),array('is_cancel_forscreen'=>1));
            }
        }else{
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(5);
            $key = C('SAPP_CANCEL_FORSCREEN');
            $cache_key = $key.$openid.'-'.$forscreen_id;
            $redis->set($cache_key,date('Y-m-d H:i:s'),86400);
        }
        $this->to_back(array());
    }

    public function recordDisplaynum(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $id = intval($this->params['id']);
        $type = intval($this->params['type']);//类型1节目,2内容

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid,avatarUrl,nickName',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_box = new \Common\Model\BoxModel();
        $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
        $fields = "box.id as box_id,hotel.id as hotel_id,hotel.area_id";
        $box_info = $m_box->getBoxByCondition($fields,$where);
        $m_datadisplay = new \Common\Model\Smallapp\DatadisplayModel();

        $area_id = $box_info[0]['area_id'];
        $add_date = date('Y-m-d');
        switch ($type){
            case 1:
                $ads_id = $id;
                $res_record = $m_datadisplay->getInfo(array('ads_id'=>$ads_id,'type'=>3,'area_id'=>$area_id,'add_date'=>$add_date));
                if(!empty($res_record)){
                    $m_datadisplay->where(array('id'=>$res_record['id']))->setInc('display_num',1);
                }else{
                    $field = 'a.media_id,b.name,b.oss_addr';
                    $m_ads = new \Common\Model\AdsModel();
                    $res_adsinfo = $m_ads->getAdsList($field,array('a.id'=>$ads_id),'b.id desc','0,1');
                    $data = array('ads_id'=>$ads_id,'media_id'=>$res_adsinfo[0]['media_id'],'resource_name'=>$res_adsinfo[0]['name'],'oss_addr'=>$res_adsinfo[0]['oss_addr'],
                        'area_id'=>$area_id,'display_num'=>1,'type'=>3,'add_date'=>$add_date);
                    $m_datadisplay->add($data);
                }
                break;
            case 2:
                $forscreen_id = $id;
                $res_record = $m_datadisplay->getInfo(array('forscreen_id'=>$forscreen_id,'area_id'=>$area_id,'add_date'=>$add_date));
                if(!empty($res_record)){
                    $m_datadisplay->where(array('id'=>$res_record['id']))->setInc('display_num',1);
                }else{
                    $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
                    $fwhere = array('openid'=>$openid,'forscreen_id'=>$forscreen_id);
                    $res_info = $m_forscreen->getWhere('*',$fwhere,'id asc','0,1','');
                    $imgs = json_decode($res_info[0]['imgs'],true);
                    $oss_addr = $imgs[0];
                    $data = array('forscreen_id'=>$forscreen_id,'resource_id'=>$res_info[0]['resource_id'],'area_id'=>$area_id,
                        'oss_addr'=>$oss_addr,'display_num'=>1,'type'=>2,'add_date'=>$add_date);
                    $m_datadisplay->add($data);
                }
                break;
        }
        $this->to_back(array());
    }

    public function musiclist(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid,avatarUrl,nickName',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }

        $m_welcomeresource = new \Common\Model\Smallapp\WelcomeresourceModel();
        $fields = 'id,forscreen_music_name as name,media_id,color,small_wordsize,type';
        $where = array('status'=>1,'type'=>3,'music_type'=>array('in',array(2,3)));
        $res_resource = $m_welcomeresource->getDataList($fields,$where,'sort desc');
        $music = array();
        if(!empty($res_resource)){
            $m_media = new \Common\Model\MediaModel();
            foreach ($res_resource as $v){
                $res_media = $m_media->getMediaInfoById($v['media_id']);
                $oss_addr = $res_media['oss_addr'];
                $music[]=array('id'=>$v['id'],'name'=>$v['name'],'oss_addr'=>$oss_addr,'oss_path'=>$res_media['oss_path']);
            }
        }

        $res_data = array('music'=>$music);
        $this->to_back($res_data);

    }
}
