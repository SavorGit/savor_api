<?php
namespace APP3\Controller;

use \Common\Controller\BaseController as BaseController;
class RecommendController extends BaseController{
 	private $tvPlayRecommondNums;   //电视播放投屏底部推荐条数
 	private $picRecommondNums;      //图集推荐条数
 	private $imgTextRecommondNums;  //图文推荐条数
 	private $videoRecommondNums;     //视频推荐条数
 	/**
     * 构造函数
     */
    function _init_() {
        
        switch(ACTION_NAME) {
            case 'getRecommendInfo':
                $this->is_verify = 1;
                $this->valid_fields=array('articleId'=>'1001');
                break;
            case 'getTvPlayRecommend':
                $this->is_verify = 1;
                $this->valid_fields = array('articleId'=>1001,'sort_num'=>1001);
                break;
        }
        parent::_init_();
        $actionname  = strtolower(ACTION_NAME);          //方法名称
        $versionname = $this->traceinfo['versionname'];  //版本号
        if($actionname =='getrecommendinfo' && $versionname =='3.0'){
            $this->getRecommendInfo30();
        }
        $this->tvPlayRecommondNums = 5;
        $this->picRecommondNums    = 6;
        $this->imgTextRecommondNums= 5;
        $this->videoRecommondNums  = 5;
    }
    public function getRecommendInfo30(){
        $artid = $this->params['articleId'];
        //$artid = 2675;
        $arinfo = array();
        $res = array();
        $articleModel = new  \Common\Model\ArticleModel();
        $vinfo = $articleModel->where('id='.$artid)->find();
        if(empty($vinfo)){
            $this->to_back(18002);
        }
        if($vinfo['state']!=2){
            $this->to_back(18005);
        }
        
        if($vinfo['hot_category_id'] == 103 && $vinfo['type'] == 2){
            $data = array();
        }else{
            $arinfo = $this->judgeRecommendInfo30($vinfo);
            if($arinfo){
                foreach($arinfo as $dv){
                    $where = 'AND mc.id = '. $dv['id'];
                    $dap = $articleModel->getArtinfoById($where);
                    $res[] = $dap;
                }
                $data = $this->changRecList($res);
            }else{
                $data = array();
            }
        }
        $this->to_back($data);
    }
    public function getRecommendInfo(){
        $artid = $this->params['articleId'];
        //$artid = 2675;
        $arinfo = array();
        $res = array();
        $articleModel = new  \Common\Model\ArticleModel();
        $vinfo = $articleModel->where('id='.$artid)->find();
               
        if(empty($vinfo)){//如果该文章被物理删除  获取最新的5条文章 不包含专题文章
            //获取最新最新内容开始
            $res = $articleModel->getRecmmondList(' and hot_category_id !=103 and mc.state = 2 ',' mc.sort_num desc '," limit $this->imgTextRecommondNums");
            
            $data = $this->changRecList($res);
            $this->to_back($data);
            //获取最新最新内容结束
        }
        if($vinfo['hot_category_id'] == 103){
            $data = array();
        }else{
            $arinfo = $this->judgeRecommendInfo($vinfo);
            if($arinfo){
                foreach($arinfo as $dv){
                    $where = 'AND mc.id = '. $dv['id'];
                    $dap = $articleModel->getArtinfoById($where);
                    $res[] = $dap;
                }
                $data = $this->changRecList($res);
            }else{
                $data = array();
            }
        }
        $this->to_back($data);
    }

    public function combination($a, $m) {
        $r = array();

        $n = count($a);
        if ($m <= 0 || $m > $n) {
            return $r;
        }

        for ($i=0; $i<$n; $i++) {
            $t = array($a[$i]);
            if ($m == 1) {
                $r[] = $t;
            } else {
                $b = array_slice($a, $i+1);
                $c = $this->combination($b, $m-1);
                foreach ($c as $v) {
                    $r[] = array_merge($t, $v);
                }
            }
        }

        return $r;
    }
    public function judgeRecommendInfo30($vinfo){
        //推荐数
        //var_dump($vinfo);
        $mend_len = 5;
        $articleModel = new \Common\Model\ArticleModel();
        //获取推荐列表
        $order_tag = $vinfo['order_tag'];
        // var_dump($order_tag);
        $order_tag_arr = explode(',', $order_tag);
        $tag_len = count($order_tag_arr);
        if($tag_len == 0){
            $dap = array();
        }else{
            $where = "1=1 and state = 2  and hot_category_id = ".$vinfo['hot_category_id']." and type = ".$vinfo['type'];
            $field = 'id,title,order_tag';
            $dat = array();
            $dap = array();
            $data = array();
            for($i=$tag_len;$i>=1;$i--){
                $art = $this->combination($order_tag_arr, $i);
                foreach($art as $v){
                    $dat[] = $v;
                }
    
            }
    
            foreach($dat as $dk=>$dv) {
                $info = $articleModel->getRecommend($where, $field, $dv);
                foreach($info as $v){
                    if($v['id'] == $vinfo['id']){
                        continue;
                    }
                    if(!array_key_exists($v['id'], $dap)){
                        $dap[$v['id']] = $v;
                        $mend_len--;
                    }
    
                }
                if($mend_len <=0 ){
                    break;
                }
            }
            if($mend_len <=0 ){
                $dap = array_slice($dap, 0, 5);
            }
        }
    
        return $dap;
    }
    public function judgeRecommendInfo($vinfo){
        //推荐数
        //print_r($vinfo);
        $now_date = date('Y-m-d H:i:s',time());
        if($vinfo['type']==0 || $vinfo['type'] ==1){//纯文本、图文
            $mend_len = $this->imgTextRecommondNums;
        }else if($vinfo['type']==2){//图集
            $mend_len = $this->picRecommondNums;
        }else if($vinfo['type']==3){//视频
            $mend_len = $this->videoRecommondNums;
        }
        $articleModel = new \Common\Model\ArticleModel();
        //获取推荐列表
        
        //根据相同的文章类型的标签获取推荐 开始
        $order_tag = $vinfo['order_tag'];
        $order_tag_arr = explode(',', $order_tag);
        $tag_len = count($order_tag_arr);
        if($tag_len == 0 || empty($order_tag)){
            $dap = array();
        }else{
            $where = "1=1 and state = 2 and hot_category_id !=103  and type = ".$vinfo['type']." and bespeak_time<='".$now_date."'";
            $field = 'id,title,order_tag';
            $dat = array();
            $dap = array();
            $data = array();
            for($i=$tag_len;$i>=1;$i--){
                $art = $this->combination($order_tag_arr, $i);
                foreach($art as $v){
                    $dat[] = $v;
                }

            }
            $nums = 0;
            foreach($dat as $dk=>$dv) {
                $info = $articleModel->getRecommend($where, $field, $dv);
                foreach($info as $v){
                    if($v['id'] == $vinfo['id']){
                        continue;
                    }
                    if(!array_key_exists($v['id'], $dap)){
                        $dap[$v['id']] = $v;
                    }
                }
                $nums = count($dap);
                if($nums>=$mend_len){
                    break;
                }
            }
        }
        //根据相同的文章类型的标签获取推荐 结束
        //其他全分类查找推荐
        if($nums<$mend_len){
            if($tag_len){//如果该文章有标签
                $where = "1=1 and state = 2  and hot_category_id in(101,102)  and bespeak_time<='".$now_date."'";
                $field = 'id,title,order_tag';
                
                foreach($dat as $dk=>$dv) {
                    $info = $articleModel->getRecommend($where, $field, $dv);
                    foreach($info as $v){
                        if($v['id'] == $vinfo['id']){
                            continue;
                        }
                        if(!array_key_exists($v['id'], $dap)){
                            $dap[$v['id']] = $v;
                        }
                    }
                    $nums = count($dap);
                    if($nums>=$mend_len){
                        break;
                    }
                }
            }
        }
        //获取最新最新内容开始
        if($nums<$mend_len){
            $info = $articleModel->getList("hot_category_id != 103 and state =2 and bespeak_time<='".$now_date."'",' sort_num desc',0,10,'id,title,order_tag');
            foreach($info as $v){
                if($v['id'] == $vinfo['id']){
                    continue;
                }
                if(!array_key_exists($v['id'], $dap)){
                    $dap[$v['id']] = $v;
                }
            }  
        }
        //获取最新最新内容结束
        $dap = array_slice($dap, 0, $mend_len);
        return $dap;
    }

    public function changRecList($result){
        $rs = array();
        $mbpictModel = new \Common\Model\MbPicturesModel();
        $mediaModel  = new \Common\Model\MediaModel();
        //判断结果
        foreach($result as $key=>$v){
            foreach($v as $kk=> $vv){
                if(empty($vv)){
                    unset($result[$key][$kk]);
                }
            }
            $result[$key]['imageURL'] = $this->getOssAddr($v['imgUrl']) ;
            if(!empty($v['index_img_url'])){
                $result[$key]['indexImgUrl'] = $this->getOssAddr($v['index_img_url']) ;
            }

            $result[$key]['contentURL'] = $this->getContentUrl($v['contentUrl']);
            if($v['type'] == 2){
                //图集
                $info =  $mbpictModel->where('contentid='.$v['artid'])->find();
                $detail_arr = json_decode($info['detail'], true);
                /* foreach($detail_arr as $dk=> $dr){
                     $media_info = $mediaModel->getMediaInfoById($dr['aid']);
                     $detail_arr[$dk]['pic_url'] =$media_info['oss_addr'];
                     unset($detail_arr[$dk]['aid']);

                 }*/
                $result[$key]['colTuJi'] = count($detail_arr);

            }
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
            if($v['logo']){
                $result[$key]['logo'] = $this->getOssAddr($v['logo']);
            }
            $result[$key]['updateTime'] = date("Y-m-d",strtotime($result[$key]['updateTime']));
            unset($result[$key]['content'],$result[$key]['contentUrl'],$result[$key]['videoUrl'],$result[$key]['imgUrl'],$result[$key]['index_img_url']);
        }
        return $result;
    }
    public function getTvPlayRecommend(){
        $articleId = $this->params['articleId'];
        $sort_num = $this->params['sort_num'];
        $m_home = new \Common\Model\HomeModel();
        $map['content_id'] = $articleId;
        $map['state'] =1;
        $nums = $m_home->where($map)->count();
        
        if(empty($nums)){
            $this->to_back(19002);
        }
        $m_home = new \Common\Model\HomeModel();
        $data = $remainInfos =array();
        $count = $m_home->getAllDemandListNums();
        
        if(empty($count) || $count ==1){
            $this->to_back($data);
        }else if($count>1 && $count<6){
            $data = $m_home->getAllDemandList($order='mh.sort_num asc',$limit = " limit $this->tvPlayRecommondNums");
            
        }else if($count>=6){
            $where = ' and mh.sort_num>'.$sort_num;
            $data = $m_home->getRecmmondDemand($where,$order='mh.sort_num asc'," limit $this->tvPlayRecommondNums");
            $num = count($data);
            
            if($num<$this->tvPlayRecommondNums){
                $remainNum = $this->tvPlayRecommondNums-$num;
                $remainInfos = $m_home->getAllDemandList($order,$limit = " limit $remainNum");
            }
        }
        $data =  array_merge($data,$remainInfos);
        $m_media = new \Common\Model\MediaModel();
        foreach($data as $key=>$v){
            foreach($v as $kk=> $vv){
                if(empty($vv)){
                    unset($data[$key][$kk]);
                }
            }
            $data[$key]['imageURL'] = $this->getOssAddr($v['imgUrl']) ;
            $data[$key]['contentURL'] = $this->getContentUrl($v['contentUrl']);
            if(!empty($v['videoUrl'])) $data[$key]['videoURL']   = substr($v['videoUrl'],0,strpos($v['videoUrl'], '.f')) ;
            if($v['type'] ==3){
                if(empty($v['name'])){
                    unset($data[$key]['name']);
                }else{
                    $ttp = explode('/', $v['name']);
                    $data[$key]['name'] = $ttp[2];
                }
            }
            if($v['type'] ==3 && empty($v['content'])){
                $data[$key]['type'] = 4;
            }
            $data[$key]['createTime'] = strtotime($v['createTime']);
            $data[$key]['updateTime'] = date('Y-m-d',strtotime($v['createTime']));   //用updateTime作为文章创建时间
            if($v['logo']){
                $media_infos  = $m_media->getMediaInfoById($v['logo']);
                $result[$key]['logo'] = $media_infos['oss_addr'];
            }
             
            unset($data[$key]['content'],$data[$key]['contentUrl'],$data[$key]['videoUrl'],$data[$key]['imgUrl']);
        }
        $this->to_back($data);   
    }
    /**
     * @desc 获取非正常文章的推荐列表
     */
    public function getAbnorRecommend(){
        $aritcleId = $this->params['articleId'];
        $m_content =  new \Common\Model\ContentModel();
        $vinfo = $m_content->getInfoById('id,title,order_tag',$aritcleId);
        if(!empty($vinfo) && !empty($vinfo['order_tag'])){//如果该文章还存在 并且有标签
            
        }else {//如果该文章存在但没有标签 或者被物理删除
            
        }
    }
}

