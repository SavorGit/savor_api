<?php
namespace Smallappdata\Controller;
use \Common\Controller\CommonController as CommonController;

class ConfigController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getCity':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'conditiondata':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getCity(){
        $openid = $this->params['openid'];
        $m_vintner = new \Common\Model\VintnerModel();
        $res_vintner = $m_vintner->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_vintner)){
            $this->to_back(95003);
        }
        $m_area_info = new \Common\Model\AreaModel();
        $where = array('is_in_hotel'=>1,'id'=>array('neq',246));
        $all_area = $m_area_info->getWhere('id as area_id,region_name as area_name',$where,'id asc','',2);
        array_unshift($all_area,array('area_id'=>0,'area_name'=>'全国'));
        $this->to_back($all_area);
    }

    public function conditiondata(){
        $openid = $this->params['openid'];
        $m_vintner = new \Common\Model\VintnerModel();
        $res_vintner = $m_vintner->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_vintner)){
            $this->to_back(95003);
        }
        $m_area_info = new \Common\Model\AreaModel();
        $where = array('is_in_hotel'=>1,'id'=>array('neq',246));
        $all_area = $m_area_info->getWhere('id as area_id,region_name as area_name',$where,'id asc','',2);
        array_unshift($all_area,array('area_id'=>0,'area_name'=>'全国'));

        $s_month = '2023-07';
        $data_end_date = date('Y-m',strtotime('-1 month'));
        $data_date_range = array($s_month,$data_end_date);
        $sale_date_range = array("$s_month-01",date('Y-m-d'));

        $res_data = array('city_list'=>$all_area,
            'sale_date'=>array('range'=>$sale_date_range,'default'=>array(date('Y-m-d',strtotime('-30 day')),date('Y-m-d'))),
            'data_date'=>array('range'=>$data_date_range,'default'=>array(date('Y-m',strtotime('-3 month')),$data_end_date)),
        );
        $this->to_back($res_data);
    }
}