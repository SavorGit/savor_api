<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
class ContentController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'hotplay':
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001,'pagesize'=>1002,'box_mac'=>1002);
                break;
            case 'hotplaylist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001,'pagesize'=>1002,'box_mac'=>1002);
                break;
            case 'programlist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001,'pagesize'=>1001,'box_mac'=>1001);
                break;
            case 'nearhotel':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001);
                break;
            case 'hotdrinks':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001);
                break;
            case 'addFormid':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'formid'=>1001);
                break;
            case 'guidePrompt':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'type'=>1001);
                break;
            case 'initdata':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'adsinfo':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'res_id'=>1001);
                break;
            case 'feedback':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'contact'=>1002,'mobile'=>1002,'content'=>1001,
                    'mobile_brand'=>1001,'mobile_model'=>1001);
                break;
        }
        parent::_init_();
    }

    public function initdata(){
        $openid = $this->params['openid'];
        $goods_list = $this->init_goodsdata($openid);
        $hotels = $this->init_hoteldata();
        $forscreen_hotels = array('hotels'=>$hotels);
        $user_data = $this->init_userdata($openid);

        $data = array('optimize_data'=>$goods_list,'forscreen_hotels'=>$forscreen_hotels,'public_list'=>$user_data['public_list'],
            'collect_list'=>$user_data['collect_list']);
        $this->to_back($data);
    }

    public function hotplaylist(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $pagesize = !empty($this->params['pagesize'])?intval($this->params['pagesize']):6;
        $box_mac = $this->params['box_mac'];
        $all_hot_nums = 8;

        $where = array('status'=>1);
        $orderby = 'sort desc';
        $m_hotplay = new \Common\Model\Smallapp\HotplayModel();
        $res_play = $m_hotplay->getDataList('*',$where,$orderby,0,$all_hot_nums);

        $oss_host = get_oss_host();
        $default_avatar = $oss_host.'media/resource/btCfRRhHkn.jpg';
        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $m_public = new \Common\Model\Smallapp\PublicModel();
        $m_pubdetail = new \Common\Model\Smallapp\PubdetailModel();
        $m_user = new \Common\Model\Smallapp\UserModel();
        $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
        $m_ads = new \Common\Model\AdsModel();
        $m_media = new \Common\Model\MediaModel();

        $datalist = array();
        foreach ($res_play['list'] as $v){
            $res_id = $v['forscreen_record_id'];
            $res_play_nums = $m_play_log->getOne('*',array('res_id'=>$res_id,'type'=>4),'id desc');
            if(!empty($res_play_nums)){
                $play_nums = $res_play_nums['nums'];
            }else{
                $play_nums = 0;
            }
            $play_nums = $play_nums+$v['init_playnum'];
            if($play_nums>100000){
                $play_nums = floor($play_nums/10000).'w';
            }

            $dinfo = array('res_id'=>$res_id,'play_nums'=>$play_nums,'is_show'=>1,'type'=>1,'title'=>'');
            $res_public = $m_public->getOne('*',array('id'=>$v['data_id']),'');
            $dinfo['forscreen_id'] = $res_public['forscreen_id'];
            $dinfo['res_type'] = $res_public['res_type'];
            $where = array('openid'=>$res_public['openid']);
            $fields = 'id user_id,avatarUrl,nickName';
            $res_user = $m_user->getOne($fields, $where);
            $dinfo['nickName'] = $res_user['nickName'];
            $dinfo['avatarUrl'] = $res_user['avatarUrl'];
            $fields_forscreen = 'res_url,duration,resource_size,resource_id,width,height';
            $all_forscreen = $m_pubdetail->getWhere($fields_forscreen,array('forscreen_id'=>$res_public['forscreen_id']),'id asc','','');
            $dinfo['res_nums'] = count($all_forscreen);
            $whtype = 0;//0默认 1横版 2竖版
            if($dinfo['res_type']==2){
                if($all_forscreen[0]['width']>$all_forscreen[0]['height']){
                    $whtype = 1;
                }else{
                    $whtype = 2;
                }
            }
            $dinfo['whtype'] = $whtype;
            $now = time();
            $diff_time =  $now - strtotime($res_public['create_time']);
            if($diff_time<=86400){
                $create_time = viewTimes(strtotime($res_public['create_time']));
            }else{
                $create_time = '';
            }
            $hotel_name = '';
            $res_forscreen = $m_forscreen->getInfo(array('id'=>$res_id));
            if(!empty($res_forscreen)){
                $hotel_name = $res_forscreen['hotel_name'];
            }
            $dinfo['create_time'] = $create_time;
            $dinfo['hotel_name'] = $hotel_name;

            $pubdetails = array();
            foreach ($all_forscreen as $dv){
                $forscreen_url = $dv['res_url'];
                $res_url = $oss_host.$forscreen_url;
                if($dinfo['res_type']==1){
                    $img_url = $res_url."?x-oss-process=image/quality,Q_50";
                }else{
                    $img_url = $oss_host.$forscreen_url.'?x-oss-process=video/snapshot,t_3000,f_jpg,w_450,m_fast';
                }
                $pubdetail = array('res_url'=>$res_url,'img_url'=>$img_url,'forscreen_url'=>$forscreen_url,'duration'=>$dv['duration'],
                    'resource_size'=>$dv['resource_size'],'res_id'=>$dv['resource_id']);
                $addr_info = pathinfo($forscreen_url);
                $pubdetail['filename'] = $addr_info['basename'];
                $pubdetails[]=$pubdetail;
            }
            if($v['media_id']>0){
                $res_media = $m_media->getMediaInfoById($v['media_id']);
                $cover_img_url = $res_media['oss_addr']."?x-oss-process=image/quality,Q_50";
                $pubdetails[0]['img_url'] = $cover_img_url;
            }
            $dinfo['pubdetail'] = $pubdetails;

            $rets = $m_public->getFindnums($openid,$dinfo['forscreen_id'],2);
            $dinfo['is_collect'] = $rets['is_collect'];
            $dinfo['collect_num'] = $rets['collect_num'];
            $dinfo['share_num']  = $rets['share_num'];
            $datalist[] = $dinfo;
        }
        if(!empty($box_mac)){
            $m_box = new \Common\Model\BoxModel();
            $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
            $fields = "box.id as box_id,hotel.id as hotel_id,hotel.area_id";
            $box_info = $m_box->getBoxByCondition($fields,$where);
            $box_id = $box_info[0]['box_id'];
            $area_id = $box_info[0]['area_id'];
            $hotel_id = $box_info[0]['hotel_id'];

            $redis = new \Common\Lib\SavorRedis();
            $redis->select(14);
            $cache_key = 'box:play:'.$box_mac;
            $res_boxplays = $redis->get($cache_key);
            if(!empty($res_boxplays)){
                $host_name = C('HOST_NAME');
                $m_store = new \Common\Model\Smallapp\StoreModel();
                $box_resources = json_decode($res_boxplays,true);
                $user_datalist = array();
                if(!empty($box_resources['hotplay'])){
                    $play_ids = array_unique($box_resources['hotplay']);
                    foreach ($datalist as $v){
                        if(in_array($v['res_id'],$play_ids)){
                            $v['type'] = 1;
                            $v['title'] = '';
                            $v['qrcode_url'] = '';
                            $user_datalist[]=$v;
                        }
                    }
                }
                $datalist = $user_datalist;
            }
            $m_datadisplay = new \Common\Model\Smallapp\DatadisplayModel();
            $m_datadisplay->recordDisplaynum($datalist,$area_id,2);
        }
        $total = count($datalist);
        $data = array('total'=>$total,'datalist'=>$datalist);
        $this->to_back($data);
    }

    public function programlist(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $pagesize = !empty($this->params['pagesize'])?intval($this->params['pagesize']):10;
        $box_mac = $this->params['box_mac'];

        $oss_host = get_oss_host();
        $default_avatar = $oss_host.'media/resource/btCfRRhHkn.jpg';
        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $m_public = new \Common\Model\Smallapp\PublicModel();
        $m_user = new \Common\Model\Smallapp\UserModel();
        $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
        $m_ads = new \Common\Model\AdsModel();
        $m_media = new \Common\Model\MediaModel();
        $m_box = new \Common\Model\BoxModel();
        $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
        $fields = "box.id as box_id,hotel.id as hotel_id,hotel.area_id";
        $box_info = $m_box->getBoxByCondition($fields,$where);
        $box_id = $box_info[0]['box_id'];
        $area_id = $box_info[0]['area_id'];
        $hotel_id = $box_info[0]['hotel_id'];

        $redis = new \Common\Lib\SavorRedis();
        $redis->select(14);
        $cache_key = 'box:play:'.$box_mac;
        $res_boxplays = $redis->get($cache_key);
        $total = 0;
        $datalist = array();
        if(!empty($res_boxplays)){
            $host_name = C('HOST_NAME');
            $m_store = new \Common\Model\Smallapp\StoreModel();
            $box_resources = json_decode($res_boxplays,true);

            if(!empty($box_resources['list'])){
                $tmp_pro_ids = array();
                $tmp_life_ids = array();
                $exclude_videos = C('EXCLUDE_VIDEOS');
                foreach ($box_resources['list'] as $v){
                    if($v['type']=='pro' && !in_array($v['media_id'],$exclude_videos)){
                        $tmp_pro_ids[$v['media_id']] = $v['media_id'];
                    }
                    if($v['type']=='life'){
                        $tmp_life_ids[$v['media_id']] = $v['media_id'];
                    }
                }
                $m_program_list =  new \Common\Model\ProgramMenuListModel();
                $where  = array('menu_num'=>$box_resources['menu_num']);
                $order = "id desc";
                $program_info = $m_program_list->getInfo('id', $where, $order);
                $res_pro_ads = array();
                if(!empty($tmp_pro_ids)){
                    $fields = 'ads.id as ads_id,ads.name title,ads.type as ads_type,ads.description as content,ads.img_url,ads.portraitmedia_id,ads.duration,ads.create_time,media.id as media_id,media.type as media_type,media.oss_addr,media.oss_filesize as resource_size';
                    $where = array('a.menu_id'=>$program_info['id'],'a.type'=>2);
                    $where['media.id']  = array('in',array_values($tmp_pro_ids));
                    $order = 'a.sort_num asc';
                    $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
                    $res_pro_ads = $m_program_menu_item->getList($fields,$where,$order,"0,500",'media.id');
                }
                if(!empty($tmp_life_ids)){
                    $now_date = date('Y-m-d H:i:s');
                    $m_life_ads_hotel = new \Common\Model\Smallapp\LifeAdsHotelModel();
                    $where = array('a.hotel_id'=>$hotel_id,'lads.state'=>1);
                    $where['lads.start_date'] = array('ELT',$now_date);
                    $where['lads.end_date'] = array('EGT',$now_date);
                    $where['media.id']  = array('in',array_values($tmp_life_ids));
                    $res_life_ads = $m_life_ads_hotel->getList($fields,$where,'lads.id desc','0,2');
                    $all_ads = array_merge($res_life_ads,$res_pro_ads);
                }else{
                    $all_ads = $res_pro_ads;
                }
                $total = count($all_ads);
                $offset = ($page-1)*$pagesize;
                $all_page_ads = array_slice($all_ads,$offset,$pagesize);

                $hot_cache_key = C('SAPP_HOTPLAY_PRONUM');
                $redis->select(5);
                $all_pro_nums = $redis->get($hot_cache_key);
                $all_pro_nums = json_decode($all_pro_nums,true);
                foreach($all_page_ads as $v){
                    if($v['media_type']==1){
                        $res_type = 2;
                    }else{
                        $res_type = 1;
                    }
                    $play_where = array('res_id'=>$v['ads_id'],'type'=>3);
                    $play_info = $m_play_log->getOne('nums',$play_where);
                    $play_nums = 0;
                    if(!empty($play_info)){
                        $play_nums = intval($play_info['nums']);
                    }
                    if($v['ads_type']!=8 && isset($all_pro_nums[$v['media_id']])){
                        $play_nums = $play_nums + $all_pro_nums[$v['media_id']];
                    }
                    if($play_nums>100000){
                        $play_nums = floor($play_nums/10000).'w';
                    }

                    $dinfo = array('ads_id'=>$v['ads_id'],'res_id'=>$v['media_id'],'play_nums'=>$play_nums,'forscreen_id'=>0,'res_type'=>$res_type,
                        'title'=>$v['title'],'nickName'=>'小热点','avatarUrl'=>$default_avatar,'res_nums'=>1,'is_show'=>1);
                    $res_url = $oss_host.$v['oss_addr'];
                    $forscreen_url = $v['oss_addr'];
                    $duration = intval($v['duration']);
                    $resource_size = $v['resource_size'];
                    $res_id = $v['media_id'];
                    if($v['portraitmedia_id']){
                        $res_media = $m_media->getMediaInfoById($v['portraitmedia_id']);
                        $res_url = $res_media['oss_addr'];
                    }
                    $pdetail = array('res_url'=>$res_url,'forscreen_url'=>$forscreen_url,'duration'=>$duration,
                        'resource_size'=>$resource_size,'res_id'=>$res_id);
                    $oss_info = pathinfo($forscreen_url);
                    $pdetail['filename'] = $oss_info['basename'];
                    if(!empty($v['img_url'])){
                        $img_url = $oss_host.$v['img_url'];
                    }else{
                        if($res_type==1){
                            $img_url = $res_url."?x-oss-process=image/quality,Q_50";
                        }else{
                            $img_url = $res_url.'?x-oss-process=video/snapshot,t_10000,f_jpg,w_450,m_fast';
                        }
                    }
                    $pdetail['img_url'] = $img_url;
                    $dinfo['pubdetail'] = array($pdetail);
                    $qrcode_url = '';
                    if($v['ads_type']==8){
                        $res_store = $m_store->getInfo(array('ads_id'=>$v['ads_id']));
                        if(!empty($res_store)){
                            $data_id = $res_store['id'];
                            $qrcode_url = $host_name."/Smallapp46/qrcode/getBoxQrcode?box_mac={$box_mac}&box_id={$box_id}&data_id={$data_id}&type=37";
                        }
                        $dinfo['type'] = 3;
                    }else{
                        $dinfo['type'] = 2;
                    }
                    $dinfo['qrcode_url'] = $qrcode_url;

                    //获取是否收藏、分享个数、收藏个数、获取播放次数
                    $rets = $m_public->getFindnums($openid,$v['ads_id'],3);
                    $dinfo['is_collect'] = $rets['is_collect'];
                    $dinfo['collect_num'] = $rets['collect_num'];
                    $dinfo['share_num']  = $rets['share_num'];

                    $datalist[]=$dinfo;
                }
                $m_datadisplay = new \Common\Model\Smallapp\DatadisplayModel();
                $m_datadisplay->recordDisplaynum($datalist,$area_id,3);
            }
        }
        $data = array('total'=>$total,'datalist'=>$datalist);
        $this->to_back($data);
    }

    public function adsinfo(){
        $openid = $this->params['openid'];
        $res_id = intval($this->params['res_id']);

        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
        $play_where = array('res_id'=>$res_id,'type'=>3);
        $res_play = $m_play_log->getOne('nums',$play_where,'id desc');
        $play_num = 0;
        if(!empty($res_play)){
            $play_num = intval($res_play['nums']);
        }
        $oss_host = get_oss_host();
        $avatarUrl = $oss_host."media/resource/btCfRRhHkn.jpg";
        $nickName = '小热点';
        $data = array('forscreen_id'=>$res_id,'forscreen_char'=>'','public_text'=>'','res_type'=>1,
            'res_nums'=>1,'is_pub_hotelinfo'=>0,'create_time'=>'','avatarUrl'=>$avatarUrl,'nickName'=>$nickName,
            'hotel_name'=>''
        );

        $m_ads = new \Common\Model\AdsModel();
        $field = 'a.id,a.name title,a.create_time,a.type as ads_type,a.duration,b.type as media_type,b.oss_addr,b.oss_filesize';
        $where = array('a.id'=>$res_id);
        $res_ads = $m_ads->getAdsList($field,$where,$order,'0,1');
        $ads_info = $res_ads[0];

        $now = time();
        $diff_time =  $now - strtotime($ads_info['create_time']);
        if($diff_time<=86400){
            $create_time = viewTimes(strtotime($ads_info['create_time']));
        }else{
            $create_time = '';
        }
        $oss_host = get_oss_host();
        $oss_path = $ads_info['oss_addr'];
        $oss_path_info = pathinfo($oss_path);
        $pubdetail_info = array('res_url'=>$oss_host.$oss_path,'forscreen_url'=>$oss_path,'duration'=>$ads_info['duration'],
            'resource_size'=>$ads_info['oss_filesize'],'filename'=>$oss_path_info['basename'],'res_id'=>$res_id
        );

        $map = array('openid'=>$openid,'res_id'=>$res_id,'status'=>1);
        $is_collect = $m_collect->countNum($map);
        if(empty($is_collect)){
            $data['is_collect'] = "0";
        }else {
            $data['is_collect'] = "1";
        }
        //收藏个数
        $map = array('res_id'=>$res_id,'type'=>5,'status'=>1);
        $collect_num = $m_collect->countNum($map);
        //分享个数
        $map = array('res_id'=>$res_id,'type'=>5,'status'=>1);
        $share_num = $m_share->countNum($map);

        $data['create_time'] = $create_time;
        $data['collect_num'] = $collect_num;
        $data['share_num']   = $share_num;
        $data['play_num']    = $play_num;
        $data['pubdetail']   = array($pubdetail_info);
        $this->to_back($data);
    }


    public function addFormid(){
        $openid = $this->params['openid'];
        $formid = $this->params['formid'];
        $key = C('SAPP_FORMID').$openid;
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $res_cache = $redis->get($key);
        if(!empty($res_cache)){
            $res_data = json_decode($res_cache,true);
            $res_data[$formid] = time();
        }else{
            $res_data = array($formid=>time());
        }
        $redis->set($key,json_encode($res_data),86400*8);
        $res = array();
        $this->to_back($res);
    }

    public function guidePrompt(){
        $openid = $this->params['openid'];
        $type = $this->params['type'];
        $key = C('SAPP_GUIDE_PROMPT').$openid;
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $res_cache = $redis->get($key);
        if(!empty($res_cache)){
            $res_data = json_decode($res_cache,true);
            $res_data[$type] = array('is_prompt'=>1,'add_time'=>date('Y-m-d H:i:s'));
        }else{
            $res_data = array($type=>array('is_prompt'=>1,'add_time'=>date('Y-m-d H:i:s')));
        }
        $redis->set($key,json_encode($res_data));
        $res = array();
        $this->to_back($res);
    }

    private function init_hoteldata(){
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(5);
        $initdata_key = C('SAPP_INITDATA');
        $hotel_key = $initdata_key.'hotel';
        $res_hotels = $redis->get($hotel_key);
        if(!empty($res_hotels)){
            $hotel_list = json_decode($res_hotels,true);
        }else{
            $page = 1;
            $pagesize = 10;
            $offset = $page * $pagesize;
            $oss_host = get_oss_host();
            $hotel_box_type_arr = C('HEART_HOTEL_BOX_TYPE');
            $hotel_box_type_arr = array_keys($hotel_box_type_arr);
            $m_hotel = new \Common\Model\HotelModel();
            $fields = "a.id hotel_id,a.name,a.addr,a.tel,
                   b.avg_expense,concat('".$oss_host."',c.`oss_addr`) as img_url,
                   d.name food_name";
            $where = array('a.area_id'=>1);
            $where['a.state'] = 1;
            $where['a.flag']  = 0;
            $where['a.hotel_box_type'] = array('in',$hotel_box_type_arr);
            $where['a.id'] = array('not in','7,482,504,791,508,844,845,597,201,493,883');
            $order = " a.id asc";
            $limit = " 0 ,".$offset;
            $hotel_list = $m_hotel->alias('a')
                ->join('savor_hotel_ext b on a.id=b.hotel_id','left')
                ->join('savor_media c on b.hotel_cover_media_id=c.id','left')
                ->join('savor_hotel_food_style d on b.food_style_id=d.id','left')
                ->field($fields)
                ->where($where)
                ->order($order)
                ->limit($limit)
                ->select();
            foreach($hotel_list as $key=>$v){
                if(empty($v['food_name'])){
                    $hotel_list[$key]['food_name'] = '';
                }
                if(!empty($v['img_url'])){
                    $hotel_list[$key]['img_url'] = $v['img_url'].'?x-oss-process=image/resize,p_20';
                }else{
                    $hotel_list[$key]['img_url'] = '';
                }
            }
            if(!empty($hotel_list)){
                $redis->set($hotel_key,json_encode($hotel_list),86400*7);
            }
        }
        return $hotel_list;
    }

    private function init_goodsdata($openid){
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(5);
        $initdata_key = C('SAPP_INITDATA');
        $goods_key = $initdata_key."goods:$openid";
        $res_goods = $redis->get($goods_key);
        if(!empty($res_goods)){
            $goods_list = json_decode($res_goods,true);
        }else{
            $page = 1;
            $pagesize = 5;
            $all_nums = $page * $pagesize;
            $m_goods = new \Common\Model\Smallapp\GoodsModel();
            $fields = 'id,name,media_id,intro,label,cover_imgmedia_ids';
            $nowtime = date('Y-m-d H:i:s');
            $where = array('type'=>10,'status'=>2,'show_status'=>1);
            $where['start_time'] = array('elt',$nowtime);
            $where['end_time'] = array('egt',$nowtime);
            $orderby = 'id desc';
            $res_goods = $m_goods->getDataList($fields,$where,$orderby,0,$all_nums);
            $m_media = new \Common\Model\MediaModel();
            $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
            $m_public = new \Common\Model\Smallapp\PublicModel();
            $goods_list = array();
            foreach ($res_goods['list'] as $v){
                $media_id = $v['media_id'];
                $media_info = $m_media->getMediaInfoById($media_id);
                $oss_path = $media_info['oss_path'];
                $oss_path_info = pathinfo($oss_path);
                if($media_info['type']==2){
                    $img_url = $media_info['oss_addr']."?x-oss-process=image/quality,Q_50";
                }else{
                    $img_url = $media_info['oss_addr'].'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450,m_fast';
                }

                $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'img_url'=>$img_url,'duration'=>$media_info['duration'],'tx_url'=>$media_info['oss_addr'],
                    'filename'=>$oss_path_info['basename'],'forscreen_url'=>$oss_path,'resource_size'=>$media_info['oss_filesize'],
                    'intro'=>$v['intro'],'label'=>array());
                if(!empty($v['label'])){
                    $dlabel = json_decode($v['label'],true);
                    foreach ($dlabel as $dv){
                        if(!empty($dv)){
                            $dinfo['label'][]=$dv;
                        }
                    }
                }
                $cover_imgs = array();
                $cover_imgmedia_ids = $v['cover_imgmedia_ids'];
                if(!empty($cover_imgmedia_ids)){
                    $cover_imgmedia_ids = json_decode($cover_imgmedia_ids,true);
                    foreach ($cover_imgmedia_ids as $cv){
                        if(!empty($cv)){
                            $media_info = $m_media->getMediaInfoById($cv);
                            $cover_imgs[] = $media_info['oss_addr']."?x-oss-process=image/quality,Q_50";
                        }
                    }
                }
                $dinfo['cover_imgs'] = $cover_imgs;
                $rets = $m_public->getFindnums($openid,$v['id'],4);
                $dinfo['share_num']  = $rets['share_num'];
                $resplay_nums = $m_play_log->getOne('nums',array('res_id'=>$v['id'],'type'=>5),'id desc');
                $dinfo['play_num'] = intval($resplay_nums['nums']);
                $goods_list[] = $dinfo;
            }
            $redis->set($goods_key,json_encode($goods_list),3600*6);
        }
        return $goods_list;
    }

    private function init_userdata($openid){
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(5);
        $initdata_key = C('SAPP_INITDATA');
        $user_key = $initdata_key."user:$openid";
        $res_user = $redis->get($user_key);
        if(!empty($res_user)){
            $user_data = json_decode($res_user,true);
        }else{
            //获取我的公开
            $page_size = 3;
            $limit = "limit 0,".$page_size;
            $fields = 'a.forscreen_id,a.res_type';
            $where = array();
            $where['a.openid']   = $openid;
            $where['box.flag']   = 0;
            $where['box.state']  = 1;
            $where['hotel.flag'] = 0;
            $where['hotel.state']= 1;
            $where['a.status']   = 2;
            $order = "a.create_time desc";
            $m_public = new \Common\Model\Smallapp\PublicModel();
            $public_list = $m_public->getList($fields, $where, $order, $limit);
            if(!empty($public_list)){
                $m_pubdetail = new \Common\Model\Smallapp\PubdetailModel();
                $oss_host = get_oss_host();
                foreach($public_list as $key=>$v){
                    $fields = "concat('".$oss_host."',`res_url`) res_url,duration";
                    $where = array();
                    $where['forscreen_id'] = $v['forscreen_id'];
                    $pubdetail_info = $m_pubdetail->getWhere($fields, $where);
                    if($v['res_type']==2){
                        $pubdetail_info[0]['vide_img'] = $pubdetail_info[0]['res_url']."?x-oss-process=video/snapshot,t_3000,f_jpg,w_220,m_fast";
                        $pubdetail_info[0]['duration'] = secToMinSec(intval($pubdetail_info[0]['duration']));
                    }else {
                        $pubdetail_info[0]['vide_img'] = $pubdetail_info[0]['res_url'];
                    }
                    $public_list[$key]['res_url'] = $pubdetail_info[0]['res_url'];
                    $public_list[$key]['imgurl']  = $pubdetail_info[0]['vide_img'] ;
                }
            }else{
                $public_list = array();
            }

            //我的收藏
            $m_collect = new \Common\Model\Smallapp\CollectModel();
            $limit = "limit 0,".$page_size;
            $fields = "a.res_id,a.type,b.res_type";
            $where = array();
            $where['a.openid'] = $openid;
            $where['a.status'] = 1;
            $where['a.type'] = array('in',array(1,2,3));
            $order="a.create_time desc";
            $collect_info = $m_collect->getList($fields, $where, $order, $limit);
            if(!empty($collect_info)){
                $m_content  = new \Common\Model\ContentModel();
                $m_pubdetail= new \Common\Model\Smallapp\PubdetailModel();
                $m_public      = new \Common\Model\Smallapp\PublicModel();
                $m_ads = new \Common\Model\AdsModel();
                $oss_host = get_oss_host();
                foreach($collect_info as $key=>$v){
                    switch ($v['type']){
                        case 1://点播
                            $collect_info[$key]['res_type'] = 2;
                            $info = $m_content->field("`title`,`tx_url` res_url, concat('".$oss_host."',`img_url`) imgurl, '2' as  res_type , '1' as res_nums")
                                ->where(array('id'=>$v['res_id']))
                                ->find();
                            $res_url = strstr($info['res_url'], '?',-1);
                            $collect_info[$key]['res_nums'] = 1;
                            $info['res_url'] = $res_url;
                            $collect_info[$key]['res_url'] = $info['res_url'];
                            $collect_info[$key]['imgurl']  = $info['imgurl'];
                            break;
                        case 2://投屏
                            $pub_info = $m_public->getOne('res_type,res_nums', array('forscreen_id'=>$v['res_id'],'status'=>2));
                            $collect_info[$key]['res_type'] = $pub_info['res_type'];
                            $collect_info[$key]['res_nums'] = $pub_info['res_nums'];
                            if(!empty($pub_info)){
                                $fields = "resource_id,concat('".$oss_host."',`res_url`) res_url";
                                $pubdetails = $m_pubdetail->getWhere($fields, array('forscreen_id'=>$v['res_id']));
                                if($v['res_type']==2){
                                    $pubdetails[0]['imgurl'] = $pubdetails['0']['res_url'].'?x-oss-process=video/snapshot,t_3000,f_jpg,w_220,m_fast';
                                    $collect_info[$key]['res_url'] = $pubdetails[0]['res_url'];
                                    $collect_info[$key]['imgurl']  = $pubdetails[0]['imgurl'];
                                }else {
                                    $collect_info[$key]['res_url'] = $pubdetails[0]['res_url'];
                                    $collect_info[$key]['imgurl']  = $pubdetails[0]['res_url'];
                                }
                            }
                            break;
                        case 3://节目单列表
                            $collect_info[$key]['res_type'] = 3;
                            $info = $m_ads->alias('a')
                                ->field("a.id,concat('".$oss_host."',a.img_url) imgurl,concat('".$oss_host."',`oss_addr`) res_url")
                                ->join('savor_media media on a.media_id=media.id')
                                ->where(array('a.id'=>$v['res_id']))
                                ->find();
                            $collect_info[$key]['res_nums'] = 1;
                            if(empty($info['imgurl'])){
                                $collect_info[$key]['imgurl'] = $info['res_url'] .'?x-oss-process=video/snapshot,t_3000,f_jpg,w_220,m_fast';
                            }else {
                                $collect_info[$key]['imgurl']  = $info['imgurl'];
                            }
                            $collect_info[$key]['res_url'] = $info['res_url'];
                            break;
                    }
                }
            }else{
                $collect_info = array();
            }
            $user_data = array('public_list'=>$public_list,'collect_list'=>$collect_info);
            $redis->set($user_key,json_encode($user_data),3600*4);
        }
        return $user_data;
    }

    public function nearhotel(){
        $res_data = array('total'=>0,'datalist'=>array());
        $this->to_back($res_data);

        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res = $m_user->getOne('id',array('openid'=>$openid,'status'=>1),'id desc');
        if(empty($res)){
            $this->to_back(92010);
        }

        $m_box = new \Common\Model\BoxModel();
        $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
        $fields = "box.id as box_id,hotel.id as hotel_id,hotel.area_id,hotel.gps";
        $box_info = $m_box->getBoxByCondition($fields,$where);
        $gps = $box_info[0]['gps'];
        $hotel_id = $box_info[0]['hotel_id'];
        $cache_key = C('SAPP_NEARBYHOTEL').$hotel_id;
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(10);
        $res_cache = $redis->get($cache_key);
        $res_cache = '';
        if(!empty($res_cache)){
            $datalist = json_decode($res_cache,true);
        }else{
            $gps_arr = explode(',',$box_info[0]['gps']);
            $latitude = $gps_arr[1];
            $longitude = $gps_arr[0];
            $datalist = array();

            if(count($gps_arr)==2 && $longitude>0 && $latitude>0){
                $m_store = new \Common\Model\Smallapp\StoreModel();
                $res_store = $m_store->getHotelStore($box_info[0]['area_id']);

                $all_hotel = array();
                $oss_host = get_oss_host();
                foreach($res_store as $key=>$v){
                    if($v['hotel_id']==$box_info[0]['hotel_id']){
                        continue;
                    }
                    $v['dis'] = '';
                    if($v['gps']!=''){
                        $now_gps_arr = explode(',',$v['gps']);
                        $dis = geo_distance($latitude,$longitude,$now_gps_arr[1],$now_gps_arr[0]);
                        $v['dis_com'] = $dis;
                        if($dis>1000){
                            $tmp_dis = $dis/1000;
                            $dis = sprintf('%0.2f',$tmp_dis);
                            $dis = $dis.'km';
                        }else{
                            $dis = intval($dis);
                            $dis = $dis.'m';
                        }
                        $v['dis'] = $dis;
                    }else {
                        $v['dis'] = '';
                    }
                    $all_hotel[]=$v;
                }
                sortArrByOneField($all_hotel,'dis_com');
                $near_hotels = array_slice($all_hotel,0,3);

                $m_meida = new \Common\Model\MediaModel();
                foreach ($near_hotels as $k=>$v){
                    $tag_name = $v['tag_name'];
                    if(empty($tag_name)){
                        $tag_name = '';
                    }
                    if($v['media_id']){
                        $res_media = $m_meida->getMediaInfoById($v['media_id'],'https');
                        $img_url = $res_media['oss_addr'].'?x-oss-process=image/resize,p_50';
                        $ori_img_url = $res_media['oss_addr'];
                    }else{
                        $img_url = $oss_host.'media/resource/kS3MPQBs7Y.png';
                        $ori_img_url = $img_url;
                    }
                    $dis = $v['dis'];
                    if(empty($dis)){
                        $dis = '';
                    }
                    $tel = $v['tel'];
                    if(empty($tel)){
                        $tel = $v['mobile'];
                    }
                    $datalist[]=array('hotel_id'=>$v['hotel_id'],'name'=>$v['name'],'addr'=>$v['addr'],'tel'=>$tel,'avg_expense'=>$v['avg_expense'],
                        'dis'=>$dis,'tag_name'=>$tag_name,'img_url'=>$img_url,'ori_img_url'=>$ori_img_url
                    );
                }
                $redis->set($cache_key,json_encode($datalist),3600*5);
            }
        }
        $total = count($datalist);
        $res_data = array('total'=>$total,'datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function feedback(){
        $openid = $this->params['openid'];
        $contact = $this->params['contact'];
        $mobile = $this->params['mobile'];
        $content = $this->params['content'];
        $mobile_brand = $this->params['mobile_brand'];
        $mobile_model = $this->params['mobile_model'];

        if(!empty($mobile)){
            if(!check_mobile($mobile)){//验证手机格式
                $this->to_back(92001);
            }
        }
        $ip = get_client_ip();
        $data = array('openid'=>$openid,'content'=>$content,'ip'=>$ip);
        if(!empty($contact))        $data['contact'] = $contact;
        if(!empty($content))        $data['content'] = $content;
        if(!empty($mobile_brand))    $data['mobile_brand'] = $mobile_brand;
        if(!empty($mobile_model))    $data['mobile_model'] = $mobile_model;
        $m_feedback = new \Common\Model\Smallapp\FeedbackModel();
        $m_feedback->add($data);
        $this->to_back(array());
    }

    public function hotdrinks(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res = $m_user->getOne('id',array('openid'=>$openid,'status'=>1),'id desc');
        if(empty($res)){
            $this->to_back(92010);
        }

        $m_box = new \Common\Model\BoxModel();
        $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
        $fields = "box.id as box_id,hotel.id as hotel_id,hotel.area_id";
        $box_info = $m_box->getBoxByCondition($fields,$where);
        $hotel_id = $box_info[0]['hotel_id'];

        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $fields = 'count(g.id) as num';
        $where = array('h.hotel_id'=>$hotel_id,'g.type'=>43,'g.status'=>1);
        $res_goods = $m_hotelgoods->getGoodsList($fields,$where,'g.id desc','0,1');
        $datalist = array();
        if($res_goods[0]['num']){
            $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
            $where = array('status'=>1,'type'=>44);
            $orderby = 'id desc';
            $fields = 'id,name,price,cover_imgs,type';
            $res_goods = $m_goods->getDataList($fields,$where,$orderby,0,5);
            if(!empty($res_goods['list'])){
                $oss_host = get_oss_host();
                foreach ($res_goods['list'] as $v){
                    $img_url = '';
                    if(!empty($v['cover_imgs'])){
                        $cover_imgs_info = explode(',',$v['cover_imgs']);
                        if(!empty($cover_imgs_info[0])){
                            $img_url = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                        }
                    }
                    $price = intval($v['price']);
                    $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'price'=>$price,'img_url'=>$img_url,'type'=>$v['type']);
                    $datalist[]=$dinfo;
                }
            }
        }
        $this->to_back($datalist);
    }


}