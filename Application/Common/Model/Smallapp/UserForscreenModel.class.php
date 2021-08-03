<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class UserForscreenModel extends BaseModel{
	protected $tableName='smallapp_user_forscreen';

    public function getUserinfo($field,$where,$order){
        $data = $this->alias('a')
            ->join('savor_smallapp_user user on a.user_id=user.id','left')
            ->field($field)
            ->where($where)
            ->order($order)
            ->select();
        return $data;
    }
}