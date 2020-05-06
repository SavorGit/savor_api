<?php
namespace Smallapp44\Controller;
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
        $attr_id = $attrs[0];

        $attr_ids = join('_',$attrs);
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res = $m_goods->getInfo(array('id'=>$goods_id));
        if(empty($res)){
            $this->to_back(93034);
        }
        $parent_goods_id = $res['parent_id'];
        $res_goods = $m_goods->getInfo(array('parent_id'=>$parent_goods_id,'attr_ids'=>$attr_ids));

        $price = $res_goods['price'];
        $line_price = $res_goods['line_price'];
        $stock_num = $res_goods['amount'];
        $now_goods_id = $res_goods['id'];

        $attrs = array();
        $model_img = '';
        if($res_goods['gtype']==3){
            $res_attrs = $m_goods->getGoodsAttr($res_goods['parent_id'],$res_goods['id']);
            $model_img = $res_attrs['default']['model_img']."?x-oss-process=image/resize,p_50/quality,q_80";
            $attrs = $res_attrs['attrs'];

            $attr_1 = $attrs[0]['attrs'];
            $link_attrs = array();
            foreach ($attr_1 as $v){
                $link_attrs[]=$v['id'];
            }
            $goods_ids = array();
            foreach ($res_attrs['all_goods'] as $v){
                $goods_ids[]=$v['id'];
            }
            $where = array('goods_id'=>array('in',$goods_ids),'attr_id'=>$attr_id);
            $orderby = 'id asc';
            $m_goodsattr = new \Common\Model\Smallapp\GoodsattrModel();
            $list = $m_goodsattr->field('goods_id')->where($where)->order($orderby)->group('goods_id')->select();
            foreach ($list as $v){
                if(isset($res_attrs['all_goods_attrs'][$v['goods_id']])){
                    foreach ($res_attrs['all_goods_attrs'][$v['goods_id']] as $v){
                        $link_attrs[]=$v['id'];
                    }
                }
            }
            foreach ($attrs as $k=>$v){
                foreach ($v['attrs'] as $kk=>$vv){
                    if(in_array($vv['id'],$link_attrs)){
                        $v['attrs'][$kk]['is_disable']=0;
                    }else{
                        $v['attrs'][$kk]['is_disable']=1;
                    }
                }
                $attrs[$k] = $v;
            }
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

        $this->to_back($data);
    }

}