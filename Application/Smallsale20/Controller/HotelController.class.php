<?php
namespace Smallsale20\Controller;
use \Common\Controller\CommonController as CommonController;
class HotelController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotelList':
                $this->is_verify = 0;
                break;
            case 'tvHelpvideos':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function tvHelpvideos(){
        $hotel_id = intval($this->params['hotel_id']);
        $m_tvvideo = new \Common\Model\TvswitchvideoModel();
        $where = array('hotel_id'=>$hotel_id,'status'=>1);
        $res_videos = $m_tvvideo->getDataList('*',$where,'id desc');
        $datalist = array();
        if(!empty($res_videos)){
            $m_media = new \Common\Model\MediaModel();
            foreach ($res_videos as $v){
                $res_media = $m_media->getMediaInfoById($v['media_id']);
                $res_url = $res_media['oss_addr'];
                $info = array('name'=>$v['name'],'url'=>$res_url);
                $datalist[]=$info;
            }
        }
        $res = array('datalist'=>$datalist);
        $this->to_back($res);
    }

    public function getHotelList(){
        $m_hotel = new \Common\Model\HotelModel();
        $where = array('state'=>1,'flag'=>0);
        $hotel_box_types = C('HEART_HOTEL_BOX_TYPE');
        $box_types = array_keys($hotel_box_types);
        $where['hotel_box_type'] = array('in',$box_types);
        $res_hotels = $m_hotel->getHotelList($where,'id asc','','id,name');

        $m_hotel = new \Common\Model\HotelModel();
        $all_hotels = array();
        foreach ($res_hotels as $v){
            $hotel_has_room = $m_hotel->checkHotelHasRoom($v['id']);
            $v['hotel_has_room'] = $hotel_has_room;
            $letter = getFirstCharter($v['name']);
            $all_hotels[$letter][]=$v;
        }
        ksort($all_hotels);
        $data = array();
        foreach ($all_hotels as $k=>$v){
            $dinfo = array('id'=>ord("$k")-64,'region'=>$k,'items'=>$v);
            $data[]=$dinfo;
        }
        $this->to_back($data);
    }

    public function getExplist(){
        $avg_exp_arr = array(
            'agv_name'=>array('请选择','100以下','100-200','200以上'),
            'agv_lisg'=>array(
                array('id'=>0,'name'=>'请选择'),
                array('id'=>1,'name'=>'100以下'),
                array('id'=>2,'name'=>'100-200'),
                array('id'=>3,'name'=>'200以上')
            ));
        $this->to_back($avg_exp_arr);
    }
}