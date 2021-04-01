<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
class ContentController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotplaylist':
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001,'pagesize'=>1002,'box_mac'=>1002);
                break;
            case 'hotplay':
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001,'pagesize'=>1002,'box_mac'=>1002);
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

    public function hotplay(){
        $page = intval($this->params['page']);
        $pagesize = !empty($this->params['pagesize'])?intval($this->params['pagesize']):6;
        $box_mac = $this->params['box_mac'];
        $total_num = 10;
        $all_hot_nums = 8;
        $m_playlog = new \Common\Model\Smallapp\PlayLogModel();
        $where = array('type'=>4);

        $orderby = 'nums desc';
        $limit = "0,$all_hot_nums";
        $fields = 'res_id,nums as play_nums';
        $res_play = $m_playlog->getWhere($fields,$where,$orderby,$limit,'');

        $datalist = array();
        $oss_host = 'http://'.C('OSS_HOST').'/';
        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $m_user = new \Common\Model\Smallapp\UserModel();

        foreach ($res_play as $v){
            if($v['play_nums']>100000){
                $v['play_nums'] = floor($v['play_nums']/10000).'w';
            }

            $res_forscreen = $m_forscreen->getInfo(array('id'=>$v['res_id']));
            $v['forscreen_id'] = $res_forscreen['forscreen_id'];
            $v['res_type'] = $res_forscreen['resource_type'];
            $where = array('openid'=>$res_forscreen['openid']);
            $fields = 'id user_id,avatarUrl,nickName';
            $res_user = $m_user->getOne($fields, $where);
            $v['nickName'] = $res_user['nickName'];
            $v['avatarUrl'] = $res_user['avatarUrl'];
            $fields_forscreen = 'imgs,duration,resource_size,resource_id';
            $all_forscreen = $m_forscreen->getWheredata($fields_forscreen,array('forscreen_id'=>$res_forscreen['forscreen_id']),'id asc');
            $v['res_nums'] = count($all_forscreen);
            $pubdetails = array();
            foreach ($all_forscreen as $dv){
                $imgs_info = json_decode($dv['imgs'],true);
                $forscreen_url = $imgs_info[0];
                $res_url = $oss_host.$forscreen_url;
                if($v['res_type']==1){
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
            $v['is_show'] = 1;
            $v['pubdetail'] = $pubdetails;
            $v['type'] = 1;
            $v['title'] = '';
            $datalist[] = $v;
        }
        if(!empty($box_mac)){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(14);
            $cache_key = 'box:play:'.$box_mac;
            $res_boxplays = $redis->get($cache_key);
            if(!empty($res_boxplays)){
                $box_resources = json_decode($res_boxplays,true);
                $user_datalist = array();
                if(!empty($box_resources['hotplay'])){
                    $play_ids = array_unique($box_resources['hotplay']);
                    foreach ($datalist as $v){
                        if(in_array($v['res_id'],$play_ids)){
                            $v['type'] = 1;
                            $v['title'] = '';
                            $user_datalist[]=$v;
                        }
                    }
                }
                if(!empty($box_resources['list'])){
                    $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
                    $default_avatar = 'http://oss.littlehotspot.com/media/resource/btCfRRhHkn.jpg';
                    $tmp_pro_ids = array();
                    $tmp_life_ids = array();
                    foreach ($box_resources['list'] as $v){
                        if($v['type']=='pro'){
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
                    $fields = 'ads.id as ads_id,ads.name title,ads.type as ads_type,ads.description as content,ads.img_url,ads.portraitmedia_id,ads.duration,ads.create_time,media.id as media_id,media.type as media_type,media.oss_addr,media.oss_filesize as resource_size';
                    $where = array('a.menu_id'=>$program_info['id'],'a.type'=>2);
                    $where['media.id']  = array('in',array_values($tmp_pro_ids));
                    $order = 'a.sort_num asc';
                    $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
                    $res_pro_ads = $m_program_menu_item->getList($fields,$where,$order,"0,10",'media.id');

                    if(!empty($tmp_life_ids)){
                        $m_box = new \Common\Model\BoxModel();
                        $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
                        $box_fields = "box.id as box_id,hotel.id as hotel_id";
                        $box_info = $m_box->getBoxByCondition($box_fields,$where);
                        $hotel_id = $box_info[0]['hotel_id'];

                        $m_life_ads_hotel = new \Common\Model\Smallapp\LifeAdsHotelModel();
                        $where = array('a.hotel_id'=>$hotel_id,'lads.state'=>1);
                        $where['media.id']  = array('in',array_values($tmp_life_ids));
                        $res_life_ads = $m_life_ads_hotel->getList($fields,$where,'lads.id desc','0,2');
                        $all_ads = array_merge($res_pro_ads,$res_life_ads);
                    }else{
                        $all_ads = $res_pro_ads;
                    }
                    $m_media = new \Common\Model\MediaModel();
                    $pro_ads = $life_ads = array();
                    foreach($all_ads as $v){
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
                        if($v['ads_type']!=8 && $play_nums<10000){
                            $play_nums = $play_nums + rand(10000,100000);
                            $m_play_log->updateInfo(array('id'=>$play_info['id']),array('nums'=>$play_nums));
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
                        if($v['ads_type']==8){
                            $dinfo['type'] = 3;
                        }else{
                            $dinfo['type'] = 2;
                        }

                        if($v['ads_type']==2){
                            $pro_ads[]=$dinfo;
                        }else{
                            $life_ads[]=$dinfo;
                        }
                    }

                    shuffle($pro_ads);
                    $first_data = array_slice($pro_ads,0,1);
                    $last_data = array_slice($pro_ads,1,1);
                    $pro_index = 1;

                    $user_num = count($user_datalist);
                    $pro_num = count($pro_ads);
                    $life_num = count($life_ads);
                    if($user_num>6){
                        $user_datas = array_slice($user_datalist,0,6);
                        if($life_num>=2){
                            $life_datas = $life_ads;
                        }else{
                            $life_datas_user = array_slice($user_datalist,6,2-$life_num);
                            $life_datas = array_merge($life_ads,$life_datas_user);
                            $tmp_life_num = count($life_datas);
                            if($tmp_life_num<2){
                                $pro_index = 2;
                                $life_datas_pro = array_slice($pro_ads,$pro_index,2-$tmp_life_num);
                                $pro_index = $pro_index + (2-$tmp_life_num);
                            }
                        }
                    }else{
                        $pro_index = 2;
                        $user_datas_pro = array_slice($pro_ads,$pro_index,6-$user_num);
                        $pro_index = $pro_index + (6-$user_num);
                        $user_datas = array_merge($user_datalist,$user_datas_pro);
                        if($life_num>=2){
                            $life_datas = $life_ads;
                        }else{
                            $life_datas_pro = array_slice($pro_ads,$pro_index,2-$life_num);
                            $life_datas = array_merge($life_ads,$life_datas_pro);
                            $pro_index = $pro_index + (2-$life_num);
                        }
                    }
                    $datalist = array_merge($first_data,$user_datas,$life_datas,$last_data);
                }
            }
        }
        $data = array('datalist'=>$datalist);
        $this->to_back($data);
    }

    public function getHotplaylist(){
        $page = intval($this->params['page']);
        $pagesize = !empty($this->params['pagesize'])?intval($this->params['pagesize']):6;
        $box_mac = $this->params['box_mac'];
        $total_num = 10;
        $all_hot_nums = 8;
        $m_playlog = new \Common\Model\Smallapp\PlayLogModel();
        $where = array('type'=>4);

        $orderby = 'nums desc';
        $limit = "0,$all_hot_nums";
        $fields = 'res_id,nums as play_nums';
        $res_play = $m_playlog->getWhere($fields,$where,$orderby,$limit,'');

        $datalist = array();
        $oss_host = 'http://'.C('OSS_HOST').'/';
        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $m_user = new \Common\Model\Smallapp\UserModel();

        foreach ($res_play as $v){
            if($v['play_nums']>100000){
                $v['play_nums'] = floor($v['play_nums']/10000).'w';
            }

            $res_forscreen = $m_forscreen->getInfo(array('id'=>$v['res_id']));
            $v['forscreen_id'] = $res_forscreen['forscreen_id'];
            $v['res_type'] = $res_forscreen['resource_type'];
            $where = array('openid'=>$res_forscreen['openid']);
            $fields = 'id user_id,avatarUrl,nickName';
            $res_user = $m_user->getOne($fields, $where);
            $v['nickName'] = $res_user['nickName'];
            $v['avatarUrl'] = $res_user['avatarUrl'];
            $fields_forscreen = 'imgs,duration,resource_size,resource_id';
            $all_forscreen = $m_forscreen->getWheredata($fields_forscreen,array('forscreen_id'=>$res_forscreen['forscreen_id']),'id asc');
            $v['res_nums'] = count($all_forscreen);
            $pubdetails = array();
            foreach ($all_forscreen as $dv){
                $imgs_info = json_decode($dv['imgs'],true);
                $forscreen_url = $imgs_info[0];
                $res_url = $oss_host.$forscreen_url;
                if($v['res_type']==1){
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
            $v['is_show'] = 1;
            $v['pubdetail'] = $pubdetails;
            $v['type'] = 1;
            $v['title'] = '';
            $datalist[] = $v;
        }
        if(!empty($box_mac)){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(14);
            $cache_key = 'box:play:'.$box_mac;
            $res_boxplays = $redis->get($cache_key);
            if(!empty($res_boxplays)){
                $box_resources = json_decode($res_boxplays,true);
                $all_datalist = array();
                if(!empty($box_resources['hotplay'])){
                    $play_ids = array_unique($box_resources['hotplay']);
                    foreach ($datalist as $v){
                        if(in_array($v['res_id'],$play_ids)){
                            $v['type'] = 1;
                            $v['title'] = '';
                            $all_datalist[]=$v;
                        }
                    }
                }
                $last_num = $total_num - count($all_datalist);
                $tmp_pro_ids = array();
                if(!empty($box_resources['list'])){
                    $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
                    $default_avatar = 'http://oss.littlehotspot.com/media/resource/btCfRRhHkn.jpg';
                    foreach ($box_resources['list'] as $v){
                        if($v['type']=='pro'){
                            $tmp_pro_ids[$v['media_id']] = $v['media_id'];
                        }
                    }
                    $media_ids = array_values($tmp_pro_ids);

                    $m_program_list =  new \Common\Model\ProgramMenuListModel();
                    $where  = array('menu_num'=>$box_resources['menu_num']);
                    $order = "id desc";
                    $program_info = $m_program_list->getInfo('id', $where, $order);

                    $fields = 'ads.id as ads_id,ads.name title,ads.description as content,ads.img_url,ads.portraitmedia_id,ads.duration,ads.create_time,media.id as media_id,media.type as media_type,media.oss_addr,media.oss_filesize as resource_size';
                    $where = array('a.menu_id'=>$program_info['id'],'a.type'=>2);
                    $where['media.id']  = array('in',$media_ids);
                    $order = 'a.sort_num asc';
                    $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
                    $res_demand = $m_program_menu_item->getList($fields,$where,$order,"0,$last_num",'media.id');
                    $m_media = new \Common\Model\MediaModel();
                    foreach($res_demand as $v){
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
                            if($v['res_type']==1){
                                $img_url = $res_url."?x-oss-process=image/quality,Q_50";
                            }else{
                                $img_url = $res_url.'?x-oss-process=video/snapshot,t_10000,f_jpg,w_450,m_fast';
                            }
                        }
                        $pdetail['img_url'] = $img_url;
                        $dinfo['pubdetail'] = array($pdetail);
                        $dinfo['type'] = 2;
                        $all_datalist[] = $dinfo;
                    }
                }
                $datalist = $all_datalist;
            }
        }
        $data = array('datalist'=>$datalist);
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
        $avatarUrl = "http://oss.littlehotspot.com/media/resource/btCfRRhHkn.jpg";
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
        $oss_host = 'http://'.C('OSS_HOST').'/';
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

    private function getFindnums($openid,$res_id,$type){
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $map = array('openid'=>$openid,'res_id'=>$res_id,'type'=>$type,'status'=>1);
        $is_collect = $m_collect->countNum($map);
        if(empty($is_collect)){
            $is_collect = 0;
        }else {
            $is_collect = 1;
        }
        $map = array('res_id'=>$res_id,'type'=>$type,'status'=>1);
        $collect_num = $m_collect->countNum($map);

        //分享个数
        $map = array('res_id'=>$res_id,'type'=>$type,'status'=>1);
        $share_num = $m_share->countNum($map);

        $data = array('is_collect'=>$is_collect,'collect_num'=>$collect_num,'share_num'=>$share_num);
        return $data;
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
            $oss_host = 'http://'. C('OSS_HOST').'/';
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
                $rets = $this->getFindnums($openid,$v['id'],4);
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
                $oss_host = 'http://'. C('OSS_HOST').'/';
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
                $oss_host = 'http://'. C('OSS_HOST').'/';
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

}