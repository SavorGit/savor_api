<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class DishorderModel extends BaseModel{
	protected $tableName='smallapp_dishorder';

    public function getList($fields,$where,$orderby,$start=0,$size=0){
        if($start >= 0 && $size){
            $list = $this->alias('o')
                ->join('savor_smallapp_dishgoods goods on o.dishgoods_id=goods.id','left')
                ->field($fields)
                ->where($where)
                ->order($orderby)
                ->limit($start,$size)
                ->select();
            $count = $this->alias('o')
                ->join('savor_smallapp_dishgoods goods on o.dishgoods_id=goods.id','left')
                ->field($fields)
                ->where($where)
                ->count();
            $data = array('list'=>$list,'total'=>$count);
        }else{
            $data = $this->alias('o')
                ->join('savor_smallapp_dishgoods goods on o.dishgoods_id=goods.id','left')
                ->field($fields)
                ->where($where)
                ->order($orderby)
                ->select();
        }
        return $data;
    }
}