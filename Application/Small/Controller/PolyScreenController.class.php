<?php
/**
 * @desc    聚屏广告
 * @author  zhang.yingtao
 * @since   20180411
 */

namespace Small\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
class PolyScreenController extends CommonController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getAdsList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取聚屏广告列表
     */
    public function getAdsList(){
        $hotel_id = $this->params['hotel_id'];
        $m_hotel  = new \Common\Model\HotelModel();
        $hotel_info = $m_hotel->getInfoById($hotel_id,'id');
        if(empty($hotel_info)){
            $this->to_back(90002);
        }
        $m_box = new \Common\Model\BoxModel();
        
        $fields = 'a.id box_id,a.mac box_mac,tpmedia_id,a.update_time';
        $where  = " a.flag=0 and a.state=1  and d.id=$hotel_id";
        
        $box_list = $m_box->getBoxInfo($fields, $where);
        if(empty($box_list)){
            $this->to_back(90003);
        }
        
        $result = array();
        $m_pub_poly_ads = new \Common\Model\PubPolyAdsModel();
        foreach($box_list as $key=>$v){
            
            $list = array();
            if(!empty($v['tpmedia_id'])){
                $fields = "a.update_time,media.id,media.oss_addr name,media.md5,media.type as mtype,a.media_md5  tp_md5,
                   a.type as media_type,'poly' as type,media.oss_addr oss_path,media.duration,media.surfix,
                    media.name chinese_name,a.tpmedia_id,ads.is_sapp_qrcode";
                $where = array();
                $where['a.state'] = 1;
                $where['a.flag'] =0;
                $where['a.tpmedia_id'] = array('in',$v['tpmedia_id']);
                
                $order = 'a.update_time desc ';
                $list = $m_pub_poly_ads->getList($fields, $where, $order);
            }
            
            $result[$key]['box_id'] = $v['box_id'];
            $result[$key]['box_mac']= $v['box_mac'];
            
            if(!empty($list)){
                $update_time_arr = array_column($list,'update_time');
                $period = max($update_time_arr);
                foreach($list as $k=>$vv){
                    $name = explode('/', $vv['name']);
                    $list[$k]['name'] = $name[2];
                    if($vv['mtype']==1){
                        $list[$k]['md5_type']='easyMd5';
                    }elseif($vv['mtype']==2){
                        $list[$k]['md5_type']='fullMd5';
                    }else{
                        $list[$k]['md5_type']='easyMd5';
                    }
                    $list[$k]['is_sapp_qrcode'] = intval($vv['is_sapp_qrcode']);
                    unset($list[$k]['update_time'],$list[$k]['mtype']);
                }
                $result[$key]['menu_num'] = date('YmdHis',strtotime($period));
                $result[$key]['media_list'] = $list;
            }else {
                if(empty($v['tpmedia_id'])){
                    $v['tpmedia_id'] = "1";
                }
                
                /* $where = array();
                $where['a.flag'] =0;
                $where['a.tpmedia_id'] = array('in',$v['tpmedia_id']);
                
                $order = 'a.update_time desc ';
                $fields = "a.update_time,media.id,media.oss_addr name,media.md5,'easyMd5' as md5_type,a.media_md5  tp_md5,
                   'poly' as type,media.oss_addr oss_path,media.duration,media.surfix,
                    media.name chinese_name,a.tpmedia_id";
                $list = $m_pub_poly_ads->getList($fields, $where, $order,'limit 1');
                $period = $list[0]['update_time']; */
                $result[$key]['menu_num'] = date('YmdHis',strtotime($v['update_time']));
                $result[$key]['media_list'] = array();
            }
        }
        $this->to_back($result);
    }
}