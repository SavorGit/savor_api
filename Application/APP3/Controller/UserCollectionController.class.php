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
        }
        parent::_init_();
    }
    /**
     * @desc 获取分类列表
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
}

