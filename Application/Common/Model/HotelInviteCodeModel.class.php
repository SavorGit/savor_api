<?php
/**
 * @desc 餐厅端邀请码
 * @author zhang.yingtao
 * @since 20171201
 */
namespace Common\Model;
use Think\Model;

class HotelInviteCodeModel extends Model
{
	protected $tableName='hotel_invite_code';
	
	public function getOne($fields,$where){
	    $data = $this->field($fields)->where($where)->find();
	    return $data;
	}
	public function getInfo($fields,$where){
	    $data =  $this->alias('a')
	                  ->join('savor_hotel b on a.hotel_id=b.id','left')
	                  ->field($fields)
	                  ->where($where)
	                  ->find();
	    return $data;
	}
	public function saveInfo($where,$data){
	    $ret = $this->where($where)->save($data);
	    return $ret;
	}
}