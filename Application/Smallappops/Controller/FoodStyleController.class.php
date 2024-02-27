<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;
class FoodStyleController extends CommonController{

    function _init_() {
        switch(ACTION_NAME) {
            case 'getList':
                $this->is_verify = 0;
                break;
            
        }
        parent::_init_();
    }

    public function getList(){
        $m_food_style = new \Common\Model\FoodStyleModel();
        $fields = 'id,name';
        $food_list = $m_food_style->getWhere($fields,array('status'=>1));
        $this->to_back($food_list);
    }
}