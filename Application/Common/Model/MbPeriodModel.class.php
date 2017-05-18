<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model;
use Think\Model;

class MbPeriodModel extends Model{
	protected $tableName='mb_period';

	public function getOneInfo($field ='*',$where,$order,$limit){
		$result = $this->field($field)->where($where)->order($order)->limit($limit)->select();
		return $result;
	}


}
