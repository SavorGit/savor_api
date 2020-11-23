<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class BoxModel extends Model{
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
                   where h.state!=2 and h.flag=0 and b.state!=2 and b.flag=0 and b.mac='".$mac."' limit 1";
            $result = $this->query($sql);
            if($result){
                return $result[0];
            }else {
                return false;
            }
        }
    }

    public function getHotelInfoByBoxMacNew($mac){
        if($mac){
            $sql ="select b.id as box_id,b.name as box_name,b.room_id,b.box_type,r.name as room_name, h.id as hotel_id,
                   h.name as hotel_name,b.name box_name,h.hotel_box_type,a.id as area_id, a.region_name as area_name,b.is_open_simple,
                   b.is_4g,h.is_4g hotel_is_4g
                   from savor_box as b
                   left join savor_room as r on b.room_id=r.id
                   left join savor_hotel as h on r.hotel_id=h.id
                   left join savor_area_info as a on h.area_id=a.id
                   where h.state=1 and h.flag=0 and b.state=1 and b.flag=0 and  b.mac='".$mac."' limit 1";
            $result = $this->query($sql);
            if($result){
                return $result[0];
            }else {
                return false;
            }
        }
    }

    public function checkForscreenTypeByMac($box_mac){
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $box_key = "box:forscreentype:$box_mac";
        $res_forscreen = $redis->get($box_key);
        if(!empty($res_forscreen)){
            $forscreen_info = json_decode($res_forscreen,true);
        }else{
            $fields = 'box.id as box_id,box.box_type,box.is_sapp_forscreen,box.is_open_simple,box.is_open_popcomment';
            $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0,'hotel.state'=>1,'hotel.flag'=>0);
            $order = 'box.id desc';
            $limit = '0,1';
            $res_box = $this->alias('box')
                ->join('savor_room as room on box.room_id=room.id')
                ->join('savor_hotel as hotel on room.hotel_id=hotel.id')
                ->field($fields)
                ->where($where)
                ->order($order)
                ->limit($limit)
                ->find();
            $forscreen_type = 1;//1外网(主干) 2直连(极简)
            $box_forscreen = '1-0';
            if(!empty($res_box)){
                $is_open_popcomment = $res_box['is_open_popcomment'];
                $box_id = $res_box['box_id'];
                $box_forscreen = "{$res_box['is_sapp_forscreen']}-{$res_box['is_open_simple']}";
                switch ($box_forscreen){
                    case '1-0':
                        $forscreen_type = 1;
                        break;
                    case '0-1':
                        $forscreen_type = 2;
                        break;
                    case '1-1':
                        /* if(in_array($res_box['box_type'],array(3,6,7))){
                            $forscreen_type = 2;
                        }elseif($res_box['box_type']==2){
                            $forscreen_type = 1;
                        } */
                        $forscreen_type = 1;
                        break;
                    default:
                        $forscreen_type = 1;
                }
            }else{
                $box_id = 0;
                $is_open_popcomment = 0;
            }
            $forscreen_info = array('is_open_popcomment'=>$is_open_popcomment,'box_id'=>$box_id,'forscreen_type'=>$forscreen_type,'forscreen_method'=>$box_forscreen);
            $redis->set($box_key,json_encode($forscreen_info));
        }
        return $forscreen_info;
    }

    public function getBoxInfoByMac($mac){
        $map['mac'] = $mac;
        $map['flag'] = 0;
        return $this->where($map)->find();
    }

    public function getInfoByHotelid($hotelid,$field,$where){
        $sql = 'select '.$field;
        $sql  .= ' FROM  savor_box box  LEFT JOIN savor_room room ON  box.room_id = room.id  WHERE room.hotel_id=' . $hotelid.$where;
        $result = $this->query($sql);
        return $result;
    }

    public function getBoxByCondition($fields='box.*',$where,$group=''){
        $res = $this->alias('box')
            ->join('savor_room room on room.id= box.room_id','left')
            ->join('savor_hotel hotel on room.hotel_id=hotel.id','left')
            ->field($fields)
            ->where($where)
            ->group($group)
            ->select();
        return $res;
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
             ->where('d.id='.$hotel_id.' and a.flag=0 and a.state =1 and d.flag=0 and d.state =1 ')
             ->select();
        return $data;
    }

    public function getBoxListByHotelRelation($fields,$hotel_id){
        $cache_key = C('SMALLAPP_HOTEL_RELATION');
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(2);
        $res_cache = $redis->get($cache_key.$hotel_id);
        $where_hotel_id = "d.id=$hotel_id ";
        if(!empty($res_cache)){
            $relation_hotel_id = intval($res_cache);
            $where_hotel_id = "d.id in($hotel_id,$relation_hotel_id) ";
        }
        $data = $this->alias('a')
            ->field($fields)
            ->join('savor_room c on a.room_id= c.id','left')
            ->join('savor_hotel d on c.hotel_id=d.id','left')
            ->where($where_hotel_id.'and a.flag=0 and a.state =1 and d.flag=0 and d.state =1 ')
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
             ->join('savor_hotel_ext ext on d.id=ext.hotel_id','left')
             ->where($where)
             ->select();
        return $data;
    }

    public function countBoxNums($where){
        $nums = $this->alias('a')
             ->join('savor_room b on a.room_id= b.id','left')
             ->join('savor_hotel c on b.hotel_id=c.id','left')
             ->join('savor_hotel_ext d on d.hotel_id=c.id','left')
             ->where($where)
             ->count();
        return $nums;
    }


}