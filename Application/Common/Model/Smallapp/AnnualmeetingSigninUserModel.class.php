<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class AnnualmeetingSigninUserModel extends BaseModel{
	protected $tableName='smallapp_annualmeeting_signinuser';

    public function getsigninuser($fields,$where,$order,$limit,$group){
        $data = $this->alias('a')
            ->join('savor_smallapp_user user on a.openid=user.openid','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }
}