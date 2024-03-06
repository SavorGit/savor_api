<?php
namespace Common\Model;

class BbsCollectModel extends BaseModel{
	protected $tableName='bbs_collect';

    public function getCommentContentList($fields,$where,$orderby,$start=0,$size=0){
        $data = $this->alias('a')
            ->join('savor_bbs_content content on a.content_id=content.id','left')
            ->field($fields)
            ->where($where)
            ->order($orderby)
            ->limit($start,$size)
            ->select();
        return $data;
    }
}