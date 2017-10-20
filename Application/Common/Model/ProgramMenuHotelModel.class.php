<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class ProgramMenuHotelModel extends Model
{
	protected $tableName='programmenu_hotel';
    public function getLatestMenuid($hotel_id){
        $data = $this->alias('a')
                     ->join('savor_programmenu_list b  on a.menu_id=b.id')
                     ->field('a.menu_id,b.menu_num,a.pub_time')
                     ->where('a.hotel_id='.$hotel_id)->order('a.pub_time desc')->find();
        return $data;
    }
    public function getMenuHotelDownState($fields,$menu_id,$hotel_id){
        $data =$this
             ->field($fields)
             ->where('menu_id='.$menu_id.' and hotel_id='.$hotel_id)
             ->find();
        return $data;
    }
    public function updateInfo($where,$data){
        $ret = $this->where($where)->save($data);
        return $ret;
    }
}