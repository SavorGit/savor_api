<?php
namespace Smallappdata\Controller;
use \Common\Controller\CommonController as CommonController;

class StatdataController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'selldata':
                $this->valid_fields = array('openid'=>1001,'sdate'=>1001,'edate'=>1001);
                $this->is_verify = 1;
                break;
            case 'areadata':
                $this->valid_fields = array('openid'=>1001,'sdate'=>1001,'edate'=>1001);
                $this->is_verify = 1;
                break;
            case 'hoteldata':
                $this->valid_fields = array('openid'=>1001);
                $this->is_verify = 1;
                break;
            case 'winedata':
                $this->valid_fields = array('openid'=>1001,'sdate'=>1001,'edate'=>1001);
                $this->is_verify = 1;
                break;
            case 'typedata':
                $this->valid_fields = array('openid'=>1001,'sdate'=>1001,'edate'=>1001);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function selldata(){
        $openid = $this->params['openid'];
        $sdate = $this->params['sdate'];
        $edate = $this->params['edate'];

        $m_vintner = new \Common\Model\VintnerModel();
        $res_vintner = $m_vintner->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_vintner)){
            $this->to_back(95003);
        }
        $m_finance_goods = new \Common\Model\Finance\GoodsModel();
        $res_goods = $m_finance_goods->getDataList('id as goods_id,name as goods_name',array('brand_id'=>array('in',$res_vintner['brand_ids'])),'id desc');
        $goods = array();
        foreach ($res_goods as $v){
            $goods[$v['goods_id']]=$v;
        }
        $start_time = date('Y-m-01 00:00:00',strtotime($sdate));
        $end_time_begin = date('Y-m-01',strtotime($edate));
        $last_day = date("t", strtotime($end_time_begin));
        $end_time = date("Y-m-$last_day 23:59:59",strtotime($edate));

        $start_date = new \DateTime($start_time);
        $end_date = new \DateTime($end_time);
        $interval = new \DateInterval('P1M');
        $daterange = new \DatePeriod($start_date, $interval, $end_date);
        $all_month = array();
        foreach ($daterange as $date) {
            $all_month[]=$date->format('Y-m');
        }

        $fields = "DATE_FORMAT(add_time,'%Y-%m') as sell_month,sum(num) as num";
        $where = array('goods_id'=>array('in',array_keys($goods)),'type'=>array('in','1,4'));
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $m_sale = new \Common\Model\Finance\SaleModel();
        $res_data = $m_sale->getALLDataList($fields,$where,'','','sell_month');
        $month_data = array();
        foreach ($res_data as $v){
            $month_data[$v['sell_month']]=intval($v['num']);
        }
        $data = array();
        foreach ($all_month as $v){
            $num = isset($month_data[$v])?$month_data[$v]:0;
            $data[]=$num;
        }
        $this->to_back(array('categories'=>$all_month,'data'=>$data));
    }

    public function areadata(){
        $openid = $this->params['openid'];
        $sdate = $this->params['sdate'];
        $edate = $this->params['edate'];

        $m_vintner = new \Common\Model\VintnerModel();
        $res_vintner = $m_vintner->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_vintner)){
            $this->to_back(95003);
        }
        $m_finance_goods = new \Common\Model\Finance\GoodsModel();
        $res_goods = $m_finance_goods->getDataList('id as goods_id,name as goods_name',array('brand_id'=>array('in',$res_vintner['brand_ids'])),'id desc');
        $goods = array();
        foreach ($res_goods as $v){
            $goods[$v['goods_id']]=$v;
        }
        $start_time = date('Y-m-01 00:00:00',strtotime($sdate));
        $end_time_begin = date('Y-m-01',strtotime($edate));
        $last_day = date("t", strtotime($end_time_begin));
        $end_time = date("Y-m-$last_day 23:59:59",strtotime($edate));

        $fields = "area_id,sum(num) as num";
        $where = array('goods_id'=>array('in',array_keys($goods)),'type'=>array('in','1,4'));
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $m_sale = new \Common\Model\Finance\SaleModel();
        $res_data = $m_sale->getALLDataList($fields,$where,'','','area_id');
        $sale_data = array();
        foreach ($res_data as $v){
            $num = intval($v['num']);
            $sale_data[$v['area_id']] = $num;
        }
        $m_area_info = new \Common\Model\AreaModel();
        $where = array('is_in_hotel'=>1,'id'=>array('neq',246));
        $all_area = $m_area_info->getWhere('id as area_id,region_name as area_name',$where,'id asc','',2);
        $area_data = array();
        foreach ($all_area as $k=>$v){
            $num = isset($sale_data[$v['area_id']])?$sale_data[$v['area_id']]:0;
            $area_data[]=array('name'=>$v['area_name'],'data'=>$num);
        }
        $this->to_back(array('area_data'=>$area_data));
    }

    public function hoteldata(){
        $openid = $this->params['openid'];

        $m_vintner = new \Common\Model\VintnerModel();
        $res_vintner = $m_vintner->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_vintner)){
            $this->to_back(95003);
        }
        $m_finance_goods = new \Common\Model\Finance\GoodsModel();
        $res_goods = $m_finance_goods->getDataList('id as goods_id,name as goods_name',array('brand_id'=>array('in',$res_vintner['brand_ids'])),'id desc');
        $goods = array();
        foreach ($res_goods as $v){
            $goods[$v['goods_id']]=$v;
        }
        $m_hotelstock = new \Common\Model\Finance\HotelStockModel();
        $where = array('goods_id'=>array('in',array_keys($goods)));
        $res_hotelstock = $m_hotelstock->getALLDataList('area_id,count(DISTINCT hotel_id) as num',$where,'','','area_id');
        $all_hotel_nums = array();
        foreach ($res_hotelstock as $v){
            $all_hotel_nums[$v['area_id']]=intval($v['num']);
        }
        $m_area_info = new \Common\Model\AreaModel();
        $where = array('is_in_hotel'=>1,'id'=>array('neq',246));
        $all_area = $m_area_info->getWhere('id as area_id,region_name as area_name',$where,'id asc','',2);
        $area_data = array();
        foreach ($all_area as $k=>$v){
            $num = isset($all_hotel_nums[$v['area_id']])?$all_hotel_nums[$v['area_id']]:0;
            $area_data[]=array('name'=>$v['area_name'],'data'=>$num);
        }
        $this->to_back(array('area_hotel_data'=>$area_data));
    }

    public function winedata(){
        $openid = $this->params['openid'];
        $sdate = $this->params['sdate'];
        $edate = $this->params['edate'];

        $m_vintner = new \Common\Model\VintnerModel();
        $res_vintner = $m_vintner->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_vintner)){
            $this->to_back(95003);
        }
        $m_finance_goods = new \Common\Model\Finance\GoodsModel();
        $res_goods = $m_finance_goods->getDataList('id as goods_id,name as goods_name',array('brand_id'=>array('in',$res_vintner['brand_ids'])),'id desc');
        $goods = array();
        foreach ($res_goods as $v){
            $goods[$v['goods_id']]=$v;
        }
        $start_time = date('Y-m-01 00:00:00',strtotime($sdate));
        $end_time_begin = date('Y-m-01',strtotime($edate));
        $last_day = date("t", strtotime($end_time_begin));
        $end_time = date("Y-m-$last_day 23:59:59",strtotime($edate));

        $fields = "goods_id,sum(num) as num";
        $where = array('goods_id'=>array('in',array_keys($goods)),'type'=>array('in','1,4'));
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $m_sale = new \Common\Model\Finance\SaleModel();
        $res_data = $m_sale->getALLDataList($fields,$where,'','','goods_id');
        $sale_data = array();
        foreach ($res_data as $v){
            $num = intval($v['num']);
            $sale_data[]=array('name'=>$goods[$v['goods_id']]['goods_name'],'data'=>$num,'goods_id'=>$v['goods_id']);
        }
        $this->to_back(array('wine_data'=>$sale_data));
    }

    public function typedata(){
        $openid = $this->params['openid'];
        $sdate = $this->params['sdate'];
        $edate = $this->params['edate'];

        $m_vintner = new \Common\Model\VintnerModel();
        $res_vintner = $m_vintner->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_vintner)){
            $this->to_back(95003);
        }
        $m_finance_goods = new \Common\Model\Finance\GoodsModel();
        $res_goods = $m_finance_goods->getDataList('id as goods_id,name as goods_name',array('brand_id'=>array('in',$res_vintner['brand_ids'])),'id desc');
        $goods = array();
        foreach ($res_goods as $v){
            $goods[$v['goods_id']]=$v;
        }
        $start_time = date('Y-m-01 00:00:00',strtotime($sdate));
        $end_time_begin = date('Y-m-01',strtotime($edate));
        $last_day = date("t", strtotime($end_time_begin));
        $end_time = date("Y-m-$last_day 23:59:59",strtotime($edate));

        $fields = "type,sum(num) as num";
        $where = array('goods_id'=>array('in',array_keys($goods)),'type'=>array('in','1,4'));
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $m_sale = new \Common\Model\Finance\SaleModel();
        $res_data = $m_sale->getALLDataList($fields,$where,'','','type');
        $sale_data = array();
        foreach ($res_data as $v){
            $num = intval($v['num']);
            $sale_data[$v['type']]=$num;
        }
        $all_sale_types = C('STOCK_SALE_TYPES');
        $data1 = isset($sale_data[1])?$sale_data[1]:0;
        $data4 = isset($sale_data[4])?$sale_data[4]:0;
        $type_data = array(
            array('name'=>$all_sale_types[1],'data'=>$data1),
            array('name'=>$all_sale_types[4],'data'=>$data4),
        );
        $this->to_back(array('type_data'=>$type_data));
    }

}
