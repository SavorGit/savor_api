<?php
namespace Smallsale19\Controller;
use \Common\Controller\CommonController as CommonController;

class DishController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addDish':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'name'=>1001,'category_id'=>1002,'price'=>1001,'supply_price'=>1002,
                    'amount'=>1002,'imgs'=>1001,'intro'=>1002,'detail_imgs'=>1002,'video_path'=>1002,'is_localsale'=>1002,'type'=>1001);
                break;
            case 'editDish':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'openid'=>1001,'name'=>1001,'category_id'=>1002,'price'=>1001,'supply_price'=>1002,
                    'amount'=>1002,'imgs'=>1001,'intro'=>1002,'detail_imgs'=>1002,'video_path'=>1002,'is_localsale'=>1002,'type'=>1001);
                break;
            case 'goodslist':
                $this->is_verify = 1;
                $this->valid_fields = array('merchant_id'=>1001,'page'=>1001,'type'=>1002);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'openid'=>1002);
                break;
            case 'top':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'openid'=>1001);
                break;
            case 'putaway':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'openid'=>1001,'status'=>1001);
                break;
            case 'getPlatform':
                $this->is_verify = 1;
                $this->valid_fields = array('merchant_id'=>1001);
                break;
            case 'setPlatform':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'img1'=>1002,'img2'=>1002,'img3'=>1002);
                break;
        }
        parent::_init_();
    }

    public function addDish(){
        $openid = $this->params['openid'];
        $name = $this->params['name'];
        $price = $this->params['price'];
        $imgs = $this->params['imgs'];
        $intro = $this->params['intro'];
        $detail_imgs = $this->params['detail_imgs'];
        $type = intval($this->params['type']);//21商家外卖商品 22商家售全国商品
        $category_id = $this->params['category_id'];
        $supply_price = $this->params['supply_price'];
        $amount = intval($this->params['amount']);
        $video_path = $this->params['video_path'];
        $is_localsale = isset($this->params['is_localsale'])?intval($this->params['is_localsale']):0;

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,merchant.id as merchant_id,merchant.is_takeout,merchant.is_sale',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $staff_id = $res_staff[0]['id'];
        $merchant_id = $res_staff[0]['merchant_id'];
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $where = array('merchant_id'=>$merchant_id,'name'=>$name);
        $res_name = $m_goods->getInfo($where);
        if(!empty($res_name)){
            $this->to_back(93042);
        }
        $data = array('name'=>$name,'price'=>$price,'cover_imgs'=>$imgs,'merchant_id'=>$merchant_id,'type'=>$type,
            'staff_id'=>$staff_id,'status'=>1);
        if($type==22){
            if(empty($category_id) || empty($supply_price) || empty($amount) ||empty($video_path)){
                $this->to_back(1001);
            }
            if($amount>999){
                $this->to_back(93046);
            }
            $data['status'] = 2;
            $data['flag'] = 1;
            $data['category_id'] = $category_id;
            $data['supply_price'] = $supply_price;
            $data['amount'] = $amount;
            $data['is_localsale'] = $is_localsale;

            $typeinfo = C('RESOURCE_TYPEINFO');
            if(!empty($video_path)){
                $temp_info = pathinfo($video_path);
                $surfix = $temp_info['extension'];
                if($surfix){
                    $surfix = strtolower($surfix);
                }
                if(isset($typeinfo[$surfix])){
                    $media_type = $typeinfo[$surfix];
                }else{
                    $media_type = 3;
                }
                $m_media = new \Common\Model\MediaModel();
                $media_data = array('oss_addr'=>$video_path,'type'=>$media_type,'state'=>1);
                $media_id = $m_media->add($media_data);
                $data['video_intromedia_id'] = $media_id;
            }
        }
        if(!empty($intro)){
            $data['intro'] = trim($intro);
        }
        if(!empty($detail_imgs)){
            $data['detail_imgs'] = $detail_imgs;
        }
        $res = $m_goods->add($data);
        if($res){
            $merchant_data = array();
            if($res_staff[0]['is_takeout']==0){
                $merchant_data['is_takeout'] = 1;
            }
            if(!empty($merchant_data)){
                $m_merchant = new \Common\Model\Integral\MerchantModel();
                $m_merchant->updateData(array('id'=>$merchant_id),$merchant_data);
            }
        }
        $this->to_back(array());
    }

    public function editDish(){
        $goods_id = $this->params['goods_id'];
        $openid = $this->params['openid'];
        $name = $this->params['name'];
        $price = $this->params['price'];
        $imgs = $this->params['imgs'];
        $intro = $this->params['intro'];
        $detail_imgs = $this->params['detail_imgs'];
        $type = intval($this->params['type']);//21商家外卖商品 22商家售全国商品
        $category_id = $this->params['category_id'];
        $supply_price = $this->params['supply_price'];
        $amount = intval($this->params['amount']);
        $video_path = $this->params['video_path'];
        $is_localsale = isset($this->params['is_localsale'])?intval($this->params['is_localsale']):0;

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,merchant.id as merchant_id,merchant.is_takeout,merchant.is_sale',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if(empty($res_goods)){
            $this->to_back(93034);
        }

        $staff_id = $res_staff[0]['id'];
        $merchant_id = $res_staff[0]['merchant_id'];
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $where = array('merchant_id'=>$merchant_id,'name'=>$name);
        $res_name = $m_goods->getInfo($where);
        if(!empty($res_name) && $res_name['id']!=$goods_id){
            $this->to_back(93042);
        }
        $data = array('name'=>$name,'price'=>$price,'cover_imgs'=>$imgs,'merchant_id'=>$merchant_id,'type'=>$type,
            'staff_id'=>$staff_id,'status'=>1);
        if($type==22){
            if(empty($category_id) || empty($supply_price) || empty($amount) ||empty($video_path)){
                $this->to_back(1001);
            }
            if($amount>999){
                $this->to_back(93046);
            }
            $data['status'] = 2;
            $data['flag'] = 1;
            $data['category_id'] = $category_id;
            $data['supply_price'] = $supply_price;
            $data['amount'] = $amount;
            $data['is_localsale'] = $is_localsale;
            $m_media = new \Common\Model\MediaModel();
            $res_media = $m_media->getMediaInfoById($res_goods['video_intromedia_id']);
            if($res_media['oss_path']!=$video_path){
                $typeinfo = C('RESOURCE_TYPEINFO');
                if(!empty($video_path)){
                    $temp_info = pathinfo($video_path);
                    $surfix = $temp_info['extension'];
                    if($surfix){
                        $surfix = strtolower($surfix);
                    }
                    if(isset($typeinfo[$surfix])){
                        $media_type = $typeinfo[$surfix];
                    }else{
                        $media_type = 3;
                    }
                    $m_media = new \Common\Model\MediaModel();
                    $media_data = array('oss_addr'=>$video_path,'type'=>$media_type,'state'=>1);
                    $media_id = $m_media->add($media_data);
                    $data['video_intromedia_id'] = $media_id;
                }
            }
        }
        $data['intro'] = trim($intro);
        $data['detail_imgs'] = $detail_imgs;
        $res = $m_goods->updateData(array('id'=>$goods_id),$data);
        if($res){
            $merchant_data = array();
            if($res_staff[0]['is_takeout']==0){
                $merchant_data['is_takeout'] = 1;
            }
            if(!empty($merchant_data)){
                $m_merchant = new \Common\Model\Integral\MerchantModel();
                $m_merchant->updateData(array('id'=>$merchant_id),$merchant_data);
            }
        }
        $this->to_back(array());
    }


    public function goodslist(){
        $merchant_id = intval($this->params['merchant_id']);
        $type = isset($this->params['type'])?intval($this->params['type']):21;//21商家外卖商品 22商家售全国商品

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('id'=>$merchant_id));
        if(empty($res_merchant) || $res_merchant['status']!=1){
            $this->to_back(93035);
        }
        $page = intval($this->params['page']);
        $pagesize = 10;
        $all_nums = $page * $pagesize;

        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $where = array('merchant_id'=>$merchant_id,'type'=>$type);
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
                $price = $v['price'];
                $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'price'=>$price,'img_url'=>$img_url,'is_localsale'=>$v['is_localsale'],
                    'is_top'=>intval($v['is_top']),'status'=>intval($v['status']),'type'=>$v['type'],'flag'=>$v['flag']);
                $qrcode = $host_name."/smallsale19/qrcode/dishQrcode?data_id={$v['id']}&type=25";
                if($v['type']==22){
                    $qrcode = $host_name."/smallsale19/qrcode/dishQrcode?data_id={$v['id']}&type=26";
                }
                $dinfo['qrcode_url'] = $qrcode;
                $datalist[] = $dinfo;
            }
        }
        $this->to_back($datalist);
    }

    public function detail(){
        $openid = $this->params['openid'];
        $goods_id = intval($this->params['goods_id']);

        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if(empty($res_goods)){
            $this->to_back(93034);
        }
        $data = array('goods_id'=>$goods_id,'name'=>$res_goods['name'],'price'=>$res_goods['price'],'amount'=>$res_goods['amount'],
            'supply_price'=>$res_goods['supply_price'],'is_localsale'=>$res_goods['is_localsale'],'type'=>$res_goods['type'],'category_id'=>$res_goods['category_id']);

        $host_name = 'https://'.$_SERVER['HTTP_HOST'];
        $qrcode = $host_name."/smallsale19/qrcode/dishQrcode?data_id={$goods_id}&type=25";
        if($res_goods['type']==22){
            if($openid){
                $m_user = new \Common\Model\Smallapp\UserModel();
                $where = array('openid'=>$openid);
                $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
                $res_user = $m_user->getOne($fields, $where);
                if(empty($res_user)){
                    $this->to_back(92010);
                }
                $hash_ids_key = C('HASH_IDS_KEY');
                $hashids = new \Common\Lib\Hashids($hash_ids_key);
                $sale_uid = $hashids->encode($res_user['user_id']);
                $qrcode = $host_name."/smallsale19/qrcode/dishQrcode?data_id={$goods_id}&suid=$sale_uid&type=26";
            }else{
                $qrcode = $host_name."/smallsale19/qrcode/dishQrcode?data_id={$goods_id}&type=26";
            }
        }
        $data['qrcode_url'] = $qrcode;

        $oss_host = "https://".C('OSS_HOST').'/';
        $cover_imgs = $detail_imgs = array();
        $cover_imgs_path = $detail_imgs_path = array();

        if(!empty($res_goods['cover_imgs'])){
            $cover_imgs_info = explode(',',$res_goods['cover_imgs']);
            if(!empty($cover_imgs_info)){
                foreach ($cover_imgs_info as $v){
                    if(!empty($v)){
                        $img_url = $oss_host.$v."?x-oss-process=image/resize,m_mfit,h_400,w_750";
                        $cover_imgs[] = $img_url;
                        $cover_imgs_path[] = $v;
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
                        $detail_imgs_path[] = $v;
                    }
                }
            }
        }
        $video_url = $video_path = $video_img = '';
        $m_media = new \Common\Model\MediaModel();
        if(!empty($res_goods['video_intromedia_id'])){
            $res_media = $m_media->getMediaInfoById($res_goods['video_intromedia_id']);
            $video_url = $res_media['oss_addr'];
            $video_path = $res_media['oss_path'];
            $video_img = $video_url.'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450,m_fast';

        }
        $data['video_url'] = $video_url;
        $data['video_path'] = $video_path;
        $data['video_img'] = $video_img;
        $data['cover_imgs'] = $cover_imgs;
        $data['detail_imgs'] = $detail_imgs;
        $data['cover_imgs_path'] = $cover_imgs_path;
        $data['detail_imgs_path'] = $detail_imgs_path;
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
            $res_media = $m_media->getMediaInfoById($res_hotel['hotel_cover_media_id'],'https');
            $merchant['img'] = $res_media['oss_addr'];
        }
        $where = array('merchant_id'=>$merchant['merchant_id']);
        $merchant['num'] = $m_goods->countNum($where);
        $data['merchant'] = $merchant;

        $this->to_back($data);
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

    public function setPlatform(){
        $openid = $this->params['openid'];
        $name = $this->params['name'];
        $img_path = $this->params['img_path'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,merchant.id as merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];
        $data = array('name'=>$name,'img_path'=>$img_path,'merchant_id'=>$merchant_id);
        $m_dishplatform = new \Common\Model\Smallapp\DishplatformModel();
        $m_dishplatform->add($data);
        $this->to_back(array());
    }



}