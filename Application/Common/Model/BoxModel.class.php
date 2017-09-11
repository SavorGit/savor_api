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
                   h.name as hotel_name,a.id as area_id, a.region_name as area_name
                   from savor_box as b
                   left join savor_room as r on b.room_id=r.id
                   left join savor_hotel as h on r.hotel_id=h.id
                   left join savor_area_info as a on h.area_id=a.id 
                   where h.state!=2 and h.flag=0 and b.state!=2 and b.flag=0 and  b.mac='".$mac."' limit 1";
            $result = $this->query($sql);
            if($result){
                return $result[0];
            }else {
                return false;
            }
        }
    }
    public function getBoxInfoByMac($mac){
        $map['mac'] = $mac;
        $map['flag'] = 0;
        return $this->where($map)->find();
    }



    public function getInfoByHotelid($hotelid , $field){
        $sql = 'select '.$field;
        $sql  .= 'FROM  savor_box box  LEFT JOIN savor_room room ON  box.room_id = room.id  WHERE room.hotel_id=' . $hotelid;
        $result = $this->query($sql);
        return $result;
    }


    public function getOnerow($where){
        $list = $this->where($where)->find();
        return $list;
    }


    public function getList($fields ,$where, $order,$limit){
        $data = $this->alias('a')
             ->join('savor_room as room on a.room_id = room.id ')
             ->field($fields)
             ->where($where)
             ->order($order)
             ->limit($limit)
             ->select();
        return $data;

    }
    
}