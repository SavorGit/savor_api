<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model;
use Think\Model;

class HotelModel extends Model
{
    protected $tableName='hotel';


    public function saveData($data, $where) {
        $bool = $this->where($where)->save($data);
        return $bool;
    }


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
        $sql ="select he.mac_addr,h.name as hotel_name,a.id as area_id,a.region_name as area_name
               from savor_hotel as h
               left join savor_hotel_ext as he on h.id=he.hotel_id
               left join savor_area_info as a on h.area_id =a.id where h.flag=0 and he.mac_addr='".$mac."'";
        $result =  $this->query($sql);
        if($result){
            return $result[0];
        }else {
            return false;
        }
    }
    public function getInfoById($hotelid,$field = '*'){
        $map = array();
        $map['id']    = $hotelid;
        $map['state'] = 1;
        $map['flag']  = 0;
        $info = $this->field($field)->where($map)->find();
       
        return $info;
    }

    public function getHotelCount($where){
        return $this->where($where)->count();
    }


    /**
     * getHotelDis
     * @desc 获取同一区域的所有酒楼的
     * @param $field
     * @param $hotelid
     * @return mixed
     */
    public function getHotelDis($field,$hotelid){
        if($hotelid == 7 || $hotelid == 53){
            $sql ="select $field
               from savor_hotel where area_id = (select area_id  from savor_hotel where id= $hotelid) and hotel_box_type=3 and state=1 and flag=0";
        }else{
            $sql ="select $field
               from savor_hotel where area_id = (select area_id  from savor_hotel where id= $hotelid) and id not in (7,53) and hotel_box_type=3 and state=1 and flag=0";
        }

        $result =  $this->query($sql);
        return $result;
    }

    /**
     * getAllDis
     * @获取所有酒楼数据
     * @param $field 字段
     * @return array
     */
    public function getAllDis($field){
        $sql ="select $field
               from savor_hotel where id not in (7,53) and   hotel_box_type=3 and state=1 and flag=0";
        $result =  $this->query($sql);
        return $result;
    }


    public function gethotellogoInfo($hotelid){
        $sql = "SELECT
        media.id AS id,
        media.oss_addr AS name,
        media.md5 AS md5,
        'fullMd5' AS md5_type,
        'logo' AS type,
        media.oss_addr AS oss_path,
        media.duration AS duration,
        media.surfix AS suffix,
        0 AS sortNum,
        media.name AS chinese_name,
        media.id AS version
        FROM savor_hotel hotel
        LEFT JOIN savor_media media on media.id=hotel.media_id
        where
            hotel.id={$hotelid}";
        $result = $this->query($sql);
        return $result;
    }


    /**
     * getHotelInfo 获取酒楼信息
     * @access public
     * @param $hotelid
     * @return array
     */
    public function getHotelMacInfo($hotelid){
        $sql = "SELECT
        sh.id AS hotel_id,
        sh.name AS hotel_name,
        sh.area_id AS area_id,
        sh.addr AS address,
        sh.contractor AS linkman,
        sh.mobile AS mobile,
        sh.tel AS tel,
        sh.maintainer AS maintainer,
        sh.level AS level,
        sh.iskey AS key_point,
        sh.install_date AS install_date,
        sh.state AS state,
        sh.state_change_reason AS state_reason,
        sh.gps AS gps,
        sh.remark AS remark,
        sh.flag AS flag,
        sh.create_time AS create_time,
        sh.update_time AS update_time,
        sh.hotel_box_type AS hotel_box_type,
        she.mac_addr AS mac,
        she.ip_local AS ip_local,
        she.ip AS ip,
        she.server_location AS server
        FROM savor_hotel sh
        LEFT JOIN savor_hotel_ext she
        ON sh.id=she.hotel_id
        where
            sh.id={$hotelid}";
        $result = $this->query($sql);
        return $result;
    }


    public function getStatisticalNumByHotelId($hotel_id,$type=''){
        $sql = "select id as room_id,hotel_id from savor_room where hotel_id='$hotel_id'";
        $res = $this->query($sql);
        $room_num = $box_num = $tv_num = 0;
        $all_rooms = array();
        foreach ($res as $k=>$v){
            $room_num++;
            $all_rooms[] = $v['room_id'];
        }
        if($type == 'room'){
            $nums = array('room_num'=>$room_num,'room'=>$all_rooms);
            return $nums;
        }
        if($room_num){
            $rooms_str = join(',', $all_rooms);
            $sql = "select id as box_id,room_id from savor_box where room_id in ($rooms_str)";
            $res = $this->query($sql);
            $all_box = array();
            foreach ($res as $k=>$v){
                $box_num++;
                $all_box[] = $v['box_id'];
            }
            if($type == 'box'){
                $nums = array('box_num'=>$box_num,'box'=>$all_box);
                return $nums;
            }
            if($box_num){
                $box_str = join(',', $all_box);
                $sql = "select count(id) as tv_num from savor_tv where box_id in ($box_str)";
                $res = $this->query($sql);
                $tv_num = $res[0]['tv_num'];
                if($type == 'tv'){
                    $nums = array('tv_num'=>$tv_num);
                    return $nums;
                }
            }
        }
        $nums = array('room_num'=>$room_num,'box_num'=>$box_num,'tv_num'=>$tv_num);
        return $nums;
    }

    public function getRoomNumByHotelId($hotel_id){
        $sql = "select id as room_id,hotel_id from savor_room where hotel_id='$hotel_id' and state=1 and flag=0";
        $res = $this->query($sql);
        $room_num = 0;
        foreach ($res as $k=>$v){
            $room_num++;
        }
        return $room_num;
    }

}