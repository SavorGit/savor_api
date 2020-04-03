<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class UserincomeModel extends BaseModel{
	protected $tableName='smallapp_userincome';

    public function getList($fields,$where,$orderby,$start=0,$size=0){
        if($start >= 0 && $size){
            $list = $this->alias('i')
                ->join('savor_smallapp_user u on u.openid=i.openid','left')
                ->field($fields)
                ->where($where)
                ->order($orderby)
                ->limit($start,$size)
                ->select();
            $count = $this->countNum($where);
            $data = array('list'=>$list,'total'=>$count);
        }else{
            $data = $this->alias('i')
                ->join('savor_smallapp_user u on u.openid=i.openid','left')
                ->field($fields)
                ->where($where)
                ->order($orderby)
                ->select();
        }
        return $data;
    }
}