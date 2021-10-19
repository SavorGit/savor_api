<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController;
class ConstellationController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getConstellationList':
                $this->is_verify =0;
                break;
            case 'getConstellationDetail':
                $this->is_verify = 1;
                $this->valid_fields = array('constellation_id'=>1001);
                break;
            case 'getVideoList':
                $this->is_verify = 1;
                $this->valid_fields = array('constellation_id'=>1001);
                break;
        }
        parent::_init_();
    }

    /**
     * @desc 获取星座列表
     */
    public function getConstellationList(){
        $fields = 'id,name,media_id,start_month,start_day,end_month,end_day,intro';
        $where = array('status'=>1);
        $orderby = 'end_month asc,end_day asc';
        $m_constellation = new \Common\Model\Smallapp\ConstellationModel();
        $res = $m_constellation->getDataList($fields,$where,$orderby);
        $month = date('n');
        $day = date('j');
        $now_constellation = 0;
        foreach ($res as $k=>$v){
            if($month==$v['end_month'] && $day<=$v['end_day']){
                $now_constellation = $k;
                break;
            }elseif($month==$v['start_month'] && $day>=$v['start_day']){
                $now_constellation = $k;
                break;
            }
        }
        $total = count($res);
        $next_constellation = $now_constellation+1;
        if($next_constellation>=$total){
            $next_constellation = 0;
        }
        $constellations = array($res[$now_constellation],$res[$next_constellation]);
        $result = array();
        if(!empty($constellations)){
            $m_media = new \Common\Model\MediaModel();
            foreach ($constellations as $k=>$v){
                if($k==0){
                    $is_now = 1;
                }else{
                    $is_now = 0;
                }
                $res_media = $m_media->getMediaInfoById($v['media_id']);
                $img_url = $res_media['oss_addr'];
                $date = $v['start_month'].'.'.$v['start_day'].'-'.$v['end_month'].'.'.$v['end_day'];
                $result[] = array('id'=>$v['id'],'name'=>$v['name'],'is_now'=>$is_now,'img_url'=>$img_url,'date'=>$date,'intro'=>$v['intro']);
            }
        }
        $this->to_back($result);
    }

    /**
     * @desc 获取星座详情
     */
    public function getConstellationDetail(){
        $constellation_id = intval($this->params['constellation_id']);
        $m_constellation = new \Common\Model\Smallapp\ConstellationModel();
        $condition = array('id'=>$constellation_id);
        $result = $m_constellation->getInfo($condition);
        if(!empty($result)){
            $m_media = new \Common\Model\MediaModel();
            $res_media = $m_media->getMediaInfoById($result['media_id']);
            $result['img_url'] = $res_media['oss_addr'];
            $url = 'https://'.$_SERVER['HTTP_HOST']."/h5/constellation/detail/id/$constellation_id";
            $result['detail_url'] = $url;
            $result['date'] = $result['start_month'].'.'.$result['start_day'].'-'.$result['end_month'].'.'.$result['end_day'];
            unset($result['media_id'],$result['status'],$result['create_time']);
        }
        $this->to_back($result);
    }

    /**
     * @desc 获取星座视频列表
     */
    public function getVideoList(){
        $constellation_id = intval($this->params['constellation_id']);
        $m_constellationvideo = new \Common\Model\Smallapp\ConstellationvideoModel();
        $where = array('constellation_id'=>$constellation_id,'status'=>1);
        $orderby = 'sort desc,id desc';
        $res = $m_constellationvideo->getDataList('name,media_id',$where,$orderby);
        $result = array();
        if(!empty($res)){
            $m_media = new \Common\Model\MediaModel();
            foreach ($res as $v){
                $res_media = $m_media->getMediaInfoById($v['media_id']);
                $video_url = $res_media['oss_addr'];
                $video_img = $video_url.'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450';
                $result[] = array('name'=>$v['name'],'video_img'=>$video_img,'video_url'=>$video_url,'duration'=>$res_media['duration'],'resource_size'=>$res_media['oss_filesize']);
            }
        }
        $this->to_back($result);
    }


}
