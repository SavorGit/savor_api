<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class CommenttagidsModel extends BaseModel{
	protected $tableName='smallapp_comment_tagids';

    public function getCommentTags($fileds,$where){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_smallapp_tags tag on a.tag_id=tag.id','left')
            ->where($where)
            ->select();
        return $res;
    }
}