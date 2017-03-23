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
        }
        parent::_init_();
    }
    /**
     * @desc 非酒店环境下拉
     */
    public function getLastVodList(){
        $createTime = $this->params['createTime'];
        $flag = $this->params['flag'];
        //$createTime = strtotime($createTime);
        $m_mb_content = new \Common\Model\ContentModel();
        $result = $m_mb_content->getVodList($createTime,1);
        //print_r($result);exit;
        $data = array();
        foreach($result as $key=>$v){
            foreach($v as $kk=> $vv){
                if(empty($vv)){
                    unset($result[$key][$kk]);
                }
            }
            $result[$key]['imgUrl'] = $this->getOssAddr($v['imgUrl']) ;
            $result[$key]['contentUrl'] = $this->getContentUrl($v['contentUrl']);
            if(!empty($v['videoUrl'])) $result[$key]['videoUrl']   = substr($v['videoUrl'],0,strpos($v['videoUrl'], '.f')) ; 
            if($v['type'] ==3 && empty($v['content'])){
                $result[$key]['type'] = 4;
            }
            $result[$key]['createTime'] = strtotime($v['createTime']);
            
            $ids[] = $v['id'];
            unset($result[$key]['content']);
        }
        if($result){
            $data['list'] = $result;
            $data['time'] = $result[0]['id'];
            $data['minTime'] = $result[0]['createTime'];
            $data['flag'] = implode(',', $ids);
            if(!empty($flag)){
                $old_ids = explode($flag, ',');
                $update_info = array_diff($ids, $old_ids);
                $data['count'] = count($update_info);
            }
        }
        
        $this->to_back($data);
    }
    /**
     * @desc 酒店环境上拉
     */
    public function getVodList(){
        $createTime = $this->params['createTime'];
        //$createTime = strtotime($createTime);
        $m_mb_content = new \Common\Model\ContentModel();
        $result = $m_mb_content->getVodList($createTime,2);
        //print_r($result);exit;
        $data = array();
        foreach($result as $key=>$v){
            foreach($v as $kk=> $vv){
                if(empty($vv)){
                    unset($result[$key][$kk]);
                }
            }
            $result[$key]['imgUrl'] = $this->getOssAddr($v['imgUrl']) ;
            $result[$key]['contentUrl'] = $this->getContentUrl($v['contentUrl']);
            if(!empty($v['videoUrl'])) $result[$key]['videoUrl']   = substr($v['videoUrl'],0,strpos($v['videoUrl'], '.f')) ;
            if($v['type'] ==3 && empty($v['content'])){
                $result[$key]['type'] = 4;
            }
            $result[$key]['createTime'] = strtotime($v['createTime']);
            unset($result[$key]['content']);
        }
        if($result){
            $data['list'] = $result;
            $data['time'] = $result[0]['id'];
            $data['minTime'] = $result[0]['createTime'];
        }
        $this->to_back($data);
    }
}