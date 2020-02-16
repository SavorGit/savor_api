<?php
namespace Smallsale18\Controller;
use \Common\Controller\CommonController as CommonController;

class DishController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'goodslist':
                $this->is_verify = 1;
                $this->valid_fields = array('merchant_id'=>1001,'page'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001);
                break;
            case 'addDish':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'name'=>1001,'price'=>1001,
                    'imgs'=>1001,'intro'=>1002,'detail_imgs'=>1002);
                break;
            case 'top':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'openid'=>1001);
                break;
            case 'putaway':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'openid'=>1001,'status'=>1001);
                break;
        }
        parent::_init_();
    }

    public function goodslist(){
        $merchant_id = intval($this->params['merchant_id']);
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('id'=>$merchant_id));
        if(empty($res_merchant) || $res_merchant['status']!=1){
            $this->to_back(93035);
        }
        $page = intval($this->params['page']);
        $pagesize = 10;
        $all_nums = $page * $pagesize;

        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $where = array('merchant_id'=>$merchant_id);
        $orderby = 'is_top desc,status asc,id desc';
        $res_goods = $m_goods->getDataList('*',$where,$orderby,0,$all_nums);

        $datalist = array();
        if($res_goods['total']){
            $host_name = 'https://'.$_SERVER['HTTP_HOST'];
            foreach ($res_goods['list'] as $v){
                $img_url = '';
                if(!empty($v['cover_imgs'])){
                    $oss_host = "https://".C('OSS_HOST').'/';
                    $cover_imgs_info = explode(',',$v['cover_imgs']);
                    if(!empty($cover_imgs_info[0])){
                        $img_url = $oss_host.$cover_imgs_info[0].'?x-oss-process=image/resize,p_50/quality,q_80';
                    }
                }
                $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'price'=>$v['price'],'img_url'=>$img_url,
                    'is_top'=>intval($v['is_top']),'status'=>intval($v['status']));
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
        $data = array('goods_id'=>$goods_id,'name'=>$res_goods['name'],'price'=>$res_goods['price']);
        $oss_host = "https://".C('OSS_HOST').'/';
        $cover_imgs = $detail_imgs = array();
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

        $merchant = array();
        $merchant['merchant_id'] = $res_goods['merchant_id'];
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('id'=>$res_goods['merchant_id']));
        $m_hotel = new \Common\Model\HotelModel();
        $field = 'hotel.name,hotel.mobile,hotel.tel,ext.hotel_cover_media_id';
        $where = array('hotel.id'=>$res_merchant['hotel_id']);
        $res_hotel = $m_hotel->getHotelById($field,$where);
        $merchant['name'] = $res_hotel['name'];
        $merchant['mobile'] = $res_hotel['mobile'];
        $merchant['tel'] = $res_hotel['tel'];
        $merchant['img'] = '';
        if(!empty($res_hotel['hotel_cover_media_id'])){
            $m_media = new \Common\Model\MediaModel();
            $res_media = $m_media->getMediaInfoById($res_hotel['hotel_cover_media_id'],'https');
            $merchant['img'] = $res_media['oss_addr'];
        }
        $where = array('merchant_id'=>$merchant['merchant_id']);
        $merchant['num'] = $m_goods->countNum($where);
        $data['merchant'] = $merchant;

        $this->to_back($data);
    }

    public function addDish(){
        $openid = $this->params['openid'];
        $name = $this->params['name'];
        $price = $this->params['price'];
        $imgs = $this->params['imgs'];
        $intro = $this->params['intro'];
        $detail_imgs = $this->params['detail_imgs'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,merchant.id as merchant_id,merchant.is_takeout',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $staff_id = $res_staff[0]['id'];
        $merchant_id = $res_staff[0]['merchant_id'];
        $data = array('name'=>$name,'price'=>$price,'cover_imgs'=>$imgs,'merchant_id'=>$merchant_id,
            'staff_id'=>$staff_id,'status'=>1);
        if(!empty($intro)){
            $data['intro'] = trim($intro);
        }
        if(!empty($detail_imgs)){
            $data['detail_imgs'] = $detail_imgs;
        }
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res = $m_goods->add($data);
        if($res && $res_staff[0]['is_takeout']==0){
            $m_merchant = new \Common\Model\Integral\MerchantModel();
            $m_merchant->updateData(array('id'=>$merchant_id),array('is_takeout'=>1));
        }
        $this->to_back(array());
    }

    public function top(){
        $goods_id = intval($this->params['goods_id']);
        $openid = $this->params['openid'];
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,merchant.id as merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if(empty($res_goods)){
            $this->to_back(93034);
        }
        if($res_goods['status']!=1){
            $this->to_back(93037);
        }
        $where = array('merchant_id'=>$res_goods['merchant_id'],'is_top'=>1);
        $res = $m_goods->getInfo($where);
        if(!empty($res)){
            $m_goods->updateData(array('id'=>$res['id']),array('is_top'=>0));
        }
        $m_goods->updateData(array('id'=>$goods_id),array('is_top'=>1));
        $this->to_back(array());
    }

    public function putaway(){
        $goods_id = intval($this->params['goods_id']);
        $status = intval($this->params['status']);
        $openid = $this->params['openid'];
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,merchant.id as merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if(empty($res_goods)){
            $this->to_back(93034);
        }
        if(in_array($status,array(1,2))){
            $data = array('status'=>$status);
            if($status==2){
                $data['is_top'] = 0;
            }
            $m_goods->updateData(array('id'=>$goods_id),$data);
        }
        $this->to_back(array());
    }

}