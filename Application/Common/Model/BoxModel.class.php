<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class BoxModel extends Model
{
    protected $tableName='box';
    public function getHotelInfoByBoxMac($mac){
        if($mac){
            $sql ="select b.id as box_id,b.name as box_name,b.room_id,r.name as room_name, h.id as hotel_id,
                   h.name as hotel_name,a.id as area_id, a.name as area_name
                   from savor_box as b
                   left join savor_room as r on b.room_id=r.id
                   left join savor_hotel as h on r.hotel_id=h.id
                   left join savor_area as a on h.area_id=a.id 
                   where b.mac='".$mac."' limit 1";
            $result = $this->query($sql);
            if($result){
                return $result[0];
            }else {
                return false;
            }
        }
    }
}