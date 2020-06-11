<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class DishController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'goodslist':
                $this->is_verify = 1;
                $this->valid_fields = array('merchant_id'=>1001,'page'=>1001,'type'=>1002);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function goodslist(){
        $merchant_id = intval($this->params['merchant_id']);
        $page = intval($this->params['page']);
        $type = isset($this->params['type'])?intval($this->params['type']):21;//类型21商家外卖商品 22商家售全国商品

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('id'=>$merchant_id));
        if(empty($res_merchant) || $res_merchant['status']!=1){
            $this->to_back(93035);
        }

        $pagesize = 10;
        $all_nums = $page * $pagesize;

        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $where = array('merchant_id'=>$merchant_id,'status'=>1,'type'=>$type);
        $where['gtype'] = array('in',array(1,2));
        $orderby = 'is_top desc,status asc,id desc';
        $res_goods = $m_goods->getDataList('*',$where,$orderby,0,$all_nums);

        $datalist = array();
        if($res_goods['total']){
            $host_name = 'https://'.$_SERVER['HTTP_HOST'];
            foreach ($res_goods['list'] as $v){
                $price = $v['price'];
                $line_price = $v['line_price'];
                $stock_num = $v['amount'];
                $goods_id = $v['id'];

                $gtype = $v['gtype'];
                $attrs = array();
                $model_img = '';
                if($gtype==2){
                    $res_attrs = $m_goods->getGoodsAttr($v['id']);
                    $price = $res_attrs['default']['price'];
                    $line_price = $res_attrs['default']['line_price'];
                    $stock_num = $res_attrs['default']['amount'];
                    $goods_id = $res_attrs['default']['id'];
                    $attrs = $res_attrs['attrs'];
                    $model_img = $res_attrs['default']['model_img'];
                }

                $img_url = '';
                if(!empty($v['cover_imgs'])){
                    $oss_host = "https://".C('OSS_HOST').'/';
                    $cover_imgs_info = explode(',',$v['cover_imgs']);
                    if(!empty($cover_imgs_info[0])){
                        $img_url = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                    }
                }
                $dinfo = array('id'=>$goods_id,'name'=>$v['name'],'price'=>$price,'line_price'=>$line_price,'type'=>$v['type'],'gtype'=>$gtype,
                    'model_img'=>$model_img,'stock_num'=>$stock_num,'img_url'=>$img_url,'is_localsale'=>intval($v['is_localsale']),
                    'status'=>intval($v['status']),'attrs'=>$attrs);
                $dinfo['qrcode_url'] = $host_name."/smallsale18/qrcode/dishQrcode?data_id={$v['id']}&type=25";
                $datalist[] = $dinfo;
            }
        }
        $this->to_back($datalist);
    }

    public function detail(){
        $goods_id = intval($this->params['goods_id']);

        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if(empty($res_goods)){
            $this->to_back(93034);
        }
        if($res_goods['status']!=1){
            $this->to_back(93037);
        }

        $price = $res_goods['price'];
        $line_price = $res_goods['line_price'];
        $stock_num = $res_goods['amount'];

        $specification_goods = array();
        $attrs = array();
        $model_img = '';
        if($res_goods['type']==22 && in_array($res_goods['gtype'],array(2,3))){
            if($res_goods['gtype']==2){
                $res_data = $m_goods->getDataList('*',array('parent_id'=>$goods_id,'status'=>1),'id asc',0,1);
                if(!$res_data['total']){
                    $this->to_back(93037);
                }
                $res_goods['parent_id'] = $goods_id;
                $goods_id = $res_data['list'][0]['id'];
            }
            $res_attrs = $m_goods->getGoodsAttr($res_goods['parent_id'],$goods_id);
            $all_goods = $res_attrs['all_goods'];
            foreach ($all_goods as $v){
                $name = str_replace('_',',',$v['attr_name']);
                $specification_goods[]=array('goods_id'=>$v['id'],'name'=>$name,'attr_ids'=>$v['attr_ids']);
            }

            $price = $res_attrs['default']['price'];
            $line_price = $res_attrs['default']['line_price'];
            $stock_num = $res_attrs['default']['amount'];
            $model_img = $res_attrs['default']['model_img'];
            $attrs = $res_attrs['attrs'];
            $res_goods = $m_goods->getInfo(array('id'=>$res_goods['parent_id']));
        }

        $data = array('goods_id'=>$goods_id,'name'=>$res_goods['name'],'price'=>$price,'line_price'=>$line_price,
            'is_localsale'=>$res_goods['is_localsale'],'stock_num'=>$stock_num,'type'=>$res_goods['type'],'notice'=>$res_goods['notice'],
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
        $data['specification_goods'] = $specification_goods;

        $merchant = $gift = array();
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
            $m_goodsactivity = new \Common\Model\Smallapp\GoodsactivityModel();
            $res_activity = $m_goodsactivity->getInfo(array('goods_id'=>$goods_id));
            if(!empty($res_activity)){
                $res_ginfo = $m_goods->getInfo(array('id'=>$res_activity['gift_goods_id']));
                $gift = array('id'=>$res_ginfo['id'],'name'=>$res_ginfo['name']);
            }
        }
        if(empty($gift)){
            $gift = new \stdClass();
        }
        $data['gift'] = $gift;

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

        $this->to_back($data);
    }

    public function getPlatform(){
        $merchant_id = intval($this->params['merchant_id']);

        $m_dishplatform = new \Common\Model\Smallapp\DishplatformModel();
        $where = array('merchant_id'=>$merchant_id);
        $res_platform = $m_dishplatform->getDataList('*',$where,'id desc');
        $datalist = array();
        if(!empty($res_platform)){
            $oss_host = "http://".C('OSS_HOST').'/';
            foreach ($res_platform as $v){
                $img_url = $oss_host.'/'.$v['img_path'];
                $info = array('id'=>$v['id'],'name'=>$v['name'],
                    'img_path'=>$v['img_path'],'img_url'=>$img_url);
                $datalist[]=$info;
            }
        }
        $this->to_back($datalist);
    }

}