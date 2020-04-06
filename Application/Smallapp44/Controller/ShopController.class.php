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
        }
        parent::_init_();
    }

    public function goods(){
        $category_id = isset($this->params['category_id'])?intval($this->params['category_id']):0;
        $keywords = isset($this->params['keywords'])?trim($this->params['category_id']):'';
        $page = intval($this->params['page']);
        $pagesize = 10;
        $all_nums = $page * $pagesize;
        if($category_id){
            $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
            $fields = "id,name,price,line_price,'0' as media_id,cover_imgs,type,add_time";
            $orderby = 'id desc';
            $where = array('type'=>22,'status'=>1);
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
                $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'price'=>$v['price'],'line_price'=>$v['line_price'],'type'=>$v['type'],'is_tv'=>0);
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
        }
        $this->to_back($res_data);
    }



}