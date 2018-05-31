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


    public function getListInfo($fields ,$where, $order,$limit){
        $data = $this->alias('a')
            ->join('savor_room as room on a.room_id = room.id ')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;

    }

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




    public function getInfoByHotelid($hotelid , $field,$where){
        $sql = 'select '.$field;
        $sql  .= ' FROM  savor_box box  LEFT JOIN savor_room room ON  box.room_id = room.id  WHERE room.hotel_id=' . $hotelid.$where;

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
    public function getTvNumsByHotelid($hotel_id){
        $count = $this->alias('a')
        ->join('savor_room c on a.room_id= c.id')
        ->join('savor_hotel d on c.hotel_id=d.id')
        ->where('d.id='.$hotel_id.' and a.flag=0 and a.state !=2 and c.flag=0 and c.state !=2')
        ->count();
         
        return  $count;
    
    }
    public function getBoxListByHotelid($fields,$hotel_id){
        $data = $this->alias('a')
             ->field($fields)
             ->join('savor_room c on a.room_id= c.id','left')
             ->join('savor_hotel d on c.hotel_id=d.id','left')
             ->where('d.id='.$hotel_id.' and a.flag=0 and a.state =1 ')
             ->select();
        return $data;
    }


    public function saveData($save, $where) {
        $data = $this->where($where)->save($save);
        return $data;
    }
    public function getBoxInfo($fileds,$where){
        $data = $this->alias('a')
             ->field($fileds)
             ->join('savor_room c on a.room_id= c.id','left')
             ->join('savor_hotel d on c.hotel_id=d.id','left')
             ->where($where)
             ->select();
        return $data;
    }
    public function countBoxNums($where){
        $nums = $this->alias('a')
             
             ->join('savor_room b on a.room_id= b.id')
             ->join('savor_hotel c on b.hotel_id=c.id')
             ->join('savor_hotel_ext d on d.hotel_id=c.id')
             ->where($where)
             ->count();
        return $nums;
    }
}