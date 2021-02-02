<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class ActivityapplyModel extends BaseModel{
	protected $tableName='smallapp_activityapply';

	public function getApplylist($fields,$where,$orderby,$group=''){
        $data = $this->field($fields)->where($where)->order($orderby)->group($group)->select();
        return $data;
    }
}