<?php
namespace Common\Model;
use Think\Model;

class RoomModel extends Model{
	protected $tableName='room';

	public function getWhere($where, $field){
		$list = $this->where($where)->field($field)->select();

		return $list;
	}
    public function getOne($fields,$where){
        $data = $this->field($fields)->where($where)->find();
        return $data;
    }

    public function getRoomByCondition($fields='room.*',$where,$group=''){
        $res = $this->alias('room')
            ->join('savor_hotel hotel on room.hotel_id=hotel.id','left')
            ->field($fields)
            ->where($where)
            ->group($group)
            ->select();
        return $res;
    }


}