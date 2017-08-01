<?php
namespace APP3\Controller;

use \Common\Controller\BaseController as BaseController;
class UserCollectionController extends BaseController{
 	/**
     * 构造函数
     */
    function _init_() {
        $this->valid_fields=array('articleId'=>'1001');
        switch(ACTION_NAME) {
            case 'addMyCollection':
                $this->is_verify = 1;
                break;
            case 'getLastCollectoinList':
                $this->is_verify = 0;
                break;
            case 'getCollectoinState':
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }


    public function getCollectoinState(){
        $usecModel = new \Common\Model\UserCollectionModel();
        $traceinfo = $this->traceinfo;
        $deviceid = $traceinfo['deviceid'];
        $data = array();
        if(empty($deviceid)){
            $data = 18001;
        }else {
            $map['device_id'] = $deviceid;
            $map['artid'] = $this->params['articleId'];
            $result = $usecModel->getOne($map);
            if($result){
                $data['state'] = $result['state'];
            }else{
               $data['state'] = 0;
            }

        }
        $this->to_back($data);
    }
    /**
     * @desc 收藏取肖收藏
     */
    public function addMyCollection(){
        $d_time = date("Y-m-d H:i:s");
        $dat = array();
        $dp = array();
        $save = array();
        $traceinfo = $this->traceinfo;
        $save['device_id'] = $traceinfo['deviceid'];
        $save['artid'] = $this->params['articleId'];
        $ucolModel = new \Common\Model\UserCollectionModel();
        if(empty($save['device_id'])){
            $data = 18001;
        }else{
            if(!intval($save['artid'])>0){
                $data = 18002;
            }else{
                //判断是否存在
                $info = $ucolModel->getOne($save);
                $save['state'] = empty($this->params['state'])?0:$this->params['state'];
                if ($info) {
                    $where = ' id='.$info['id'];
                    $dat['state'] = $save['state'];
                    $dat['update_time'] = $d_time;
                    $bool = $ucolModel->saveData($dat, $where);
                    if($bool) {
                        $data = 10000;
                    } else {
                        $data = 18004;
                    }
                } else{
                    $save['create_time'] = $d_time;
                    $save['update_time'] = $d_time;
                    $bool = $ucolModel->addData($save);
                    if($bool) {
                        $data = 10000;
                    } else {
                        $data = 18003;
                    }
                }

            }
        }
        $this->to_back($data);
    }

    public function changColList($result){
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
            $result[$key]['ucreateTime'] = date('Y-m-d',strtotime($v['ucreateTime']));
            
            $ids[] = $v['colid'];
            unset($result[$key]['content'],$result[$key]['contentUrl'],$result[$key]['videoUrl'],$result[$key]['imgUrl'],$result[$key]['index_img_url']);
        }
        $rs['list'] = $result;
        return $rs;
    }


    /**
     * @desc 上下拉二十条
     */
    public function getLastCollectoinList(){
        $usecModel = new \Common\Model\UserCollectionModel();
        $createTime = $this->params['createTime'];
        if(empty($createTime)){
            $createTime = '';
            $type = 1;
        }else{
            $createTime = date("Y-m-d H:i:s", $createTime);
            $type = 2;
        }
        $traceinfo = $this->traceinfo;
        $deviceid = $traceinfo['deviceid'];
        if(empty($deviceid)){
            $data = 18001;
        }else{
            $result = $usecModel->getCollecitonList($deviceid, $createTime,$type);
            $res = $this->changColList($result);

            if($result){
                $count = count($result);
                if($count<20){
                    $nextPage = 0;
                }else{
                    $where = '1=1';
                    $where .= " and ucl.device_id = '".$deviceid."' and ucl.state= 1 and mc.state=2 ";
                    $order  = ' ucl.create_time asc';
                    $info = $usecModel->alias('ucl') 
                                      ->join(' savor_mb_content mc on ucl.artid = mc.id')
                                      ->join('savor_article_source as on mc.source_id =as.id ')
                                      ->where($where)->order($order)->limit(1)->find();
                    $art_num_get = $info['artid'];
                    //获取传过去最后一条
                    $art_pass_last = $result[$count-1]['artid'];
                    if($art_pass_last == $art_num_get){
                        $nextPage = 0;
                    }else{
                        $nextPage = 1;
                    }

                }
                $data['list'] = $res['list'];
                $data['nextpage'] = $nextPage;
            }else{
                $data = array();
            }
        }
        $this->to_back($data);
    }




}

