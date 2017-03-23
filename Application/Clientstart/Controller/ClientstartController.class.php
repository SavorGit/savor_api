<?php
namespace Clientstart\Controller;

use \Common\Controller\BaseController as BaseController;
class ClientstartController extends BaseController{
 	/**
     * 构造函数
     */
    function _init_() {

        $this->valid_fields=array('ctype'=>'1001');
        switch(ACTION_NAME) {
            case 'getInfo':
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取分类列表
     */
    public function getInfo(){

        $mediaModel = new \Common\Model\MediaModel();
        $type = $this->params['ctype'];
        if ( in_array($type, array(3,4)) ){
            $csModel = new \Common\Model\ClientstartModel();
            $dat = array('ctype'=>$type);
            $field = 'id,status,duration,media_id,img_id';
            $info = $csModel->getOne($dat,$field);
            if( $info['status'] == 1 ) {
                $meid = $info['img_id'];
            } else if( $info['status'] == 2 ) {
                $meid = $info['media_id'];
            }
            unset($info['img_id'],$info['media_id']);
            $m_info = $mediaModel->getMediaInfoById($meid);
            $info['url'] = $m_info['oss_addr'];
            $data = $info;
        } else {
            $data = '13001';
        }
        $this->to_back($data);
    }
}