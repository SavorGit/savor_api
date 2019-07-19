<?php
namespace Smalldinnerapp11\Controller;
use \Common\Controller\CommonController as CommonController;

class GoodsController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getGoodslist':
                $this->is_verify = 1;
                $this->valid_fields = array('offset'=>1001,'pagesize'=>1001,'type'=>1001,'hotel_id'=>1001);
        }
        parent::_init_();
    }

    public function getGoodslist(){
        /*
         * todo 待修改
         */

        $offset = intval($this->params['offset']);
        $pagesize = intval($this->params['pagesize']);
        $type = $this->params['type'];//10官方活动促销,20我的活动
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $fields = 'id as goods_id,name,img_addr,video_addr,price,rebate_integral,jd_url,type';
        $where = array('type'=>$type);
        $orderby = 'id desc';
        $res_goods = $m_goods->getDataList($fields,$where,$orderby,$offset,$pagesize);
        $datalist = $res_goods['list'];
        $oss_host = 'http://'.C('OSS_HOST').'/';
        foreach ($datalist as $k=>$v){
            $datalist[$k]['img_addr'] = $v['img_addr'];
            $datalist[$k]['img_addrurl'] = $oss_host.$v['img_addr'];
            $datalist[$k]['video_addr'] = $v['video_addr'];
            $datalist[$k]['video_addrurl'] = $oss_host.$v['video_addr'];
        }


        if ($offset + $pagesize > $res_goods['total']) {
            $offset = $res_goods['total'];
        } else {
            $offset += $pagesize;
        }
        $data = array('offset'=>intval($offset),'pagesize'=>$pagesize,'datalist'=>$datalist,'total'=>intval($res_goods['total']));
        $this->to_back($data);
    }
}