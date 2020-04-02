<?php
namespace Smallsale19\Controller;
use \Common\Controller\CommonController as CommonController;

class ShopController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'categorylist':
                $this->is_verify = 0;
                break;
            case 'goods':
                $this->valid_fields = array('openid'=>1001,'category_id'=>1002,'keywords'=>1002,'page'=>1001);
                $this->is_verify = 1;
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

    public function goods(){
        $openid = $this->params['openid'];
        $keywords = !empty($this->params['keywords'])?trim($this->params['keywords']):'';
        $category_id = intval($this->params['category_id']);
        $page = intval($this->params['page']);
        $pagesize = 10;
        $all_nums = $page * $pagesize;

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }

        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $where = array('type'=>22,'status'=>1,'flag'=>2);
        if($category_id){
            $where['category_id'] = $category_id;
        }
        if(!empty($keywords)){
            $where['name'] = array('like',"%$keywords%");
        }
        $orderby = 'id desc';
        $res_goods = $m_goods->getDataList('*',$where,$orderby,0,$all_nums);

        $datalist = array('total'=>0,'datalist'=>array());
        if($res_goods['total']){
            $hash_ids_key = C('HASH_IDS_KEY');
            $hashids = new \Common\Lib\Hashids($hash_ids_key);
            $sale_uid = $hashids->encode($res_user['user_id']);

            $host_name = 'https://'.$_SERVER['HTTP_HOST'];
            foreach ($res_goods['list'] as $v){
                $img_url = '';
                if(!empty($v['cover_imgs'])){
                    $oss_host = "https://".C('OSS_HOST').'/';
                    $cover_imgs_info = explode(',',$v['cover_imgs']);
                    if(!empty($cover_imgs_info[0])){
                        $img_url = $oss_host.$cover_imgs_info[0].'?x-oss-process=image/resize,p_50/quality,q_80';
                    }
                }
                $price = $v['price'];
                $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'price'=>$price,'img_url'=>$img_url);
                $dinfo['qrcode_url'] = $host_name."/smallsale19/qrcode/dishQrcode?data_id={$v['id']}_$sale_uid&type=26";
                $datalist[] = $dinfo;
            }
            $datalist['total'] = $res_goods['total'];
            $datalist['datalist'] = $datalist;
        }
        $this->to_back($datalist);
    }


}