<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class CartModel extends BaseModel{
	protected $tableName='smallapp_cart';

    public function getList($fields,$where,$orderby,$start=0,$size=0){
        if($start >= 0 && $size){
            $list = $this->alias('cart')
                ->join('savor_smallapp_dishgoods goods on cart.goods_id=goods.id','left')
                ->field($fields)
                ->where($where)
                ->order($orderby)
                ->limit($start,$size)
                ->select();
            $count = $this->alias('cart')
                ->join('savor_smallapp_dishgoods goods on cart.goods_id=goods.id','left')
                ->field($fields)
                ->where($where)
                ->count();
            $data = array('list'=>$list,'total'=>$count);
        }else{
            $data = $this->alias('cart')
                ->join('savor_smallapp_dishgoods goods on cart.goods_id=goods.id','left')
                ->field($fields)
                ->where($where)
                ->order($orderby)
                ->select();
        }
        return $data;
    }
}