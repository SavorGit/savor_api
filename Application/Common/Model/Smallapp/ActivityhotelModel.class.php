<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class ActivityhotelModel extends BaseModel{
	protected $tableName='smallapp_activityhotel';

    public function getActivityhotelDatas($fields,$where,$order,$limit,$group){
        $data = $this->alias('a')
            ->join('savor_smallapp_activity activity on a.activity_id=activity.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }

}