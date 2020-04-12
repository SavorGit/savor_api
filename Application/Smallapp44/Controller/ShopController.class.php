<?php
namespace Smallapp44\Controller;
use \Common\Controller\CommonController as CommonController;

class ShopController extends CommonController{

    public $is_tv = 0;

    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'goods':
                $this->is_verify = 1;
                $this->valid_fields = array('category_id'=>1002,'keywords'=>1002,'page'=>1001);
                break;
            case 'recommend':
                $this->is_verify = 1;
                $this->valid_fields = array('merchant_id'=>1002,'goods_id'=>1002,'page'=>1001,'pagesize'=>1002);
                break;
            case 'getcartgoods':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_ids'=>1001);
                break;
        }
        parent::_init_();
    }

    public function goods(){
        $category_id = isset($this->params['category_id'])?intval($this->params['category_id']):0;
        $keywords = isset($this->params['keywords'])?trim($this->params['keywords']):'';
        $page = intval($this->params['page']);
        $pagesize = 10;
        $all_nums = $page * $pagesize;
        if($category_id){
            $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
            $fields = "id,name,price,line_price,'0' as media_id,cover_imgs,amount,type,add_time";
            $orderby = 'id desc';
            $where = array('type'=>22,'status'=>1,'category_id'=>$category_id);
            $res_goods = $m_goods->getDataList($fields,$where,$orderby,0,$all_nums);
        }else{
            $m_goods = new \Common\Model\Smallapp\GoodsModel();
            $res_goods = $m_goods->getAllShopGoods($keywords,0,$all_nums);
        }
        $res_data = array('total'=>0,'datalist'=>array());
        if($res_goods['total']){
            $res_data['total'] = $res_goods['total'];

            $oss_host = "http://".C('OSS_HOST').'/';
            $m_media = new \Common\Model\MediaModel();
            foreach ($res_goods['list'] as $v){
                $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'price'=>$v['price'],'line_price'=>$v['line_price'],'stock_num'=>$v['amount'],
                    'type'=>$v['type'],'is_tv'=>0);
                if($v['type']==10){
                    $media_id = $v['media_id'];
                    $media_info = $m_media->getMediaInfoById($media_id);
                    $oss_path = $media_info['oss_path'];
                    $oss_path_info = pathinfo($oss_path);
                    if($media_info['type']==2){
                        $img_url = $media_info['oss_addr']."?x-oss-process=image/resize,p_50/quality,q_80";
                    }else{
                        $img_url = $media_info['oss_addr'].'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450,m_fast';
                    }
                    $dinfo['is_tv'] = $this->is_tv;
                    $dinfo['img_url'] = $img_url;
                    $dinfo['duration'] = $media_info['duration'];
                    $dinfo['tx_url'] = $media_info['oss_addr'];
                    $dinfo['filename'] = $oss_path_info['basename'];
                    $dinfo['forscreen_url'] = $oss_path;
                    $dinfo['resource_size'] = $media_info['oss_filesize'];
                }else{
                    $img_url = '';
                    if(!empty($v['cover_imgs'])){
                        $cover_imgs_info = explode(',',$v['cover_imgs']);
                        if(!empty($cover_imgs_info[0])){
                            $img_url = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                        }
                    }
                    $dinfo['img_url'] = $img_url;
                }
                $res_data['datalist'][]=$dinfo;
            }
            $datalist = $res_data['datalist'];
            shuffle($datalist);
            $res_data['datalist'] = $datalist;
        }
        $this->to_back($res_data);
    }

    public function recommend(){
        $merchant_id = intval($this->params['merchant_id']);
        $page = intval($this->params['page']);
        $pagesize = isset($this->params['pagesize'])?intval($this->params['pagesize']):10;
        $goods_id = isset($this->params['goods_id'])?intval($this->params['goods_id']):10;

        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $where = array('status'=>1,'type'=>22,'is_recommend'=>1);
        if($merchant_id){
            $where['merchant_id'] = $merchant_id;
        }
        if($goods_id){
            $where['id'] = array('neq',$goods_id);
        }
        $orderby = 'id desc';
        $all_nums = $page * $pagesize;
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
                        $img_url = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                    }
                }
                $price = $v['price'];
                $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'price'=>$price,'line_price'=>$v['line_price'],'type'=>$v['type'],
                    'stock_num'=>$v['amount'],'img_url'=>$img_url,'status'=>intval($v['status']));
                $dinfo['qrcode_url'] = $host_name."/smallsale18/qrcode/dishQrcode?data_id={$v['id']}&type=25";
                $datalist[] = $dinfo;
            }
        }
        $this->to_back($datalist);
    }

    public function getcartgoods(){
        $goods_ids = $this->params['goods_ids'];
        $json_str = stripslashes(html_entity_decode($goods_ids));
        $goods_ids_arr = json_decode($json_str,true);
        $ids = array();
        $id_mapinfo = array();
        if(!empty($goods_ids_arr)){
            foreach ($goods_ids_arr as $v){
                if(!empty($v['id'])){
                    $ids[]=intval($v['id']);
                }
                $id_mapinfo[$v['id']]=array('amount'=>$v['amount'],'ischecked'=>$v['ischecked']);
            }
        }
        $datas = array('online'=>array(),'offline'=>array());
        if(!empty($ids)){
            $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
            $fields = "id,name,price,amount,cover_imgs,type,is_localsale,status,merchant_id";
            $where = array('id'=>array('in',$ids));
            $res_goods = $m_goods->getDataList($fields,$where,'id desc');
            $res_online = array();
            foreach ($res_goods as $v){
                $img_url = '';
                if(!empty($v['cover_imgs'])){
                    $oss_host = "https://".C('OSS_HOST').'/';
                    $cover_imgs_info = explode(',',$v['cover_imgs']);
                    if(!empty($cover_imgs_info[0])){
                        $img_url = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                    }
                }
                $num = $id_mapinfo[$v['id']]['amount'];
                if($num>$v['amount']){
                    $num = $v['amount'];
                }
                $ischecked = false;
                if(isset($id_mapinfo[$v['id']]['ischecked'])){
                    $ischecked = $id_mapinfo[$v['id']]['ischecked'];
                }
                $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'price'=>$v['price'],'amount'=>$num,'stock_num'=>$v['amount'],'type'=>$v['type'],
                    'img_url'=>$img_url,'status'=>intval($v['status']),'ischecked'=>$ischecked,'is_localsale'=>$v['is_localsale'],'localsale_str'=>'');
                if($v['is_localsale'] && $v['type']==22){
                    $m_merchant = new \Common\Model\Integral\MerchantModel();
                    $fields = 'hotel.area_id,area.region_name';
                    $res_merchantinfo = $m_merchant->getMerchantInfo($fields,array('m.id'=>$v['merchant_id']));
                    $dinfo['localsale_str'] = '仅售'.$res_merchantinfo[0]['region_name'];
                }

                if($v['status']==1){
                    $res_online[$v['merchant_id']][]=$dinfo;
                }else{
                    $datas['offline'][]=$dinfo;
                }
            }
            foreach ($res_online as $k=>$v){
                $m_merchant = new \Common\Model\Integral\MerchantModel();
                $res_merchant = $m_merchant->getMerchantInfo('m.id,hotel.name as hotel_name',array('m.id'=>$k));
                $info = array('merchant_id'=>$k,'name'=>$res_merchant[0]['hotel_name'],'ischecked'=>false,'goods'=>$v);
                $datas['online'][]=$info;
            }
        }
        $this->to_back($datas);
    }



}