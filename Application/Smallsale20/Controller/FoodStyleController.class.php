<?php
namespace Smallsale20\Controller;
use \Common\Controller\CommonController as CommonController;
class FoodStyleController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getList':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取菜系列表
     */
    public function getList(){
        $m_food_style = new \Common\Model\FoodStyleModel();
        $fields = "id,name";
        $where['status'] = 1;
        
        $food_list = $m_food_style->getWhere($fields, $where);
        $food_name_list = array();
        $tmp = array('id'=>0,'name'=>'请选择');
        array_unshift($food_list, $tmp);
        foreach($food_list as $key=>$v){
            $food_name_list[] = $v['name'];
        }
        $data['food_list'] = $food_list;
        $data['food_name_list'] = $food_name_list;
        $this->to_back($data); 
    }
}