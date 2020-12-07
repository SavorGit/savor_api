<?php
namespace Opclient20\Controller;
use \Common\Controller\CommonController as CommonController;
class BootsituationController extends CommonController{

    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotelsByMainId':
                $this->is_verify = 1;
                $this->valid_fields = array('main_hotelid'=>1001);
                break;
            case 'getDetailByHotelId':
                $this->is_verify = 1;
                $this->valid_fields = array('h_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getHotelsByMainId(){
        $all_main_hotels = array(
            250=>array('name'=>'宝燕壹号','hotel_ids'=>'255,252,258,285,254,260,261,264,262'),
            330=>array('name'=>'榕港','hotel_ids'=>'331,630,332,330,671,483,807,338,533,490,336,335,333'),
        );
        $last_heart_time = date('Y-m-d H:i:s',strtotime('-10 minutes'));

        $main_hotelid = intval($this->params['main_hotelid']);
        if(!isset($all_main_hotels[$main_hotelid])){
            $this->to_back(array());
        }
        $main_hotel_info = $all_main_hotels[$main_hotelid];
        $m_hotels = new \Common\Model\HotelModel();
        $where = array('id'=>array('in',$main_hotel_info['hotel_ids']));
        $where['state'] = 1;
        $where['flag'] = 0;
        $res_hotels = $m_hotels->getHotelList($where,'','','id,name');
        $m_box = new \Common\Model\BoxModel();
        $m_heartlog = new \Common\Model\HeartLogModel();
        $hotels = array();
        foreach ($res_hotels as $v){
            $where = array('hotel.id'=>$v['id'],'box.state'=>1,'box.flag'=>0);
            $res_boxs = $m_box->getBoxByCondition('box.mac',$where);
            $all_boxs = array();
            foreach ($res_boxs as $bv){
                $all_boxs[]=$bv['mac'];
            }
            $total_box = count($all_boxs);
            $open_box = 0;
            $where = array('hotel_id'=>$v['id'],'box_mac'=>array('in',$all_boxs),
                'last_heart_time'=>array('egt',$last_heart_time),'type'=>2);
            $res_heart_boxs = $m_heartlog->getHotelHeartBox($where,'count(box_id) as num');
            if(!empty($res_heart_boxs)){
                $open_box = intval($res_heart_boxs[0]['num']);
            }
            $close_box = $total_box - $open_box;

            $hotels[] = array('id'=>$v['id'],'name'=>$v['name'],'boxCount'=>array('total'=>$total_box,'open'=>$open_box,'close'=>$close_box));
        }
        $resp_data = array('title'=>$main_hotel_info['name'],'hotels'=>$hotels);
        $this->to_back($resp_data);
    }

    public function getDetailByHotelId(){
        $hotel_id = intval($this->params['h_id']);
        $last_heart_time = date('Y-m-d H:i:s',strtotime('-10 minutes'));
        $m_hotels = new \Common\Model\HotelModel();
        $res_hotels = $m_hotels->getInfoById($hotel_id,'id,name');
        if(empty($res_hotels)){
           $this->to_back(array());
        }
        $where = array('hotel.id'=>$hotel_id,'box.state'=>1,'box.flag'=>0);
        $m_box = new \Common\Model\BoxModel();
        $m_heartlog = new \Common\Model\HeartLogModel();
        $res_boxs = $m_box->getBoxByCondition('box.id,box.mac,room.name as room_name',$where);
        $all_boxs = array();
        foreach ($res_boxs as $bv){
            $all_boxs[]=$bv['mac'];
        }

        $where = array('hotel_id'=>$hotel_id,'box_mac'=>array('in',$all_boxs),
            'last_heart_time'=>array('egt',$last_heart_time),'type'=>2);
        $res_heart_boxs = $m_heartlog->getHotelHeartBox($where,'box_mac');
        $open_boxs = array();
        if(!empty($res_heart_boxs)){
            foreach ($res_heart_boxs as $v){
                $open_boxs[]=$v['box_mac'];
            }
        }
        $opens = $closes = array();
        foreach ($res_boxs as $v){
            $binfo = array('id'=>$v['id'],'name'=>$v['room_name']);
            if(in_array($v['mac'],$open_boxs)){
                $opens[]=$binfo;
            }else{
                $closes[]=$binfo;
            }
        }
        $situation = array(
            array('label'=>'开机数量','count'=>count($opens),'boxes'=>$opens),
            array('label'=>'未开机数量','count'=>count($closes),'boxes'=>$closes)
        );
        $resp_data = array('id'=>$hotel_id,'name'=>$res_hotels['name'],'situation'=>$situation);
        $this->to_back($resp_data);
    }
}