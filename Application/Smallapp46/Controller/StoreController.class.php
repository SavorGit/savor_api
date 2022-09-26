<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
class StoreController extends CommonController{

    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'dataList':
                $this->is_verify =1;
                $this->valid_fields = array('page'=>1001,'area_id'=>1001,'country_id'=>1002,
                    'latitude'=>1002,'longitude'=>1002,'cate_id'=>1001,
                    'food_style_id'=>1002,'avg_exp_id'=>1002
                );
                break;
            case 'detail':
                $this->is_verify =1;
                $this->valid_fields = array('store_id'=>1001,'openid'=>1001,'box_mac'=>1002);
                break;
            case 'collectList':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001);
                break;

        }
        parent::_init_();
    }
    /**
     * @desc 店铺列表
     */
    public function dataList(){
        $page     = $this->params['page'] ? $this->params['page'] :1;
        $area_id  = $this->params['area_id'] ? $this->params['area_id'] :1;
        $county_id = $this->params['county_id'];
        $cate_id = intval($this->params['cate_id']);
        $food_style_id = $this->params['food_style_id'];
        $avg_id   = $this->params['avg_exp_id'];
        $latitude = $this->params['latitude'];
        $longitude = $this->params['longitude'];
        $pagesize = 10;

        $m_store = new \Common\Model\Smallapp\StoreModel();
        if($cate_id==0 || $cate_id==120){
            $res_store = $m_store->getHotelStore($area_id,$county_id,$food_style_id,$avg_id);
        }else{
            $res_store = $m_store->getLifeStore($area_id,$county_id,$cate_id,$avg_id);
        }
        if($longitude>0 && $latitude>0){
            $bd_lnglat = getgeoByTc($latitude, $longitude);
            foreach($res_store as $key=>$v){
                $res_store[$key]['dis'] = '';
                if($v['gps']!='' && $longitude>0 && $latitude>0){
                    $latitude = $bd_lnglat[0]['y'];
                    $longitude = $bd_lnglat[0]['x'];

                    $gps_arr = explode(',',$v['gps']);
                    $dis = geo_distance($latitude,$longitude,$gps_arr[1],$gps_arr[0]);
                    $res_store[$key]['dis_com'] = $dis;
                    if($dis>1000){
                        $tmp_dis = $dis/1000;
                        $dis = sprintf('%0.2f',$tmp_dis);
                        $dis = $dis.'km';
                    }else{
                        $dis = intval($dis);
                        $dis = $dis.'m';
                    }
                    $res_store[$key]['dis'] = $dis;
                }else {
                    $res_store[$key]['dis'] = '';
                }
            }
            sortArrByOneField($res_store,'dis_com');
        }
        if($cate_id==0){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(9);
            $cache_key = C('FINANCE_HOTELSTOCK');
            $result  = $redis->get($cache_key);
            $hotel_arr = json_decode($result,true);
            $stock_hotel = array();
            $other_hotel = array();
            foreach ($res_store as $k=>$v){
                if(!empty($hotel_arr[$v['hotel_id']])){
                    $stock_hotel[]=$v;
                }else{
                    $other_hotel[]=$v;
                }
            }
            $res_store = array_merge($stock_hotel,$other_hotel);
        }

        $offset = $page * $pagesize;
        $hotel_list = array_slice($res_store,0,$offset);
        $m_meida = new \Common\Model\MediaModel();
        $datalist = array();
        $oss_host = get_oss_host();
        foreach ($hotel_list as $k=>$v){
            $tag_name = $v['tag_name'];
            if(empty($tag_name)){
                $tag_name = '';
            }
            if($v['media_id']){
                $res_media = $m_meida->getMediaInfoById($v['media_id']);
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
            $is_detail = 0;
            if($v['hotel_id']>=10000){
                $res_store_data = $m_store->getInfo(array('id'=>$v['hotel_id']));
                if(!empty($res_store_data['ads_id']) && !empty($res_store_data['detail_imgs'])){
                    $is_detail = 1;
                }
            }
            $datalist[]=array('hotel_id'=>$v['hotel_id'],'name'=>$v['name'],'addr'=>$v['addr'],'tel'=>$tel,'avg_expense'=>$v['avg_expense'],
                'dis'=>$dis,'tag_name'=>$tag_name,'img_url'=>$img_url,'ori_img_url'=>$ori_img_url,'is_detail'=>$is_detail
            );

        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function detail(){
        $store_id = intval($this->params['store_id']);
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];

        $m_store = new \Common\Model\Smallapp\StoreModel();
        $res_store = $m_store->getInfo(array('id'=>$store_id));
        $tel = $res_store['tel'];
        if(empty($tel)){
            $tel = $res_store['mobile'];
        }
        $oss_host = get_oss_host();
        $detail_imgs =array();
        if(!empty($res_store['detail_imgs'])){
            $detail_imgs_info = explode(',',$res_store['detail_imgs']);
            if(!empty($detail_imgs_info)){
                foreach ($detail_imgs_info as $v){
                    if(!empty($v)){
                        $img_url = $oss_host.$v."?x-oss-process=image/quality,Q_60";
                        $detail_imgs[] = $img_url;
                    }
                }
            }
        }
        $m_media = new \Common\Model\MediaModel();
        $coupon_img = '';
        if(!empty($res_store['coupon_media_id'])){
            $res_coupon = $m_media->getMediaInfoById($res_store['coupon_media_id']);
            $coupon_img = $res_coupon['oss_addr'];
        }
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $where = array('openid'=>$openid,'res_id'=>$store_id,'type'=>6,'status'=>1);
        $res_collect = $m_collect->getOne('*', $where);
        $is_collect = 0;
        if(!empty($res_collect)){
            $is_collect = 1;
        }
        $data = array('id'=>$store_id,'name'=>$res_store['name'],'addr'=>$res_store['addr'],'tel'=>$tel,'is_collect'=>$is_collect,
            'detail_imgs'=>$detail_imgs, 'coupon_img'=>$coupon_img,
        );
        $m_ads = new \Common\Model\AdsModel();
        $res_ads = $m_ads->getWhere(array('id'=>$res_store['ads_id']), "*");
        $video_img_url = '';
        if(!empty($res_ads)){
            $video_img_url = $oss_host.$res_ads[0]['img_url'];
        }
        $qrcode_url = '';
        if(!empty($box_mac)){
            $m_box = new \Common\Model\BoxModel();
            $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
            $fields = "box.id as box_id,hotel.id as hotel_id";
            $box_info = $m_box->getBoxByCondition($fields,$where);
            $box_id = $box_info[0]['box_id'];
            $host_name = C('HOST_NAME');
            $qrcode_url = $host_name."/Smallapp46/qrcode/getBoxQrcode?box_mac={$box_mac}&box_id={$box_id}&data_id={$store_id}&type=37";
        }

        $data['qrcode_url'] = $qrcode_url;
        $data['video_img_url'] = $video_img_url;
        $media_info = $m_media->getMediaInfoById($res_ads[0]['media_id']);
        $oss_path = $media_info['oss_path'];
        $oss_path_info = pathinfo($oss_path);
        $data['ads_id'] = $res_store['ads_id'];
        $data['duration'] = $media_info['duration'];
        $data['tx_url'] = $media_info['oss_addr'];
        $data['filename'] = $oss_path_info['basename'];
        $data['forscreen_url'] = $oss_path;
        $data['resource_size'] = $media_info['oss_filesize'];
        $this->to_back($data);
    }

    public function collectList(){
        $openid = $this->params['openid'];
        $page = $this->params['page'];
        $pagesize = 10;

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $offset = $page * $pagesize;
        $limit = "0,$offset";
        $field = 'store.id as store_id,store.cover_media_id as media_id,store.name,store.addr,store.tel,store.mobile,store.gps,store.avg_expense,category.name as tag_name,store.category_id as cate_id';
        $where = array('a.openid'=>$openid,'a.type'=>6,'a.status'=>1,'store.status'=>1);
        $res_collect = $m_collect->getStore($field,$where,'a.id desc',$limit);
        $datalist = array();
        $total = 0;
        if(!empty($res_collect)){
            $oss_host = get_oss_host();
            $m_meida = new \Common\Model\MediaModel();
            foreach ($res_collect as $v){
                $tag_name = $v['tag_name'];
                if(empty($tag_name)){
                    $tag_name = '';
                }
                if($v['media_id']){
                    $res_media = $m_meida->getMediaInfoById($v['media_id']);
                    $img_url = $res_media['oss_addr'].'?x-oss-process=image/resize,p_20';
                    $ori_img_url = $res_media['oss_addr'];
                }else{
                    $img_url = $oss_host.'media/resource/kS3MPQBs7Y.png';
                    $ori_img_url = $img_url;
                }
                $tel = $v['tel'];
                if(empty($tel)){
                    $tel = $v['mobile'];
                }
                $datalist[]=array('store_id'=>$v['store_id'],'name'=>$v['name'],'addr'=>$v['addr'],'tel'=>$tel,'avg_expense'=>$v['avg_expense'],
                    'tag_name'=>$tag_name,'img_url'=>$img_url,'ori_img_url'=>$ori_img_url
                );
            }
            $total = count($datalist);
        }
        $res_data = array('total'=>$total,'datalist'=>$datalist);
        $this->to_back($res_data);
    }


}