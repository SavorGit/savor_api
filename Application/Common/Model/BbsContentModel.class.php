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

    public function updateHotNum($id,$view_num,$like_num,$comment_num,$collect_num){
        $hot_num = $view_num*1 + $like_num*2 + $comment_num*5 + $collect_num*5;
        $this->updateData(array('id'=>$id),array('hot_num'=>$hot_num));
        return $hot_num;
    }
}