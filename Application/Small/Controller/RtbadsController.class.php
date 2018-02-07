<?php
namespace Small\Controller;
use Think\Controller;

use \Common\Controller\CommonController as CommonController;
class RtbadsController extends CommonController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getAll':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
            case 'getTagPortrayalPercent':
                $this->is_verify = 0;
                break;
            case 'getRtbConfig':
                $this->is_verify = 0;
                break;
            case 'getHotelAttendant':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
           
        }
        parent::_init_();
       
    }
    public function getAll(){
        $hotel_id = $this->params['hotel_id'];
        $m_rtb_ads = new \Common\Model\PubRtbAdsModel();
         
         $field = "a.id rtsid,a.start_date,a.end_date,c.id,c.oss_addr AS name,c.md5, 'easyMd5' as `md5_type`,
                   'rtbads' as `type`,c.oss_addr oss_path,c.duration,c.surfix,c.name as chinese_name,a.create_time,a.admaster_sin";
         $where = array();
         $now_date = date('Y-m-d H:i:s');
         $where['h.hotel_id'] = $hotel_id;
         $where['a.flag'] = 0;
         //$where['b.flag'] =0;
         $where['a.start_date'] = array('elt',$now_date);
         $where['a.end_date']   = array('egt',$now_date);
         $order = 'a.create_time desc';

         $data = $m_rtb_ads->getAdsList($field, $where, $order);
         if(empty($data)){
             $this->to_back(10000);
         }
         $m_pub_rtbtag = new \Common\Model\PubRtbtagModel(); 
         foreach($data as $key=>$val){
             
             if(!empty($val['name'])){
                 $ttp = explode('/', $val['name']);
                 $data[$key]['name'] = $ttp[2];
             }
             $adsb_arr[] = $val['create_time'];
             
             $tag_list = $m_pub_rtbtag->getlist('b.tagname,b.tag_code',array('pub_ads_id'=>$val['rtsid']));
             $data[$key]['tag_list'] = $tag_list;
             unset($data[$key]['rtsid']);
             unset($data[$key]['create_time']);
         }
         $period = date('YmdHis',strtotime(max($adsb_arr)));
         $result['period'] = $period;
         $result['list']   = $data;
         $this->to_back($result);
    }
    /**
     * @获取标签画像匹配成功的百分比
     */
    public function getTagPortrayalPercent(){
        $percent = C('RTB_TAG_PORTRAYAL_PERCENT');
        $data['percent'] = $percent;
        $this->to_back($data);
    }
    /**
     * @desc 获取RTB广告配置项
     */
    public function getRtbConfig(){
        $data = array();
        $rtb_ads_config_arr = C('RTB_ADS_CONFIG_ARR');
        $data['minMacNum'] = $rtb_ads_config_arr['minMacNum'];
        $data['maxAdsNum'] = $rtb_ads_config_arr['maxAdsNum'];
        $data['minLineNum']= $rtb_ads_config_arr['minLineNum'];
        
        $this->to_back($data);
    }
    /**
     * @desc 获取餐厅工作人员mac
     */
    public function getHotelAttendant(){
        $hotel_id = $this->params['hotel_id'];
        $hotel_attendant = new \Common\Model\HotelAttendantModel();
        $fields = 'hotel_id,device_mac'; 
        $where = array();
        $where['hotel_id'] = $hotel_id ;
        $data =  $hotel_attendant->getWhere($fields, $where);
        if(empty($data)){
            $this->to_back(80001);
        }
        $this->to_back($data);
    }
   
}