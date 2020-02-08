<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class CommentModel extends BaseModel{
	protected $tableName='smallapp_comment';

	public function getCommentInfo($fields,$condition){
        $result = $this->field($fields)->where($condition)->select();
        return $result;
    }
}