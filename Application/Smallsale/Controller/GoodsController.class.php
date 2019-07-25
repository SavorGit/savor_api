<?php
namespace Smallsale\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\AliyunOss;

class GoodsController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getGoodslist':
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001,'type'=>1001,'hotel_id'=>1001,'openid'=>1001);
                break;
            case 'addActivityGoods':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001,'oss_addr'=>1001,'oss_filesize'=>1002,
                    'price'=>1001, 'start_time'=>1001,'end_time'=>1001,'scope'=>1001,'goods_id'=>1002);
                break;
            case 'getPlayList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
            case 'removePlaygoods':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'goods_id'=>1001);
                break;
            case 'programPlay':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'goods_id'=>1001);
                break;


        }
        parent::_init_();
    }

    public function getGoodslist(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 5;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }

        $hotel_id = intval($this->params['hotel_id']);
        $page = intval($this->params['page']);
        $pagesize = 15;
        $all_nums = $page * $pagesize;
        $type = $this->params['type'];//10官方活动促销,20我的活动
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $fields = 'g.id as goods_id,g.name,g.media_id,g.imgmedia_id,g.price,g.rebate_integral,g.jd_url';
        $where = array('h.hotel_id'=>$hotel_id,'g.type'=>$type);
        if($type==20){
            $fields .= ' ,g.start_time,g.end_time,g.scope,g.status';
            $where['h.openid'] = $openid;
        }else{
            $where['g.status'] = 2;
        }
        $orderby = 'g.id desc';
        $limit = "0,$all_nums";
        $res_goods = $m_hotelgoods->getList($fields,$where,$orderby,$limit);
        $m_media = new \Common\Model\MediaModel();
        $datalist = array();
        foreach ($res_goods as $v){
            $media_id = $v['media_id'];
            $imgmedia_id = $v['imgmedia_id'];
            $media_info = $m_media->getMediaInfoById($media_id);
            $v['oss_addr'] = $media_info['oss_path'];
            $v['media_type'] = $media_info['type'];
            if($media_info['type']==2){
                $v['img_url'] = $media_info['oss_addr'];
            }else{
                if($imgmedia_id){
                    $media_info = $m_media->getMediaInfoById($imgmedia_id);
                    $v['img_url'] = $media_info['oss_addr'];
                }else{
                    $v['img_url'] = $media_info['oss_addr'].'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450';
                }
            }
            unset($v['media_id'],$v['imgmedia_id']);
            $datalist[] = $v;
        }
        $data = array('datalist'=>$datalist);
        $this->to_back($data);
    }

    public function getdetail(){
        $goods_id= intval($this->params['goods_id']);
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if($res_goods['status']!=2){
            $this->to_back(92020);
        }
        $data = array('goods_id'=>$goods_id,'name'=>$res_goods['name'],'jd_url'=>$res_goods['jd_url'],'type'=>$res_goods['type']);

        $media_id = $res_goods['media_id'];
        $imgmedia_id = $res_goods['imgmedia_id'];
        $m_media = new \Common\Model\MediaModel();
        $media_info = $m_media->getMediaInfoById($media_id);
        if($media_info['type']==2){
            $data['img_url'] = $media_info['oss_addr'];
        }else{
            if($imgmedia_id){
                $media_info = $m_media->getMediaInfoById($imgmedia_id);
                $data['img_url'] = $media_info['oss_addr'];
            }else{
                $data['img_url'] = $media_info['oss_addr'].'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450';
            }
        }
        $this->to_back($data);
    }

    public function addActivityGoods(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $oss_addr = $this->params['oss_addr'];
        $oss_filesize = $this->params['oss_filesize'];
        $price = $this->params['price'];
        $start_time = $this->params['start_time'];
        $end_time = $this->params['end_time'];
        $scope = intval($this->params['scope']);//0全部,1包间,2非包间
        $goods_id = intval($this->params['goods_id']);
        $tmp_start_time = strtotime($start_time);
        $tmp_end_time = strtotime($end_time);
        if($tmp_start_time>$tmp_end_time){
            $this->to_back(92012);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 5;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $res_hotelgoods = $m_hotelgoods->getInfo(array('hotel_id'=>$hotel_id,'openid'=>$openid));
        if(!empty($res_hotelgoods)){
            if(!$goods_id || ($goods_id && $res_hotelgoods['goods_id']!=$goods_id)){
                $this->to_back(92013);
            }
        }

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
        $data = array('price'=>$price,'type'=>20,'scope'=>$scope,'status'=>1,
            'start_time'=>date('Y-m-d 00:00:00',$tmp_start_time),
            'end_time'=>date('Y-m-d 23:59:59',$tmp_end_time),
        );

        $accessKeyId = C('OSS_ACCESS_ID');
        $accessKeySecret = C('OSS_ACCESS_KEY');
        $endpoint = 'oss-cn-beijing.aliyuncs.com';
        $bucket = C('OSS_BUCKET');
        $aliyunoss = new AliyunOss($accessKeyId, $accessKeySecret, $endpoint);
        $aliyunoss->setBucket($bucket);
        $media_id = 0;
        if($oss_filesize){
            if($type==1){//视频
                $range = '0-199';
                $bengin_info = $aliyunoss->getObject($oss_addr,$range);
                $last_size = $oss_filesize-1;
                $last_range = $last_size - 199;
                $last_range = $last_range.'-'.$last_size;
                $end_info = $aliyunoss->getObject($oss_addr,$last_range);
                $file_str = md5($bengin_info).md5($end_info);
                $fileinfo = strtoupper($file_str);
            }else{
                $fileinfo = $aliyunoss->getObject($oss_addr,'');
            }
            if($fileinfo){
                $md5 = md5($fileinfo);
            }else{
                $this->to_back(92017);
            }
            $m_media = new \Common\Model\MediaModel();
            $add_mediadata = array('oss_addr'=>$oss_addr,'oss_filesize'=>$oss_filesize,'surfix'=>$surfix,
                'type'=>$type,'md5'=>$md5,'create_time'=>date('Y-m-d H:i:s'));
            $media_id = $m_media->add($add_mediadata);
        }
        if(!$goods_id && !$media_id){
            $this->to_back(92017);
        }
        if($media_id){
            $data['media_id'] = $media_id;
        }
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        if($goods_id){
            $m_goods->updateData(array('id'=>$goods_id),$data);
        }else{
            $gid = $m_goods->addData($data);
            $hotelgoods_data = array('hotel_id'=>$hotel_id,'openid'=>$openid,'goods_id'=>$gid);
            $m_hotelgoods->addData($hotelgoods_data);
        }
        $res_data = array('goods_id'=>intval($goods_id),'media_type'=>$type);
        $this->to_back($res_data);
    }


    public function getPlayList(){
        $hotel_id = $this->params['hotel_id'];
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);

        $cache_key = C('SAPP_DINNER').'activitygoods:loopplay:'.$hotel_id;
        $res_cache = $redis->get($cache_key);
        if(!empty($res_cache)){
            $loopplay_data = json_decode($res_cache,true);
        }else{
            $fields = 'h.hotel_id,h.goods_id';
            $where = array('h.hotel_id'=>$hotel_id,'g.status'=>2);
            $orderby = 'g.id desc';
            $limit = "0,1";
            $res_goods = $m_hotelgoods->getList($fields,$where,$orderby,$limit);
            $loopplay_data = array($res_goods[0]['goods_id']=>$res_goods[0]['goods_id']);
            $redis->set($cache_key,json_encode($loopplay_data));

            $program_key = C('SAPP_DINNER_ACTIVITYGOODS_PROGRAM');
            $period = getMillisecond();
            $period_data = array('period'=>$period);
            $redis->set($program_key,json_encode($period_data));
        }

        $goods_ids = array_keys($loopplay_data);
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $fields = 'id as goods_id,name,media_id,imgmedia_id,price,rebate_integral,jd_url';
        $where = array('status'=>2);
        $where['id'] = array('in',$goods_ids);
        $res_goods = $m_goods->getDataList($fields,$where,'id desc',0,5);
        $m_media = new \Common\Model\MediaModel();
        $datalist = array();
        foreach ($res_goods['list'] as $v){
            $media_id = $v['media_id'];
            $imgmedia_id = $v['imgmedia_id'];
            $media_info = $m_media->getMediaInfoById($media_id);
            $v['oss_addr'] = $media_info['oss_path'];
            $v['media_type'] = $media_info['type'];
            if($media_info['type']==2){
                $v['img_url'] = $media_info['oss_addr'];
            }else{
                if($imgmedia_id){
                    $media_info = $m_media->getMediaInfoById($imgmedia_id);
                    $v['img_url'] = $media_info['oss_addr'];
                }else{
                    $v['img_url'] = $media_info['oss_addr'].'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450';
                }
            }
            unset($v['media_id'],$v['imgmedia_id']);
            $datalist[] = $v;
        }
        $data = array('datalist'=>$datalist);
        $this->to_back($data);
    }

    public function removePlaygoods(){
        $hotel_id = $this->params['hotel_id'];
        $goods_id = $this->params['goods_id'];
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $where = array('hotel_id'=>$hotel_id,'goods_id'=>$goods_id);
        $res_hotelgoods = $m_hotelgoods->getInfo($where);
        if(empty($res_hotelgoods)){
            $this->to_back(92018);
        }
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = C('SAPP_DINNER').'activitygoods:loopplay:'.$hotel_id;
        $res_cache = $redis->get($cache_key);
        if(empty($res_cache)){
            $this->to_back(92019);
        }
        $loopplay_data = json_decode($res_cache,true);
        if(isset($loopplay_data[$goods_id])){
            unset($loopplay_data[$goods_id]);
        }
        $redis->set($cache_key,json_encode($loopplay_data));

        $program_key = C('SAPP_DINNER_ACTIVITYGOODS_PROGRAM');
        $period = getMillisecond();
        $period_data = array('period'=>$period);
        $redis->set($program_key,json_encode($period_data));

        $this->to_back(array());
    }

    public function programPlay(){
        $box_mac = $this->params['box_mac'];
        $goods_id = intval($this->params['goods_id']);
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if($res_goods['status']!=2){
            $this->to_back(92014);
        }

        $m_box = new \Common\Model\BoxModel();
        $map = array();
        $map['a.mac'] = $box_mac;
        $map['a.state'] = 1;
        $map['a.flag']  = 0;
        $map['d.state'] = 1;
        $map['d.flag']  = 0;
        $box_info = $m_box->getBoxInfo('a.id as box_id,d.id as hotel_id', $map);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $forscreen_id = getMillisecond();
        $m_netty = new \Common\Model\NettyModel();
        $message = array('action'=>41,'goods_id'=>$goods_id,'forscreen_id'=>$forscreen_id);
        $res = $m_netty->pushBox($box_mac,json_encode($message));
        if(isset($res['error_code']) && $res['error_code']==90109){
            $this->to_back(92015);
        }
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = C('SAPP_DINNER').'activitygoods:loopplay:'.$box_info[0]['hotel_id'];
        $res_cache = $redis->get($cache_key);
        if(!empty($res_cache)){
            $data = json_decode($res_cache,true);
        }else{
            $data = array();
        }
        $m_sysconfig = new \Common\Model\SysConfigModel();
        $res_config = $m_sysconfig->getAllconfig();
        if($res_config['activity_adv_playtype']==1){
            $data = array();
        }else{
            if(count($data)>5){
                $this->to_back(92016);
            }
        }
        $redis->select(14);
        $program_key = C('SAPP_DINNER_ACTIVITYGOODS_PROGRAM');
        $period = getMillisecond();
        $period_data = array('period'=>$period);
        $redis->set($program_key,json_encode($period_data));

        $data[$goods_id] = $goods_id;
        $redis->set($cache_key,json_encode($data));
        $this->to_back(array());
    }




}