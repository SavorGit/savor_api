<?php
namespace Smallsale16\Controller;
use \Common\Controller\CommonController as CommonController;

class ConfigController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getConfig':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getConfig(){
        $hotel_id = intval($this->params['hotel_id']);

        $is_have_adv = 0;
        $m_ads = new \Common\Model\AdsModel();
        $ads_where = array('hotel_id'=>$hotel_id,'state'=>1,'is_online'=>1,'type'=>3);
        $res_ads = $m_ads->getWhere($ads_where, 'id,media_id');
        if(!empty($res_ads)){
            $is_have_adv = 1;
        }
        $res_data = array('is_have_adv'=>$is_have_adv);
        $this->to_back($res_data);
    }




}