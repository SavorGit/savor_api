<?php
namespace Smallsale19\Controller;
use \Common\Controller\CommonController as CommonController;

class CategoryController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'categorylist':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }

    public function categorylist(){
        $m_category = new \Common\Model\Smallapp\CategoryModel();
        $where = array('type'=>7,'status'=>1,'level'=>1);
        $res_category = $m_category->getDataList('id,name',$where,'sort desc,id desc');
        $category_name_list = array('全部');
        foreach ($res_category as $v){
            $category_name_list[]=$v['name'];
        }
        array_unshift($res_category,array('id'=>0,'name'=>'全部'));
        $data = array('category_list'=>$res_category,'category_name_list'=>$category_name_list);
        $this->to_back($data);
    }


}