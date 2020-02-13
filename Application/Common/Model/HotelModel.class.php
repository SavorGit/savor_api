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


    public function getPlaMac($field,$id){
        return $this->alias('a')
            ->field($field)
            ->join(' savor_hotel_ext b on a.id=b.hotel_id','left')
            ->join('savor_sysuser c on c.id=b.maintainer_id','left')
            ->join('savor_opuser_role d on c.id=d.user_id','left')
            ->where("a.id='".$id."' and c.status=1 and d.state=1")->find();
    }

    public function getOneById($field,$id){
        return $this->field($field)->where("id='".$id."'")->find();
    }

    public function getHotelInfoById($hotelId){
        $sql ="select he.mac_addr,h.name as hotel_name,h.hotel_box_type,a.id as area_id,a.region_name as area_name
               from savor_hotel as h
               left join savor_hotel_ext as he on h.id=he.hotel_id
               left join savor_area_info as a on h.area_id =a.id where h.flag=0 and h.id=".$hotelId;
        $result =  $this->query($sql);
        if($result){
            return $result[0];
        }else {
            return false;
        }
    }

    public function getHotelById($field,$where){
        $res = $this->alias('hotel')
            ->field($field)
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->join('savor_area_info area on area.id=hotel.area_id','left')
            ->where($where)
            ->find();
        return $res;
    }

    public function getHotelInfoByMac($mac){
        $sql ="select he.mac_addr,h.name as hotel_name,a.id as area_id,a.region_name as area_name
               from savor_hotel as h
               left join savor_hotel_ext as he on h.id=he.hotel_id
               left join savor_area_info as a on h.area_id =a.id where h.state=1 and h.flag=0 and he.mac_addr='".$mac."'";
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
    public function getHotelCountNums($where){
        $count =$this->alias('a')
             ->join('savor_hotel_ext b on a.id=b.hotel_id','left')
             ->where($where)
             ->count();
        return $count;
    }

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


    public function getStatisticalNumByStateHotelId($hotel_id,$type=''){
        $sql = "select id as room_id,hotel_id from savor_room where hotel_id='$hotel_id' and flag = 0";
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
            $sql = "select id as box_id,room_id from savor_box where room_id in ($rooms_str) and flag=0 ";
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
                $sql = "select count(id) as tv_num from savor_tv where box_id in ($box_str) and flag=0 ";
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
                $sql = "select count(id) as tv_num from savor_tv where box_id in ($box_str) and flag=0 and state!=2";
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
    

    public function getMacaddrByHotelId($hotel_id){
        $sql = "select * from savor_hotel_ext where hotel_id='$hotel_id' limit 1";
        $result = $this->query($sql);
        $data = !empty($result)?$result[0]:array();
        return $data;
    }

    public function getRoomNumByHotelId($hotel_id){
        $sql = "select count(*) as num from savor_room where hotel_id='$hotel_id' and state=1 and flag=0";
        $res = $this->query($sql);
        $room_num = 0;
        if(!empty($res)){
            $room_num = $res[0]['num'];
        }
        return $room_num;
    }
    public function getHotelList($where,$order,$limit,$fields = '*'){
        $data = $this->field($fields)->where($where)->order($order)->limit($limit)->select();
        return $data;
    }
    public function getHotelLists($where,$order,$limit,$fields = '*'){
        $data = $this->alias('a')
                     ->join('savor_hotel_ext b on a.id=b.hotel_id')
                    ->field($fields)->where($where)->order($order)->limit($limit)->select();
        return $data;
    }

    public function changeIdinfoToName($result=[])
    {
        if(!$result || !is_array($result))
        {
            return [];
        }
        $areaModel  = new \Common\Model\AreaModel();
        $area = $areaModel->getAllArea();
        $key_arr = C('HOTEL_KEY');
        $hotel_level = C('HOTEL_LEVEL');
        $state_change = C('STATE_REASON');
        $hotel_state = C('HOTEL_STATE');
        $hotel_box_type = C('HOTEL_BOX_TYPE');
        foreach ($result as &$value)
        {
            foreach($area as $row)
            {
                if($value['area_id'] == $row['id'])
                {
                    $value['area_name'] = $row['region_name'];
                }

            }

            foreach($key_arr as $hk=>$hv)
            {
                if($value['is_key'] == $hk)
                {
                    $value['is_key'] = $hv;
                }

            }

            foreach($hotel_level as $hk=>$hv)
            {
                if($value['level'] == $hk)
                {
                    $value['level'] = $hv;
                }

            }

            foreach($state_change as $hk=>$hv)
            {
                if($value['state_change_reason'] == $hk)
                {
                    $value['state_change_reason'] = $hv;
                }

            }

            foreach($hotel_state as $hk=>$hv)
            {
                if($value['hotel_state'] == $hk)
                {
                    $value['hotel_state'] = $hv;
                }

            }

            foreach($hotel_box_type as $hk=>$hv)
            {
                if($value['hotel_box_type'] == $hk)
                {
                    $value['hotel_box_type'] = $hv;
                }

            }

            unset($value['area_id']);
        }
        return $result;

    }//End Function

}