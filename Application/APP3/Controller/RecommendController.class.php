<?php
namespace APP3\Controller;

use \Common\Controller\BaseController as BaseController;
class RecommendController extends BaseController{
 	/**
     * 构造函数
     */
    function _init_() {
        $this->valid_fields=array('articleId'=>'1001');
        switch(ACTION_NAME) {
            case 'getRecommendInfo':
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function getRecommendInfo(){
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

    public function judgeRecommendInfo($vinfo){
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
                $info =  $mbpictModel->where('contentid='.$v['id'])->find();
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
            $result[$key]['updateTime'] = date("Y-m-d",strtotime($result[$key]['updateTime']));
            unset($result[$key]['content'],$result[$key]['contentUrl'],$result[$key]['videoUrl'],$result[$key]['imgUrl'],$result[$key]['index_img_url']);
        }
        return $result;
    }



}

