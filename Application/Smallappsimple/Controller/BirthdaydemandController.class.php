<?php
namespace Smallappsimple\Controller;
use \Common\Controller\CommonController;
class BirthdaydemandController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'demandList':
                $this->is_verify = 0;
                $this->valid_fields = array('boxMac'=>1002);
                break;
        }
        parent::_init_();
    }

    public function demandList(){
        $datalist = array();
        $m_birthday = new \Common\Model\Smallapp\BirthdayModel();
        $res_birthday = $m_birthday->getDataList('*','','id desc');
        $m_media = new \Common\Model\MediaModel();
        foreach ($res_birthday as $v){
            $name_arr = explode('-',$v['name']);
            $res_media = $m_media->getMediaInfoById($v['media_id']);
            $file_info = pathinfo($res_media['oss_addr']);
            $info = array('name'=>$v['name'],'title'=>$name_arr[0],
                'sub_title'=>$name_arr[1],'oss_url'=>$res_media['oss_addr'],'media_id'=>$res_media['id'],'oss_path'=>$res_media['oss_path'],
                'media_name'=>$file_info['basename'],'surfix'=>$res_media['surfix'],'md5'=>$res_media['md5'],
                'type'=>1
                );
            $datalist[] = $info;
        }

        $fields = 'id,name,media_id,start_month,start_day,end_month,end_day,intro';
        $where = array('status'=>1);
        $orderby = 'end_month asc,end_day asc';
        $m_constellation = new \Common\Model\Smallapp\ConstellationModel();
        $res = $m_constellation->getDataList($fields,$where,$orderby);
        $month = date('n');
        $day = date('j');
        $constellation_id = 1;
        foreach ($res as $k=>$v){
            if($month==$v['end_month'] && $day<=$v['end_day']){
                $constellation_id = $v['id'];
                break;
            }elseif($month==$v['start_month'] && $day>=$v['start_day']){
                $constellation_id = $v['id'];
                break;
            }
        }


        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $key_demand = C('SAPP_BIRTHDAYDEMAND');
        $res_demand = $redis->get($key_demand);
        if(!empty($res_demand)){
            $birthday_demand = json_decode($res_demand,true);
            if(isset($birthday_demand['constellation_id']) && $birthday_demand['constellation_id']!=$constellation_id){
                $period = getMillisecond();
            }else{
                $period = $birthday_demand['period'];
            }
        }else{
            $period = getMillisecond();
        }
        $demand_data = array('period'=>$period,'constellation_id'=>$constellation_id);
        $redis->set($key_demand,json_encode($demand_data));

        $m_constellationvideo = new \Common\Model\Smallapp\ConstellationvideoModel();
        $where = array('constellation_id'=>$constellation_id,'status'=>1);
        $orderby = 'sort desc,id desc';
        $res = $m_constellationvideo->getDataList('id,name,media_id',$where,$orderby);
        if(!empty($res)){
            $m_media = new \Common\Model\MediaModel();
            foreach ($res as $v){
                $res_media = $m_media->getMediaInfoById($v['media_id']);
                $video_url = $res_media['oss_addr'];
                $file_info = pathinfo($res_media['oss_addr']);
                $video_img = $video_url.'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450';
                $datalist[] = array('name'=>$v['name'],'img'=>$video_img,'oss_url'=>$video_url,'md5'=>$res_media['md5'],'oss_path'=>$res_media['oss_path'],
                'media_id'=>$res_media['id'],'media_name'=>$file_info['basename'],'surfix'=>$res_media['surfix'],'md5'=>$res_media['md5'],'type'=>2
                );
            }
        }
        $res = array('period'=>$period,'datalist'=>$datalist);
        $this->to_back($res);
    }


}
