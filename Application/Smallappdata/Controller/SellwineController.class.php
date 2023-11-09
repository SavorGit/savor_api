<?php
namespace Smallappdata\Controller;
use \Common\Controller\CommonController as CommonController;

class SellwineController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'statdata':
                $this->valid_fields = array('openid'=>1001,'day'=>1001,'area_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'datalist':
                $this->valid_fields = array('openid'=>1001,'day'=>1001,'area_id'=>1001,'page'=>1001);
                $this->is_verify = 1;
                break;
            case 'hotels':
                $this->valid_fields = array('openid'=>1001,'day'=>1001,'area_id'=>1001,'goods_id'=>1001,'page'=>1001);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function statdata(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $day = intval($this->params['day']);

        $m_vintner = new \Common\Model\VintnerModel();
        $res_vintner = $m_vintner->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_vintner)){
            $this->to_back(95003);
        }
        $m_finance_goods = new \Common\Model\Finance\GoodsModel();
        $res_goods = $m_finance_goods->getDataList('id,name',array('brand_id'=>array('in',$res_vintner['brand_ids'])),'id desc');
        $goods = array();
        foreach ($res_goods as $v){
            $goods[$v['id']]=$v;
        }

        $now_date = date('Y-m-d');
        switch ($day){
            case 1:
                $start_date = $now_date;
                break;
            case 2:
                $start_date = date('Y-m-d',strtotime('-6day'));
                break;
            case 3:
                $start_date = date('Y-m-01');
                break;
            default:
                $start_date = $now_date;
        }
        $end_date = $now_date;
        $start_time = date('Y-m-d 00:00:00',strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59',strtotime($end_date));

        $fields = 'goods_id,count(DISTINCT hotel_id) as hotel_num,count(id) as num';
        $where = array('goods_id'=>array('in',array_keys($goods)),'type'=>1);
        if($area_id){
            $where['area_id'] = $area_id;
        }
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time));

        $m_sale = new \Common\Model\Finance\SaleModel();
        $res_data = $m_sale->getALLDataList($fields,$where,'','','goods_id');
        $datalist = array();
        foreach ($res_data as $v){
            $v['goods_name'] = $goods[$v['goods_id']]['name'];
            $datalist[]=$v;
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $day = intval($this->params['day']);
        $page = intval($this->params['page']);
        $pagesize = 20;

        $m_vintner = new \Common\Model\VintnerModel();
        $res_vintner = $m_vintner->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_vintner)){
            $this->to_back(95003);
        }
        $m_finance_goods = new \Common\Model\Finance\GoodsModel();
        $res_goods = $m_finance_goods->getDataList('id,name',array('brand_id'=>array('in',$res_vintner['brand_ids'])),'id desc');
        $goods = array();
        foreach ($res_goods as $v){
            $goods[$v['id']]=$v;
        }

        $now_date = date('Y-m-d');
        switch ($day){
            case 1:
                $start_date = $now_date;
                break;
            case 2:
                $start_date = date('Y-m-d',strtotime('-6day'));
                break;
            case 3:
                $start_date = date('Y-m-01');
                break;
            default:
                $start_date = $now_date;
        }
        $end_date = $now_date;
        $start_time = date('Y-m-d 00:00:00',strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59',strtotime($end_date));

        $fields = 'a.id,a.add_time,a.goods_id,a.hotel_id,hotel.name as hotel_name,goods.name as goods_name,record.vintner_code';
        $where = array('a.goods_id'=>array('in',array_keys($goods)),'a.type'=>1);
        if($area_id){
            $where['a.area_id'] = $area_id;
        }
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));

        $offset = ($page-1)*$pagesize;
        $limit = "$offset,$pagesize";
        $m_sale = new \Common\Model\Finance\SaleModel();
        $data_list = $m_sale->getSaleStockRecordList($fields,$where,'',$limit,'a.id desc');
        foreach ($data_list as $k=>$v){
            $data_list[$k]['num'] = 1;
            $data_list[$k]['add_time'] = date('Y-m-d H:i',strtotime($v['add_time']));
            $data_list[$k]['hotel_name'] = $this->hideHotelName($v['hotel_name']);
        }
        $this->to_back(array('datalist'=>$data_list));
    }

    public function hotels(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $day = intval($this->params['day']);
        $goods_id = intval($this->params['goods_id']);
        $page = intval($this->params['page']);
        $pagesize = 20;

        $m_vintner = new \Common\Model\VintnerModel();
        $res_vintner = $m_vintner->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_vintner)){
            $this->to_back(95003);
        }

        $now_date = date('Y-m-d');
        switch ($day){
            case 1:
                $start_date = $now_date;
                break;
            case 2:
                $start_date = date('Y-m-d',strtotime('-6day'));
                break;
            case 3:
                $start_date = date('Y-m-01');
                break;
            default:
                $start_date = $now_date;
        }
        $end_date = $now_date;
        $start_time = date('Y-m-d 00:00:00',strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59',strtotime($end_date));

        $fields = 'a.hotel_id,hotel.name as hotel_name';
        $where = array('a.goods_id'=>$goods_id,'a.type'=>1);
        if($area_id){
            $where['a.area_id'] = $area_id;
        }
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $offset = ($page-1)*$pagesize;
        $limit = "$offset,$pagesize";
        $m_sale = new \Common\Model\Finance\SaleModel();
        $data_list = $m_sale->getSaleStockRecordList($fields,$where,'a.hotel_id',$limit);
        foreach ($data_list as $k=>$v){
            $data_list[$k]['hotel_name'] = $this->hideHotelName($v['hotel_name']);
        }
        $this->to_back(array('goods_id'=>$goods_id,'datalist'=>$data_list));
    }



    private function hideHotelName($hotel_name){
        $length = mb_strlen($hotel_name, 'UTF-8');
        $first_char = mb_substr($hotel_name, 0, 1, 'UTF-8');
        if($length<=2){
            $hotel_name = $first_char.'*';
        }else{
            $last_char = mb_substr($hotel_name, -1, 1, 'UTF-8');
            $times = $length-2>3?3:$length-2;
            $mask = str_repeat('*',$times);
            $hotel_name = $first_char.$mask.$last_char;
        }
        return $hotel_name;
    }

}
