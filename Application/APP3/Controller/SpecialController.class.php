<?php
/**
 * @desc 专题接口
 * @author zhang.yingtao
 * @since  2017-07-07
 */
namespace APP3\Controller;
use Think\Controller;
use Common\Controller\BaseController as BaseController;
class SpecialController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getSpecialName':
                $this->is_verify = 0;
                break;
            case 'getSpecialList':
                $this->is_verify =0;
                $this->valid_fields = array('sort_num'=>'1000');
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取专题名称
     */
    public function getSpecialName(){
        $m_sys_config = new \Common\Model\SysConfigModel();
        $where = "'system_special_title'";
        $info = $m_sys_config->getInfo($where);
        $data =  array();
        if(!empty($info)){
            $data['specialName'] = $info[0]['config_value'];
        }else {
            $data['specialName'] = '';
        }
       
        $this->to_back($data);
    }
    /**
     * @desc 专题列表
     */
    public function getSpecialList(){
        $sort_num = $this->params['sort_num'];
        $category_id = 103;
        $orders = 'mco.sort_num desc';
        $now = date("Y-m-d H:i:s",time());
        $where = '1=1';
        $where .= ' AND mco.state = 2   and mco.hot_category_id ='.$category_id. ' AND (((mco.bespeak=1 or mco.bespeak=2) AND mco.bespeak_time < "'.$now.'") or mco.bespeak=0)';
        if($sort_num){
            $where .=" and mco.sort_num<$sort_num ";
        }
        $artModel = new \Common\Model\ArticleModel();
        $size = $this->params['numPerPage'] ? $this->params['numPerPage'] :20;
        
        $result = $artModel->getSpecialList($where, $orders,$size);
        
        $deviceid = $this->traceinfo['deviceid'];
        foreach ($result as $key=>$val) {
            
            $result[$key]['imageURL'] = $this->getOssAddr($val['imageURL']) ;
            $result[$key]['contentURL'] = $this->getContentUrl($val['contentURL']);
            if(!empty($val['name'])){
                
                    $ttp = explode('/', $val['name']);
                    $result[$key]['name'] = $ttp[2];
                
                }
            unset($result[$key]['name']);
            foreach($val as $sk=>$sv){
                if (empty($sv)) {
                    unset($result[$key][$sk]);
                }
            }
            $result[$key]['updateTime'] = date('Y-m-d',strtotime($val['createTime']));
            
        }
        if($result){
            $count = count($result);
            if($count<20){
                $nextPage = 0;
            }else{
                $where = '1=1';
                $where .= ' AND mco.state = 2   and mco.hot_category_id ='.$category_id. ' AND (((mco.bespeak=1 or mco.bespeak=2) AND mco.bespeak_time < "'.$now.'") or mco.bespeak=0)';
                $order  = 'sort_num asc';
                $info = $artModel->alias('mco')->where($where)->order($order)->limit(1)->find();
                $sort_num_get = $info['sort_num'];
                //获取传过去最后一条
                $sort_pass_last = $result[$count-1]['sort_num'];
                if($sort_num_get == $sort_pass_last){
                    $nextPage = 0;
                }else{
                    $nextPage = 1;
                }

            }
            $data['list'] = $result;
            $data['nextpage'] = $nextPage;
        }else{
            $data = array();
        }
        $this->to_back($data);
    }
}