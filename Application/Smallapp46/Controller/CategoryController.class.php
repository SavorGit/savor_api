<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class CategoryController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'categorylist':
                $this->is_verify =1;
                $this->valid_fields = array('type'=>1001,'cate_id'=>1002);
                break;
        }
        parent::_init_();
    }

    public function categorylist(){
        $type = $this->params['type'];//7商城 8本地生活 101菜系 102人均价格
        $cate_id = intval($this->params['cate_id']);

        $m_category = new \Common\Model\Smallapp\CategoryModel();
        switch ($type){
            case 7:
                $where = array('type'=>$type,'status'=>1,'level'=>1);
                $res_category = $m_category->getDataList('id,name',$where,'sort desc,id desc');
                $default_cate = array('id'=>0,'name'=>'全部');
                break;
            case 8:
                if($cate_id){
                    $where = array('type'=>$type,'parent_id'=>$cate_id,'level'=>2);
                    $res_category = $m_category->getDataList('id,name',$where,'sort desc,id desc');
                    $default_cate = array('id'=>$cate_id,'name'=>'全部');
                }else{
                    $where = array('type'=>$type,'parent_id'=>0,'level'=>1);
                    $res_data = $m_category->getDataList('id,name,media_id',$where,'sort desc,id desc');
                    $res_category = array();
                    $m_media = new \Common\Model\MediaModel();
                    foreach ($res_data as $v){
                        $icon = '';
                        if(!empty($v['media_id'])){
                            $res_media = $m_media->getMediaInfoById($v['media_id']);
                            $icon = $res_media['oss_addr'];
                        }
                        $res_category[]=array('id'=>$v['id'],'name'=>$v['name'],'icon'=>$icon);
                    }
                    $default_cate = array();
                }
                break;
            case 101:
                $m_food_style = new \Common\Model\FoodStyleModel();
                $fields = "id,name";
                $where = array('status'=>1);
                $res_category = $m_food_style->getWhere($fields, $where);
                $default_cate = array('id'=>0,'name'=>'全部');
                break;
            case 102:
                $person_prices = C('PERSON_PRICE');
                $res_category = array();
                foreach ($person_prices as $v){
                    $res_category[] = array('id'=>$v['id'],'name'=>$v['name']);
                }
                $default_cate = array('id'=>0,'name'=>'全部');
                break;
            default:
                $where = array('type'=>7,'status'=>1,'level'=>1);
                $res_category = $m_category->getDataList('id,name',$where,'sort desc,id desc');
                $default_cate = array('id'=>0,'name'=>'全部');
        }
        if(!empty($default_cate)){
            array_unshift($res_category,$default_cate);
        }
        $category_name_list = array();
        foreach ($res_category as $v){
            $category_name_list[]=$v['name'];
        }
	    $data = array('category_list'=>$res_category,'category_name_list'=>$category_name_list);
        $this->to_back($data);
    }


}
