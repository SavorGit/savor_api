<?php
namespace Catvideo\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class CatvideoController extends BaseController{
 	/**
     * 构造函数
     */
    function _init_() {

        $this->valid_fields=array('categoryId'=>'1001','createTime'=>'1001');
        switch(ACTION_NAME) {
            case 'getLastTopList':
                $this->is_verify = 1;
                break;
            case 'getTopList':
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function changeList($res){
        if($res){
            foreach ($res as $vk=>$val) {
                $res[$vk]['contentURL'] = C('HOST_NAME').$val['contentURL'];
                $res[$vk]['imageURL'] = $this->getOssAddr($val['imageURL']);
                $res[$vk]['videoURL'] = substr($val['videoURL'], 0, strpos($val['videoURL'],'.f'));
                $len = count($val['name']);
                if($len != 0) {
                    $res[$vk]['canplay'] = 1;
                    $res[$vk]['name'] = substr($val['name'], strripos($val['name'],'/')+1);
                }
                foreach($val as $sk=>$sv){
                    if (empty($sv)) {
                        unset($res[$vk][$sk]);
                    }
                }

            }
        }
        return $res;
        //如果是空
    }
    /**
     * @desc 获取下拉列表
     */
    public function getLastTopList(){


        $artModel = new \Common\Model\ArticleModel();
        $category_id = $this->params['categoryId'];
        $crtime = $this->params['createTime'];
        $size   = I('numPerPage',20);//显示每页记录数
        $start = I('pageNum',1);
        $start  = ( $start-1 ) * $size;
        $order = I('_order','mco.id');
        $sort = I('_sort','desc');
        $orders = $order.' '.$sort;
        $now = date("Y-m-d H:i:s",time());
        $field = 'mco.id id, mcat.name category, mco.title title,med.oss_addr name, mco.duration duration, mco.img_url imageURL, mco.content_url contentURL, mco.tx_url videoURL, mco.share_title shareTitle, mco.share_content shareContent, mco.create_time createTime';
        $where = '1=1';
        $where .= ' AND mco.state = 2  and  mcat.state=1 and mco.category_id ='.$category_id. ' AND (((mco.bespeak=1 or mco.bespeak=2) AND mco.bespeak > "'.$now.'") or mco.bespeak=0)';
        $table = 'savor_mb_content mco';
        $joina = 'left join savor_mb_category mcat on mco.category_id = mcat.id';
        $joinb = 'left join savor_media med on med.id = mco.media_id';
        $res = $artModel->getCapvideolist($table, $field, $joina,$joinb, $where, $orders, $start, $size);

        $resu = $this->changeList($res);
        if($resu){
            $data['list'] = $resu;
        }else{
            $data['list'] = $resu;
        }
        $this->to_back($data);
    }



    /**
     * @desc 获取上拉列表
     */
    public function getTopList(){
        $artModel = new \Common\Model\ArticleModel();
        $category_id = $this->params['categoryId'];
        $crtime = date("Y-m-d H:i:s",$this->params['createTime']);
        $size   = I('numPerPage',20);//显示每页记录数
        $start = I('pageNum',1);
        $start  = ( $start-1 ) * $size;
        $order = I('_order','mco.id');
        $sort = I('_sort','desc');
        $orders = $order.' '.$sort;
        $now = date("Y-m-d H:i:s",time());
        $field = 'mco.id id, mcat.name category, mco.title title,med.oss_addr name, mco.duration duration, mco.img_url imageURL, mco.content_url contentURL, mco.tx_url videoURL, mco.share_title shareTitle, mco.share_content shareContent, mco.create_time createTime';
        $where = '1=1 AND ';
        $where .= 'mco.create_time < "'.$crtime.'"';
        $where .= ' AND mco.state = 2  and  mcat.state=1 and mco.category_id ='.$category_id. ' AND (((mco.bespeak=1 or mco.bespeak=2) AND mco.bespeak > "'.$now.'") or mco.bespeak=0)';
        $table = 'savor_mb_content mco';
        $joina = 'left join savor_mb_category mcat on mco.category_id = mcat.id';
        $joinb = 'left join savor_media med on med.id = mco.media_id';
        $res = $artModel->getCapvideolist($table, $field, $joina,$joinb, $where, $orders, $start, $size);


        $resu = $this->changeList($res);
        if($resu){
            $data['list'] = $resu;
        }else{
            $data['list'] = $resu;
        }
        $this->to_back($data);
    }
}

