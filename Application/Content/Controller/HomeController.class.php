<?php
namespace Content\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class HomeController extends BaseController{
 	/**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getLastVodList':
                $this->is_verify = 0;
                break;
            case 'getVodList':
                $this->is_verify = 0;
                break;
            case 'getLastHotelList':
                $this->valid_fields=array('hotelId'=>'1001');
                $this->is_verify = 1;
                break;
            case 'getHotelList':
                $this->valid_fields=array('hotelId'=>'1001','createTime'=>'1000');
                $this->is_verify = 1;
                break;

        }
        parent::_init_();
    }

    public function changeList($res){
        if($res){
            foreach ($res as $vk=>$val) {
                $res[$vk]['imageURL'] = $this->getOssAddr($val['imageURL']);
                foreach($val as $sk=>$sv){
                    if (empty($sv)) {
                        unset($res[$vk][$sk]);
                    }
                }
                if(!empty($val['name'])){
                    $ttp = explode('/', $val['name']);
                    $res[$vk]['name'] = $ttp[2];
                }
            }

        }
        return $res;
        //如果是空
    }

    /**
     * @desc 非酒店环境下拉
     */
    public function getLastVodList(){
        //$createTime = $this->params['createTime'];
        $flag = $this->params['flag'];
        $m_mb_content = new \Common\Model\ContentModel();
        $result = $m_mb_content->getVodList('',1);
       
        $data = array();
        foreach($result as $key=>$v){
            foreach($v as $kk=> $vv){
                if(empty($vv)){
                    unset($result[$key][$kk]);
                }
            }
            $result[$key]['imageURL'] = $this->getOssAddr($v['imgUrl']) ;
            $result[$key]['contentURL'] = $this->getContentUrl($v['contentUrl']);
            if(!empty($v['videoUrl'])) $result[$key]['videoURL']   = substr($v['videoUrl'],0,strpos($v['videoUrl'], '.f')) ;
            if($v['type'] ==3){
                if(empty($v['name'])){
                    unset($result[$key]['name']);
                }else{
                    $ttp = explode('/', $v['name']);
                    $result[$key]['name'] = $ttp[2];
                }
            }
            if($v['type'] ==3 && empty($v['content'])){
                $result[$key]['type'] = 4;
            }
            $result[$key]['createTime'] = strtotime($v['createTime']);

            /* if(!empty($createTime)){
                $str_create_time = strtotime($v['createTime']);

            } */
            $ids[] = $v['id'];
            unset($result[$key]['content'],$result[$key]['contentUrl'],$result[$key]['videoUrl'],$result[$key]['imgUrl']);
        }
        if($result){
            $num = count($result) -1 ;
            $data['list'] = $result;
            $data['time'] = $result[$num]['sort_num'];
            $data['minTime'] = $result[0]['createTime'];
            
            $data['maxTime'] = $result[$num]['sort_num'];
            if(!empty($flag)){
                $old_ids = explode(',', $flag);
                $update_info = array_diff($ids, $old_ids);
                $data['count'] = count($update_info);
                
            }
            $data['flag'] = implode(',', $ids);
        }

        $this->to_back($data);
    }

    /**
     * @desc 酒店环境下拉
     */
    public function getLastHotelList(){

        $m_mb_content = new \Common\Model\ContentModel();
        //$createTime = $this->params['createTime'];
        $hotel_id = $this->params['hotelId'];
        $flag = $this->params['flag'];
        $ads_arr = $m_mb_content->getHotelList($hotel_id);
        $ads_arr = $this->changeList($ads_arr);
        $data = array();
        if($ads_arr){
            $data['adsList'] = $ads_arr;
        }
        $result = $m_mb_content->getVodList('',1,20,1);
        foreach($result as $key=>$v){
            foreach($v as $kk=> $vv){
                if(empty($vv)){
                    unset($result[$key][$kk]);
                }
            }
            $result[$key]['imageURL'] = $this->getOssAddr($v['imgUrl']) ;
            $result[$key]['contentURL'] = $this->getContentUrl($v['contentUrl']);
            if(!empty($v['videoUrl'])) $result[$key]['videoURL']   = substr($v['videoUrl'],0,strpos($v['videoUrl'], '.f')) ;
            if($v['type'] ==3){
                if(empty($v['name'])){
                    unset($result[$key]['name']);
                }else{
                    $ttp = explode('/', $v['name']);
                    $result[$key]['name'] = $ttp[2];
                }
            }
            if($v['type'] ==3 && empty($v['content'])){
                $result[$key]['type'] = 4;
            }
            $result[$key]['createTime'] = strtotime($v['createTime']);
            
            $ids[] = $v['id'];
            unset($result[$key]['content'],$result[$key]['contentUrl'],$result[$key]['videoUrl'],$result[$key]['imgUrl']);
        }
        if($result){
            $num = count($result) -1 ;
            $data['vodList'] = $result;
            $data['time'] = $result[$num]['sort_num'];
            $data['minTime'] = $result[0]['createTime'];
            
            $data['maxTime'] = $result[$num]['sort_num'];
            $m_hotel = new \Common\Model\HotelModel(); 
            $hotel_info = $m_hotel->getOneById('name', $hotel_id);
            $data['hotelName'] = $hotel_info['name'];
            if(!empty($flag)){
                $old_ids = explode(',', $flag);
                $update_info = array_diff($ids, $old_ids);
                $data['count'] = count($update_info);
            
            }
            $data['flag'] = implode(',', $ids);
           
        }
        $this->to_back($data);
    }

    /**
     * @desc 酒店环境上拉
     */
    public function getHotelList(){
        $limit = 10;
        $m_mb_content = new \Common\Model\ContentModel();
        $createTime = $this->params['createTime'];
        $hotel_id = $this->params['hotelId'];
        $flag = $this->params['flag'];
        $ads_arr = $m_mb_content->getHotelList($hotel_id);
        $ads_arr = $this->changeList($ads_arr);
        $data = array();
        if($ads_arr){
            $data['adsList'] = $ads_arr;
        }
        $result = $m_mb_content->getVodList($createTime,2,$limit,$env=1);
        foreach($result as $key=>$v){
            foreach($v as $kk=> $vv){
                if(empty($vv)){
                    unset($result[$key][$kk]);
                }
            }
            $result[$key]['imageURL'] = $this->getOssAddr($v['imgUrl']) ;
            $result[$key]['contentURL'] = $this->getContentUrl($v['contentUrl']);
            if(!empty($v['videoUrl'])) $result[$key]['videoURL']   = substr($v['videoUrl'],0,strpos($v['videoUrl'], '.f')) ;
            if($v['type'] ==3){
                if(empty($v['name'])){
                    unset($result[$key]['name']);
                }else{
                    $ttp = explode('/', $v['name']);
                    $result[$key]['name'] = $ttp[2];
                }
            }
            if($v['type'] ==3 && empty(trim($v['content']))){
                $result[$key]['type'] = 4;
            }
            $result[$key]['createTime'] = strtotime($v['createTime']);
            unset($result[$key]['content'],$result[$key]['contentUrl'],$result[$key]['videoUrl'],$result[$key]['imgUrl']);
        }




        if($result){
            $data['vodList'] = $result;
            $data['time'] = $result[0]['id'];
            $num = count($result) -1;
            $data['maxTime'] = $result[$num]['sort_num'];
        }
        $this->to_back($data);
    }

    /**
     * @desc 非酒店环境上拉
     */
    public function getVodList(){
        $limit = 10;
        $createTime = $this->params['createTime'];
        $m_mb_content = new \Common\Model\ContentModel();
        $result = $m_mb_content->getVodList($createTime,2,$limit);
        //print_r($result);exit;
        $data = array();
        foreach($result as $key=>$v){
            foreach($v as $kk=> $vv){
                if(empty($vv)){
                    unset($result[$key][$kk]);
                }
            }
            $result[$key]['imageURL'] = $this->getOssAddr($v['imgUrl']) ;
            $result[$key]['contentURL'] = $this->getContentUrl($v['contentUrl']);
            if(!empty($v['videoUrl'])) $result[$key]['videoURL']   = substr($v['videoUrl'],0,strpos($v['videoUrl'], '.f')) ;
            if($v['type'] ==3 && empty(trim($v['content']))){
                $result[$key]['type'] = 4;
            }
            $result[$key]['createTime'] = strtotime($v['createTime']);
            unset($result[$key]['content'],$result[$key]['contentUrl'],$result[$key]['videoUrl'],$result[$key]['imgUrl']);
        }
        if($result){
            $data['list'] = $result;
            $data['time'] = $result[0]['id'];
            $num = count($result) -1;
            $data['maxTime'] = $result[$num]['sort_num'];
        }
        $this->to_back($data);
    }
}