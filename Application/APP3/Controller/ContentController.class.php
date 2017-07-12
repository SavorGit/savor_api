<?php
/**
 * @desc 内容接口
 * @author zhang.yingtao
 * @since  2017-07-06 
 */
namespace APP3\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class ContentController extends BaseController{
    var $cateArr;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'demandList':
                $this->is_verify = 1;
                $this->valid_fields=array('hotelId'=>'1001');
                break;
            case 'getLastCategoryList':
                $this->is_verify = 1;
                $this->valid_fields  = array('cateid'=>'1001','cateid'=>'1000');
                break;
            case 'picDetail':
                $this->is_verify = 1;
                $this->valid_fields = array('content_id'=>'1001');
                break;
        }
        $this->cateArr = array(1,2);  //1：创富 2：生活
        parent::_init_();
    }
    /**
     * @desc 投屏点播
     */
    public function demandList(){
        $hotel_id = $this->params['hotelId'];
        $m_mb_home = new \Common\Model\HomeModel();
        $result = $m_mb_home->getAllDemandList(); //获取所有投屏点播内容
        $m_media = new \Common\Model\MediaModel();
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
            $result[$key]['updateTime'] = date('Y-m-d',strtotime($v['updateTime']));
            if($v['logo']){
                $media_infos  = $m_media->getMediaInfoById($v['logo']);
                $result[$key]['logo'] = $media_infos['oss_addr'];
            }
           
            unset($result[$key]['content'],$result[$key]['contentUrl'],$result[$key]['videoUrl'],$result[$key]['imgUrl']);
        }
        $this->to_back($result);
    }
    /**
     * @desc 创富、生活分类文章列表
     */
    public function getLastCategoryList(){
        
        $artModel = new \Common\Model\ArticleModel();
        $category_id = $this->params['cateid'];  //分类id
        if(!in_array($category_id, $this->cateArr)){
            $this->to_back('19001');
        }
        $sort_num = $this->params['sort_num'];
        $size = $this->params['numPerPage'] ? $this->params['numPerPage'] :20;
        
        $orders = 'mco.sort_num desc';
        $now = date("Y-m-d H:i:s",time());
        $where = '1=1';
        $where .= ' AND mco.state = 2   and mco.hot_category_id ='.$category_id. ' AND (((mco.bespeak=1 or mco.bespeak=2) AND mco.bespeak_time < "'.$now.'") or mco.bespeak=0)';
        if($sort_num){
            $where .=" and mco.sort_num<$sort_num ";
        }
        
        
        $res = $artModel->getCateList($where, $orders,$size);
        $resu = $this->changeList($res);
        
       
        $data = $resu;
        $this->to_back($data);
    }
    private function changeList($res){
        $deviceid = $this->traceinfo['deviceid'];
       
        if($res){
            $m_media = new \Common\Model\MediaModel();
            $m_Content = new \Common\Model\ContentModel();
            $m_picturs = new \Common\Model\PicturesModel();
            $m_user_collection = new \Common\Model\UserCollectionModel();
            foreach ($res as $vk=>$val) {
                if($vk ==0){
                    $infos = $m_Content->getInfoById('index_img_url',$val['artid']);
                    
                    $res[$vk]['indexImageUrl'] = $this->getOssAddr($infos['index_img_url']);
                    
                }
                if($val['logo']){
                    $logoMediainfo = $m_media->getMediaInfoById($val['logo']);
                    $res[$vk]['logo'] = $logoMediainfo['oss_addr'];
                }
                
                $res[$vk]['contentURL'] = $this->getContentUrl($val['contentURL']);
                $res[$vk]['imageURL'] = $this->getOssAddr($val['imageURL']);
                $res[$vk]['videoURL'] = substr($val['videoURL'], 0, strpos($val['videoURL'],'.f'));
                $len = count($val['name']);
                if($len != 0) {
                    $res[$vk]['canplay'] = 1;
                    $res[$vk]['name'] = substr($val['name'], strripos($val['name'],'/')+1);
                }
                if($val['type']==3){
                    if(empty($val['content'])){
                        $res[$vk]['type'] = 4;
                    }
                }
                if($val['type']==2){
                    $res[$vk]['colTuJi'] = $m_picturs->getCountPics($val['artid']);
                    unset($res[$vk]['contentURL']);
                }
                unset($res[$vk]['content']);
                
                
                $res[$vk]['updateTime'] = date('Y-m-d',strtotime($val['updateTime']));
                foreach($val as $sk=>$sv){
                    if (empty($sv)) {
                        unset($res[$vk][$sk]);
                    }
                }
                $collect_info = $m_user_collection->getOne(array('device_id'=>$deviceid,'artid'=>$val['artid'],'state'=>'1'));
                if(!empty($collect_info)){
                    $res[$vk]['collected'] = 1;
                }else {
                    $res[$vk]['collected'] = 0;
                }
            }
        }
        return $res;
        //如果是空
    }
    /**
     * @desc 图集
     */
    public function picDetail(){
        $content_id = $this->params['content_id'];   //文章id
        $m_mb_pictures = new \Common\Model\PicturesModel();
        $info = $m_mb_pictures->getPicsByContentId($content_id);
        $info =  $info['detail'];
        $m_media = new \Common\Model\MediaModel();
        if(!empty($info)){
            
            $info= json_decode($info,true);
            foreach($info as $key=>$v){
                unset($info[$key]['aid']);
                $media_info = $m_media->getMediaInfoById($v['aid']);
                $info[$key]['pic_url'] = $media_info['oss_addr'];
            }
        }
        $this->to_back($info);
    }
}