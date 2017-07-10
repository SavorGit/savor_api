<?php
/**
 * @desc 内容接口
 * @author zhang.yingtao
 * @since  2017-07-06 
 */
namespace APP3\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
class ContentController extends CommonController{
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
                $this->valid_fields  = array('cateid'=>'1001');
                break;
            case 'picDetail':
                $this->is_verify = 1;
                $this->valid_fields = array('content_id'=>'1001');
                break;
        }
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
            $result[$key]['updateTime'] = date('Y.m.d',strtotime($v['updateTime']));
            if($v['logo']){
                $media_infos  = $m_media->getMediaInfoById($v['logo']);
                $result[$key]['logo'] = $media_infos['oss_addr'];
            }
           
            unset($result[$key]['content'],$result[$key]['contentUrl'],$result[$key]['videoUrl'],$result[$key]['imgUrl']);
        }
        $this->to_back($result);
    }
    /**
     * @desc 分类文章列表
     */
    public function getLastCategoryList(){
        $artModel = new \Common\Model\ArticleModel();
        $category_id = $this->params['cateid'];
        $crtime = $this->params['createTime'];
        $flag = $this->params['flag'];
        $size = $this->params['numPerPage'] ? $this->params['numPerPage'] :20;
        $start = $this->params['pageNum'] ? $this->params['pageNum'] : 1;
        
        $start  = ( $start-1 ) * $size;
        
        $orders = 'mco.id desc';
        $now = date("Y-m-d H:i:s",time());
        $where = '1=1';
        $where .= ' AND mco.state = 2  and  mcat.state=1 and mco.hot_category_id ='.$category_id. ' AND (((mco.bespeak=1 or mco.bespeak=2) AND mco.bespeak_time < "'.$now.'") or mco.bespeak=0)';
        $res = $artModel->getCapvideolist($where, $orders, $start, $size);

        $resu = $this->changeList($res);
        foreach($resu as $v){
            $ids[] = $v['id'];
        }
        if($resu){
            $data['list'] = $resu;
            $data['flag'] = implode(',', $ids);
            $data['minTime'] = $resu[0]['createTime'];
            $num = count($resu) -1;
            $data['maxTime'] = $resu[$num]['createTime'];
            if(!empty($flag)){
                $old_ids = explode(',', $flag);
            
                $dif_arr = array_diff($ids, $old_ids);
            
                $data['count'] = count($dif_arr);
            
            }
        }else{
            $data['list'] = $resu;
        }
        $this->to_back($data);
    }
    public function changeList($res){
        if($res){
            foreach ($res as $vk=>$val) {
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
                unset($res[$vk]['content']);
                $res[$vk]['createTime'] = strtotime($val['createTime']);
                foreach($val as $sk=>$sv){
                    if (empty($sv)) {
                        unset($res[$vk][$sk]);
                    }
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
        $m_mb_content = new \Common\Model\ContentModel();
        $m_mb_content->getPics($content_id);
    }
}