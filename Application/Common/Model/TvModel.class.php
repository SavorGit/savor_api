<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model;
use Think\Model;

class TvModel extends Model{
    protected $tableName  ='tv';
	public function getList($where, $field){
		 $list = $this->field($field)->where($where)
					  ->select();
        return $list;
	}




	
}
