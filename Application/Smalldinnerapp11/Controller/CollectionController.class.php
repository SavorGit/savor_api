<?php
namespace Smalldinnerapp11\Controller;
use \Common\Controller\CommonController as CommonController;

class CollectionController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addGoodscollection':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'openid'=>1001,'phone'=>1001);
                break;
        }
        parent::_init_();
    }

    public function addGoodscollection(){
        $goods_id= intval($this->params['goods_id']);
        $openid = $this->params['openid'];
        $phone = $this->params['phone'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if($res_goods['status']!=2){
            $this->to_back(92020);
        }

        $m_goodscollection = new \Common\Model\Smallapp\GoodscollectionModel();
        $add_data = array('openid'=>$openid,'goods_id'=>$goods_id,'phone'=>$phone);
        $m_goodscollection->add($add_data);
        $res_data = array('message'=>'收藏成功');
        $this->to_back($res_data);
    }



}