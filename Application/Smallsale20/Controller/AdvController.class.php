<?php
namespace Smallsale20\Controller;
use \Common\Controller\CommonController as CommonController;

class AdvController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getAdvList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'page'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getAdvList(){
        $hotel_id = intval($this->params['hotel_id']);
        $page = intval($this->params['page']);
        $pagesize = 10;
        $oss_host = 'http://'. C('OSS_HOST').'/';

        $m_ads = new \Common\Model\AdsModel();
        $where = array('a.hotel_id'=>$hotel_id,'a.state'=>1,'a.is_online'=>1,'a.type'=>3);
        $fields = 'a.id,a.name title,a.img_url,a.duration,a.create_time,b.id as media_id,b.oss_addr,b.oss_filesize as resource_size';
        $all_nums = $page * $pagesize;
        $limit = "0,$all_nums";
        $res_ads = $m_ads->getAdsList($fields,$where,'a.id desc',$limit,'a.media_id');
        $ads_list = array();
        if(!empty($res_ads)){
            foreach($res_ads as $v){
                $create_time = $v['create_time'];
                $dinfo = array('id'=>$v['id'],'title'=>$v['title'],'forscreen_id'=>0,'res_type'=>2,'res_nums'=>1,'create_time'=>$create_time);

                $res_url = $oss_host.$v['oss_addr'];
                $forscreen_url = $v['oss_addr'];
                $duration = intval($v['duration']);
                $resource_size = $v['resource_size'];
                $res_id = $v['media_id'];
                $pdetail = array('res_url'=>$res_url,'forscreen_url'=>$forscreen_url,'duration'=>$duration,
                    'resource_size'=>$resource_size,'res_id'=>$res_id);
                $oss_info = pathinfo($forscreen_url);
                $pdetail['filename'] = $oss_info['basename'];

                $img_url = $v['img_url']? $v['img_url'] :'media/resource/EDBAEDArdh.png';
                $pdetail['img_url'] = $oss_host.$img_url;
                $dinfo['pubdetail'] = array($pdetail);
                $ads_list[] = $dinfo;
            }
        }
        $data = array('datalist'=>$ads_list);
        $this->to_back($data);
    }




}