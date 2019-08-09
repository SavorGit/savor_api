<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;
class ForscreenHelpuserModel extends BaseModel{
	protected $tableName='smallapp_forscreen_helpuser';

    public function getList($fields,$where,$order,$limit){
        $data = $this->alias('h')
            ->join('savor_smallapp_user u on h.openid=u.openid','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;
    }

}