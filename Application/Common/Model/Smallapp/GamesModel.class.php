<?php
/**
 * @desc 小程序游戏用户(某个公众号下的用户)
 */
namespace Common\Model\Smallapp;
use Think\Model;

class GamesModel extends Model
{
	protected $tableName='smallapp_games';
	
	public function addInfo($data,$type=1){
	    if($type==1){
	        $ret = $this->add($data);
	        
	    }else {
	        $ret = $this->addAll($data);
	    }
	    return $ret;
	}
	public function updateInfo($where,$data){
	    $ret = $this->where($where)->save($data);
	    return $ret;
	}
	public function getWhere($fields,$where,$order,$limit,$group){
	    $data = $this->alias('a')
	                 ->join('savor_media m on a.media_id=m.id','left')
	                 ->field($fields)
	                 ->where($where)
	                 ->order($order)
	                 ->group($group)
	                 ->limit($limit)
	                 ->select();
	    return $data;
	}
	public function getOne($fields,$where,$order){
	    $data =  $this->field($fields)->where($where)->order($order)->find();
	    return $data;
	}
	public function countNum($where){
	    $nums = $this->where($where)->count();
	    return $nums;
	}
}