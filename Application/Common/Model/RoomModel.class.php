<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model;
use Think\Model;

class RoomModel extends Model
{
	protected $tableName='room';

	public function getWhere($where, $field){
		$list = $this->where($where)->field($field)->select();

		return $list;
	}
    public function getOne($fields,$where){
        $data = $this->field($fields)->where($where)->find();
        return $data;
    }


}//End Class