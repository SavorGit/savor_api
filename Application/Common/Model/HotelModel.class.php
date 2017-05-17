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

    public function getHotelCount($where){
        return $this->where($where)->count();
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

}