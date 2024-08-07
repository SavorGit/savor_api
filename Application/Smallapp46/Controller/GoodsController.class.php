<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class GoodsController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getDetailByAttr':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'attr'=>1001);
                break;
            case 'hotdrinklist':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1002,'type'=>1001,'room_id'=>1002,'hotel_id'=>1002,
                    'page'=>1001,'pagesize'=>1002,'version'=>1002,'openid'=>1002);
                break;
            case 'distributionGoodsList':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1002,'page'=>1001);
                break;
            case 'topDistributionGoods':
                $this->is_verify = 2;
                $this->valid_fields = array('openid'=>1002);
                break;
        }
        parent::_init_();
    }

    public function getDetailByAttr(){
        $goods_id = intval($this->params['goods_id']);
        $attr = $this->params['attr'];

        $attr_arr = explode('_',$attr);
        $attrs = array();
        foreach ($attr_arr as $v){
            $attrs[]=intval($v);
        }

        $attr_ids = join('_',$attrs);
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res = $m_goods->getInfo(array('id'=>$goods_id));
        if(empty($res)){
            $this->to_back(93034);
        }
        $parent_goods_id = $res['parent_id'];
        $res_goods = $m_goods->getInfo(array('parent_id'=>$parent_goods_id,'attr_ids'=>$attr_ids,'status'=>1));
        if(empty($res_goods)){
            $m_goodsattr = new \Common\Model\Smallapp\GoodsattrModel();
            $res_goods = $m_goodsattr->getDataList('*',array('attr_id'=>$attrs[0],'status'=>1),'id asc',0,1);
            if($res_goods['total']){
                $res_goods = $m_goods->getInfo(array('id'=>$res_goods['list'][0]['goods_id']));
            }
        }

        $price = $res_goods['price'];
        $line_price = $res_goods['line_price'];
        $stock_num = $res_goods['amount'];
        $now_goods_id = $res_goods['id'];

        $attrs = array();
        $model_img = '';
        if($res_goods['gtype']==3){
            $res_attrs = $m_goods->getGoodsAttr($parent_goods_id,$res_goods['id']);
            $model_img = $res_attrs['default']['model_img']."?x-oss-process=image/resize,p_50/quality,q_80";
            $attrs = $res_attrs['attrs'];
            $res_goods = $m_goods->getInfo(array('id'=>$res_goods['parent_id']));
        }

        $data = array('goods_id'=>$now_goods_id,'id'=>$now_goods_id,'name'=>$res_goods['name'],'price'=>$price,'line_price'=>$line_price,
            'is_localsale'=>$res_goods['is_localsale'],'stock_num'=>$stock_num,'type'=>$res_goods['type'],
            'gtype'=>$res_goods['gtype'],'attrs'=>$attrs,'model_img'=>$model_img);
        $oss_host = "https://".C('OSS_HOST').'/';
        $cover_imgs = $detail_imgs =array();
        if(!empty($res_goods['cover_imgs'])){
            $cover_imgs_info = explode(',',$res_goods['cover_imgs']);
            if(!empty($cover_imgs_info)){
                foreach ($cover_imgs_info as $v){
                    if(!empty($v)){
                        $img_url = $oss_host.$v."?x-oss-process=image/resize,m_mfit,h_400,w_750";
                        $cover_imgs[] = $img_url;
                    }
                }
            }
        }
        if(!empty($res_goods['detail_imgs'])){
            $detail_imgs_info = explode(',',$res_goods['detail_imgs']);
            if(!empty($detail_imgs_info)){
                foreach ($detail_imgs_info as $v){
                    if(!empty($v)){
                        $img_url = $oss_host.$v."?x-oss-process=image/quality,Q_60";
                        $detail_imgs[] = $img_url;
                    }
                }
            }
        }
        $data['cover_imgs'] = $cover_imgs;
        $data['detail_imgs'] = $detail_imgs;
        $data['intro'] = $res_goods['intro'];
        $data['video_img'] = '';
        $data['video_url'] = '';
        $data['localsale_str'] = '';

        $merchant = array();
        $merchant['merchant_id'] = $res_goods['merchant_id'];
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $fields = 'hotel.name,hotel.mobile,hotel.tel,ext.hotel_cover_media_id,hotel.area_id,area.region_name,m.mtype';
        $res_merchantinfo = $m_merchant->getMerchantInfo($fields,array('m.id'=>$res_goods['merchant_id']));
        if($res_goods['type']==22){
            if($res_goods['is_localsale']){
                $data['localsale_str'] = '仅售'.$res_merchantinfo[0]['region_name'];
            }
            if(!empty($res_goods['video_intromedia_id'])){
                $m_media = new \Common\Model\MediaModel();
                $media_info = $m_media->getMediaInfoById($res_goods['video_intromedia_id']);
                $data['video_img'] = $media_info['oss_addr'].'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450,m_fast';
                $data['video_url'] = $media_info['oss_addr'];
            }
        }

        $merchant['name'] = $res_merchantinfo[0]['name'];
        $merchant['mobile'] = $res_merchantinfo[0]['mobile'];
        $merchant['tel'] = $res_merchantinfo[0]['tel'];
        $merchant['area_id'] = $res_merchantinfo[0]['area_id'];
        $merchant['mtype'] = $res_merchantinfo[0]['mtype'];
        $merchant['img'] = '';
        if(!empty($res_merchantinfo[0]['hotel_cover_media_id'])){
            $m_media = new \Common\Model\MediaModel();
            $res_media = $m_media->getMediaInfoById($res_merchantinfo[0]['hotel_cover_media_id'],'https');
            $merchant['img'] = $res_media['oss_addr'];
        }
        $where = array('merchant_id'=>$merchant['merchant_id'],'status'=>1);
        $merchant['num'] = $m_goods->countNum($where);
        $data['merchant'] = $merchant;

        $m_goodsactivity = new \Common\Model\Smallapp\GoodsactivityModel();
        $res_activity = $m_goodsactivity->getInfo(array('goods_id'=>$now_goods_id));
        $gift = array();
        if(!empty($res_activity)){
            $res_ginfo = $m_goods->getInfo(array('id'=>$res_activity['gift_goods_id']));
            $gift = array('id'=>$res_ginfo['id'],'name'=>$res_ginfo['name']);
        }
        if(empty($gift)){
            $gift = new \stdClass();
        }
        $data['gift'] = $gift;
        $this->to_back($data);
    }

    public function hotdrinklist(){
        $box_mac = $this->params['box_mac'];
        $type = $this->params['type'];//44团购商品,43本店有售商品
        $page = intval($this->params['page']);
        $room_id = intval($this->params['room_id']);
        $jump_hotel_id = intval($this->params['hotel_id']);
        $pagesize = $this->params['pagesize'];
        $version = $this->params['version'];
        $openid = $this->params['openid'];
        if(empty($pagesize)){
            $pagesize = 10;
        }
        $start = ($page-1)*$pagesize;

        $fields = "hotel.id as hotel_id,hotel.area_id";
        if(!empty($box_mac)){
            $m_box = new \Common\Model\BoxModel();
            $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
            $box_info = $m_box->getBoxByCondition($fields,$where);
            $hotel_id = $box_info[0]['hotel_id'];
        }elseif(!empty($jump_hotel_id)){
            $hotel_id = $jump_hotel_id;
        }else{
            $where = array('room.id'=>$room_id,'room.state'=>1,'room.flag'=>0);
            $m_room = new \Common\Model\RoomModel();
            $room_info = $m_room->getRoomByCondition($fields,$where);
            $hotel_id = $room_info[0]['hotel_id'];
        }

        if($type==44){
            $res_goods = array();
        }else{
            $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
            $res_data = $m_hotelgoods->getStockGoodsList($hotel_id,$start,$pagesize);
            $res_goods = array('list'=>$res_data);
        }
        $datalist = array();
        if($res_goods['list']){
            $oss_host = get_oss_host();
            $m_media = new \Common\Model\MediaModel();
            foreach ($res_goods['list'] as $v){
                $img_url = '';
                if(!empty($v['cover_imgs'])){
                    $cover_imgs_info = explode(',',$v['cover_imgs']);
                    if(!empty($cover_imgs_info[0])){
                        $img_url = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                    }
                }
                $wine_img_url = '';
                if(!empty($v['small_media_id'])){
                    $res_media = $m_media->getMediaInfoById($v['small_media_id']);
                    $wine_img_url = $res_media['oss_addr'];
                }
                $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'price'=>intval($v['price']),'line_price'=>intval($v['line_price']),'type'=>$v['type'],
                    'img_url'=>$img_url,'wine_img_url'=>$wine_img_url,'finance_goods_id'=>$v['finance_goods_id']);
                $datalist[] = $dinfo;
            }
        }
        if(!empty($version) && $version>='4.6.69'){
//            $m_sellwine_activity_hotel = new \Common\Model\Smallapp\SellwineActivityHotelModel();
//            $sellwine_activity = $m_sellwine_activity_hotel->getSellwineActivity($hotel_id,$openid,2);
//            foreach ($datalist as $k=>$v){
//                $message = '';
//                if(isset($sellwine_activity['goods_data'][$v['finance_goods_id']])){
//                    $money = intval($sellwine_activity['goods_data'][$v['finance_goods_id']]['money']);
//                    $message = "下单得{$money}元红包";
//                }
//                $datalist[$k]['message'] = $message;
//            }
            $datalist = array('datalist'=>$datalist);
        }
        $this->to_back($datalist);

    }

    public function distributionGoodsList(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $pagesize = $this->params['pagesize'];
        if(empty($pagesize)){
            $pagesize = 10;
        }
        $m_distuser = new \Common\Model\Smallapp\DistributionUserModel();
        $res_duser = $m_distuser->getInfo(array('openid'=>$openid,'status'=>1));
        $sale_uid = 0;
        $qrcode_url = '';
        $level = 0;
        if(!empty($res_duser)){
            $level = $res_duser['level'];
            $sale_uid = $res_duser['id'];
            $hash_ids_key = C('HASH_IDS_KEY');
            $hashids = new \Common\Lib\Hashids($hash_ids_key);
            $sale_uid = $hashids->encode($sale_uid);
            $host_name = 'https://'.$_SERVER['HTTP_HOST'];
            $qrcode_url = $host_name."/basedata/forscreenQrcode/getBoxQrcode?type=51&data_id={$res_duser['id']}&box_id=0";
        }

        $datalist = array();
        $start = ($page-1)*$pagesize;
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goods = $m_goods->getDataList('id,name,price,line_price,cover_imgs,desc,desc2,distribution_config',array('type'=>45,'status'=>1),'sort desc',$start,$pagesize);
        $oss_host = get_oss_host();
        if($res_goods['list']){
            foreach ($res_goods['list'] as $v){
                $img_url = '';
                if(!empty($v['cover_imgs'])){
                    $cover_imgs_info = explode(',',$v['cover_imgs']);
                    if(!empty($cover_imgs_info[0])){
                        $img_url = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                    }
                }
                if($level==2){
                    $v['desc'] = $v['desc2'];
                }
                $desc = '';
                if(!empty($v['desc'])){
                    $desc = html_entity_decode($v['desc']);
                }
                $distribution_config = json_decode($v['distribution_config'],true);
                $price_list = array();
                foreach ($distribution_config as $cv){
                    $price_list[]=$cv['price'];
                }
                $min_price = min($price_list);
                $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'price'=>intval($v['price']),'line_price'=>intval($v['line_price']),
                    'img_url'=>$img_url,'desc'=>$desc,'min_price'=>$min_price);
                $datalist[] = $dinfo;
            }
        }

        $img_poster = $oss_host.'WeChat/MiniProgram/images/poster-head.png';
        $this->to_back(array('datalist'=>$datalist,'sale_uid'=>$sale_uid,'qrcode_url'=>$qrcode_url,'img_poster'=>$img_poster));
    }

    public function topDistributionGoods(){
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goods = $m_goods->getDataList('id,name,top_media_id',array('type'=>45,'status'=>1,'is_top'=>1),'sort desc,id desc');
        $datalist = array();
        if(!empty($res_goods)){
            $m_media = new \Common\Model\MediaModel();
            foreach ($res_goods as $v){
                $img_url = '';
                if(!empty($v['top_media_id'])){
                    $res_media = $m_media->getMediaInfoById($v['top_media_id']);
                    $img_url = $res_media['oss_addr'];
                }
                $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'img_url'=>$img_url);
                $datalist[] = $dinfo;
            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }
}