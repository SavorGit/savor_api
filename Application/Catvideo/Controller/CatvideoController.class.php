<?php
namespace Catvideo\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class CatvideoController extends BaseController{
 	/**
     * 构造函数
     */
    function _init_() {

        $this->valid_fields=array('categoryId'=>'1001');
        switch(ACTION_NAME) {

            case 'getLastTopList':
                $this->valid_fields=array('categoryId'=>'1001');
                $this->is_verify = 1;
                break;
            case 'getTopList':
                $this->valid_fields=array('categoryId'=>'1001','createTime'=>'1001');
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
                $res[$vk]['createTime'] = strtotime($val['createTime']);
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
        $flag = $this->params['flag'];
        $size   = I('numPerPage',20);//显示每页记录数
        $start = I('pageNum',1);
        $start  = ( $start-1 ) * $size;
        $order = I('_order','mco.id');
        $sort = I('_sort','desc');
        $orders = $order.' '.$sort;
        $now = date("Y-m-d H:i:s",time());
        $where = '1=1';
        $where .= ' AND mco.state = 2  and  mcat.state=1 and mco.category_id ='.$category_id. ' AND (((mco.bespeak=1 or mco.bespeak=2) AND mco.bespeak > "'.$now.'") or mco.bespeak=0)';
        $res = $artModel->getCapvideolist($where, $orders, $start, $size);

        $resu = $this->changeList($res);
        foreach($resu as $v){
            $ids[] = $v['id'];
        }
        if($resu){
            $data['list'] = $resu;
            $data['flag'] = implode(',', $ids);
            $data['minTime'] = $resu[0]['createTime'];
            $num = count($resu) -1;
            $data['maxTime'] = $resu[$num]['createTime'];
            if(!empty($flag)){
                $old_ids = explode(',', $flag);
            
                $dif_arr = array_diff($ids, $old_ids);
            
                $data['count'] = count($dif_arr);
            
            }
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
        $size   = I('pageSize',10);//显示每页记录数
        $start = I('pageNo',1);
        $start  = ( $start-1 ) * $size;
        $order = I('_order','mco.id');
        $sort = I('_sort','desc');
        $orders = $order.' '.$sort;
        $now = date("Y-m-d H:i:s",time());
        $where = '1=1 AND ';
        $where .= 'mco.create_time < "'.$crtime.'"';
        $where .= ' AND mco.state = 2  and  mcat.state=1 and mco.category_id ='.$category_id. ' AND (((mco.bespeak=1 or mco.bespeak=2) AND mco.bespeak > "'.$now.'") or mco.bespeak=0)';
        $res = $artModel->getCapvideolist($where, $orders, $start, $size);
        $resu = $this->changeList($res);
        if($resu){
            $data['list'] = $resu;
            $num = count($resu) -1;
            $data['minTime'] = $resu[0]['createTime'];
            $data['maxTime'] = $resu[$num]['createTime'];
        }else{
            $data['list'] = $resu;
        }
        $this->to_back($data);
    }
}

