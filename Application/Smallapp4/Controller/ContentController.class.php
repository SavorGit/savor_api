<?php
namespace Smallapp4\Controller;
use \Common\Controller\CommonController as CommonController;
class ContentController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotplaylist':
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001,'pagesize'=>1002);
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

    public function getHotplaylist(){
        $page = intval($this->params['page']);
        $pagesize = !empty($this->params['pagesize'])?intval($this->params['pagesize']):6;
        $all_nums = $page * $pagesize;
        $m_playlog = new \Common\Model\Smallapp\PlayLogModel();
        $where = array('type'=>4);

        $orderby = 'nums desc';
        $limit = "0,$all_nums";
        $fields = 'res_id as forscreen_id,nums as play_nums';
        $res_play = $m_playlog->getWhere($fields,$where,$orderby,$limit,'');

        $datalist = array();
        $oss_host = 'http://'.C('OSS_HOST').'/';
        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $m_user = new \Common\Model\Smallapp\UserModel();

        foreach ($res_play as $v){
            $res_forscreen = $m_forscreen->getInfo(array('id'=>$v['forscreen_id']));
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
                    $img_url = $res_url;
                }else{
                    $img_url = $oss_host.$forscreen_url.'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450';
                }
                $pubdetail = array('res_url'=>$res_url,'img_url'=>$img_url,'forscreen_url'=>$forscreen_url,'duration'=>$dv['duration'],
                    'resource_size'=>$dv['resource_size'],'res_id'=>$dv['resource_id']);
                $addr_info = pathinfo($forscreen_url);
                $pubdetail['filename'] = $addr_info['basename'];
                $pubdetails[]=$pubdetail;
            }
            $v['pubdetail'] = $pubdetails;
            $datalist[] = $v;
        }
        $data = array('datalist'=>$datalist);
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
//        $fields = "a.id hotel_id,a.media_id,a.name,a.addr,a.tel,b.food_style_id,
//                   b.avg_expense,concat('".$oss_host."',c.`oss_addr`) as img_url,
//                   d.name food_name";
            $fields = "a.id hotel_id,a.name,a.addr,a.tel,
                   b.avg_expense,concat('".$oss_host."',c.`oss_addr`) as img_url,
                   d.name food_name";
            $where = array('a.area_id'=>1);
            $where['a.state'] = 1;
            $where['a.flag']  = 0;
            $where['a.hotel_box_type'] = array('in',$hotel_box_type_arr);
            $where['a.id'] = array('not in','7,482,504,791,508,844,845,597');
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
            $pagesize = 10;
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
                    $img_url = $media_info['oss_addr'];
                }else{
                    $img_url = $media_info['oss_addr'].'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450';
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
                            $cover_imgs[] = $media_info['oss_addr'];
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
            $page_size = 6;
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

}