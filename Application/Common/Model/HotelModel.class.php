<?php
/**
 *@author hongwei
 *
 *
 */
namespace Common\Model;
use Think\Model;

class HotelModel extends Model
{
    protected $tableName='hotel';
    public function getOneById($field,$id){
        return $this->field($field)->where("id='".$id."'")->find();
    }

    public function getHotelInfoById($hotelId){
        $sql ="select he.mac_addr,h.name as hotel_name,a.id as area_id,a.name as area_name
               from savor_hotel as h
               left join savor_hotel_ext as he on h.id=he.hotel_id
               left join savor_area as a on h.area_id =a.id where h.flag=0 and h.id=".$hotelId;
        $result =  $this->query($sql);
        if($result){
            return $result[0];
        }else {
            return false;
        }
    }
    public function getHotelInfoByMac($mac){
        $sql ="select he.mac_addr,h.name as hotel_name,a.id as area_id,a.name as area_name
               from savor_hotel as h
               left join savor_hotel_ext as he on h.id=he.hotel_id
               left join savor_area as a on h.area_id =a.id where h.flag=0 and he.mac_addr='".$mac."'";
        $result =  $this->query($sql);
        if($result){
            return $result[0];
        }else {
            return false;
        }
    }

}