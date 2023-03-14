<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class DistributionUserModel extends BaseModel{
	protected $tableName='smallapp_distribution_user';

    public function getUserDatas($fields,$where,$order,$limit=''){
        $data = $this->alias('a')
            ->join('savor_smallapp_user user on a.openid=user.openid','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;
    }
}