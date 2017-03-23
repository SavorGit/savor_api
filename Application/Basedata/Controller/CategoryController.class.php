<?php
namespace BaseData\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class CategoryController extends BaseController{
 	/**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getCategoryList':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取分类列表
     */
    public function getCategoryList(){

        $m_category = new \Common\Model\Basedata\CategoryModel();
      //  var_dump($this->params);
        //var_dump($this->traceinfo);
        //var_dump($_SERVER);

        $data = $m_category->getAllCategory();
        
        $this->to_back($data);
    }
}