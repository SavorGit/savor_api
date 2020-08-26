<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class CommentModel extends BaseModel{
	protected $tableName='smallapp_comment';

	public function getCommentInfo($fields,$condition){
        $result = $this->field($fields)->where($condition)->select();
        return $result;
    }
    public function isHaveComment($openid,$comment_time){
        $where = array();
        $where['user.openid']= $openid;
        $where['user.status'] = 1;
        $where['user.small_app_id'] = 1;
        $where['comment.add_time'] = array('gt',$comment_time);
        $ret = $this->alias('comment')
                    ->join('savor_smallapp_user user on comment.user_id= user.id','left')->where($where)->count();
        return $ret;
    }
    public function getHotelCommentList($fields,$where,$order,$start=0,$size=0){
        $ret = $this->alias('comment')
             ->field($fields)
             ->join('savor_box box on comment.box_mac=box.mac','left')
             ->join('savor_room room on box.room_id= room.id','left')
             ->join('savor_hotel hotel on room.hotel_id=hotel.id','left')
             ->where($where)
             ->order($order)->limit($start,$size)->select();
        
        $count = count($ret);
        $data = array('list'=>$ret,'total'=>$count);
        //echo $this->getLastSql();exit;
        return $data;
    }
}