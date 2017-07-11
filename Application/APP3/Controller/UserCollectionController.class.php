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
        }
        parent::_init_();
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
                    $save['create_time'] = $d_time;
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
            $result[$key]['ucreateTime'] = strtotime($v['ucreateTime']);
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
            if($res){
                $data['list'] = $res['list'];
            }
        }
        $this->to_back($data);
    }




}

