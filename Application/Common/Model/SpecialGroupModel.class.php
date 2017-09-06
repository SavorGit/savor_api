<?php
namespace Common\Model;
use Think\Model;

/**
 * Class FeedbackModel
 * @package Common\Model\Feedback
 */
class SpecialGroupModel extends Model{
	protected $tableName = 'special_group';
	
	public function getInfo($where,$order , $limit ,$type ){
	    if(is_array($where)){
	        $where['state'] =1;
	    }else {
	        $where .=' and state =1';
	    }
	    if($type == 1){
	       $result = $this->field('id,name,title,img_url,desc')->where($where)->order($order)->find();
	    }else {
	       $result = $this->field('id,name,title,img_url,desc')->where($where)->order($order)->limit($limit)->select();
	    }
	    return $result;
	}
	public function getList($where,$order ,$limit){
	    $sql ="select `id`,`name`,`title`,`img_url`,`desc`,`update_time` updateTime from `savor_special_group` where 1=1  $where and state=1 order by $order $limit";
	    $data = $this->query($sql);
	    
	    return $data;
	}
}