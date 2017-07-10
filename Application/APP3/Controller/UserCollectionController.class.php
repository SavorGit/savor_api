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


    /**
     * @desc 下拉二十条
     */
    public function getLastCollectoinList(){
        $usecModel = new \Common\Model\UserCollectionModel();
        $traceinfo = $this->traceinfo;
        $deviceid = $traceinfo['deviceid'];
        if(empty($save['device_id'])){
            $data = 18001;
        }
        $result = $usecModel->getCollecitonList($deviceid, '',1);
        $data = array();
        //判断结果
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
}

