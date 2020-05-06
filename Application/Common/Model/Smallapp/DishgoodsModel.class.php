<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class DishgoodsModel extends BaseModel{
	protected $tableName='smallapp_dishgoods';

    public function getGoods($fileds,$where){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_integral_merchant merchant on a.merchant_id=merchant.id','left')
            ->where($where)
            ->select();
        return $res;
    }

    public function getGoodsInfo($fileds,$where){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_integral_merchant merchant on a.merchant_id=merchant.id','left')
            ->join('savor_hotel hotel on hotel.id=merchant.hotel_id','left')
            ->join('savor_area_info area on area.id=hotel.area_id','left')
            ->where($where)
            ->select();
        return $res;
    }

    public function getGoodsAttr($goods_id,$default_goods_id=0){
        $res_goods = $this->getDataList('*',array('parent_id'=>$goods_id),'id asc');
        $goods_ids = array();
        if(!empty($res_goods)){
            if(!$default_goods_id){
                $default_goods_id = $res_goods[0]['id'];
            }
            $default = array();
            foreach ($res_goods as $v){
                $goods_ids[]=$v['id'];
                if($default_goods_id==$v['id']){
                    $default = $v;
                }
            }
            $m_goodsattr = new \Common\Model\Smallapp\GoodsattrModel();
            $res_attrs = $m_goodsattr->getGoodsAttrs($goods_ids,$default_goods_id);
            $attrs = $res_attrs['all_attrs'];

            $fields = 'id,name';
            $where = array('id'=>array('in',array_keys($attrs)));
            $m_goods_specification = new \Common\Model\Smallapp\GoodsspecificationModel();
            $res_goods_specification = $m_goods_specification->getDataList($fields,$where,'sort desc');
            $all_attrs = array();
            foreach ($res_goods_specification as $v){
                $info = array('id'=>$v['id'],'name'=>$v['name'],'attrs'=>$attrs[$v['id']]);
                $all_attrs[]=$info;
            }
            $default['model_img'] ='';
            if($default['model_media_id']){
                $m_media = new \Common\Model\MediaModel();
                $res_media = $m_media->getMediaInfoById($default['model_media_id']);
                $default['model_img'] = $res_media['oss_addr'];
            }
            $res_data = array('default'=>$default,'attrs'=>$all_attrs,'all_goods'=>$res_goods,'all_goods_attrs'=>$res_attrs['all_goods_attrs']);
            return $res_data;
        }

    }
}