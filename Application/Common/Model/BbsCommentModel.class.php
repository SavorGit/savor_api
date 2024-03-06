<?php
namespace Common\Model;

class BbsCommentModel extends BaseModel{
	protected $tableName='bbs_comment';

    public function getCommentList($fields,$where,$orderby,$start=0,$size=0){
        if($start>=0 && $size>0){
            $data = $this->alias('a')
                ->join('savor_bbs_user buser on a.bbs_user_id=buser.id','left')
                ->field($fields)
                ->where($where)
                ->order($orderby)
                ->limit($start,$size)
                ->select();
        }else{
            $data = $this->alias('a')
                ->join('savor_bbs_user buser on a.bbs_user_id=buser.id','left')
                ->field($fields)
                ->where($where)
                ->order($orderby)
                ->select();
        }
        return $data;
    }

    public function getCommentContentList($fields,$where,$orderby,$start=0,$size=0,$group=''){
        $data = $this->alias('a')
            ->join('savor_bbs_content content on a.content_id=content.id','left')
            ->field($fields)
            ->where($where)
            ->order($orderby)
            ->limit($start,$size)
            ->group($group)
            ->select();
        return $data;
    }
}