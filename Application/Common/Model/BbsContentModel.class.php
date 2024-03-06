<?php
namespace Common\Model;

class BbsContentModel extends BaseModel{
	protected $tableName='bbs_content';

    public function getContentList($fields,$where,$orderby,$start=0,$size=0){
        $data = $this->alias('content')
            ->field($fields)
            ->where($where)
            ->order($orderby)
            ->limit($start,$size)
            ->select();
        return $data;
    }
}