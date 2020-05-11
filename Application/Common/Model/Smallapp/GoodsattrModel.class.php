<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class GoodsattrModel extends BaseModel{
	protected $tableName='smallapp_goods_attr';

    public function getGoodsAttrs($goods_ids,$default_goods_id){
        $fields = 'sattr.id,sattr.name,sattr.specification_id,a.goods_id';
        $where = array('a.goods_id'=>array('in',$goods_ids),'a.status'=>1);
        $orderby = 'sattr.id asc';
        $list = $this->alias('a')
            ->join('savor_smallapp_goods_specificationattr sattr on a.attr_id=sattr.id','left')
            ->field($fields)
            ->where($where)
            ->order($orderby)
            ->select();

        $all_goods_attr = array();
        $all_list = array();
        foreach ($list as $v){
            $all_list[$v['id']]=$v;
            $all_goods_attr[$v['goods_id']][$v['id']]=$v;
        }
        $default_attrs = $all_goods_attr[$default_goods_id];

        $all_attrs = array();
        foreach ($all_list as $k=>$v){
            $is_select = 0;
            if(array_key_exists($v['id'],$default_attrs)){
                $is_select = 1;
            }
            $info = array('id'=>$v['id'],'name'=>$v['name'],'is_select'=>$is_select);
            $all_attrs[$v['specification_id']][]=$info;
        }
        $res = array('all_attrs'=>$all_attrs,'all_goods_attrs'=>$all_goods_attr);
        return $res;
    }

	
}