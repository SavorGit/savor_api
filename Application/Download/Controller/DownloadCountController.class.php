<?php
namespace Download\Controller;

use \Common\Controller\BaseController as BaseController;
class DownloadCountController extends BaseController{
 	/**
     * 构造函数
     */
    function _init_() {

        
        switch(ACTION_NAME) {
            case 'recordCount':
                $this->is_verify = 1;
                $this->valid_fields=array('st'=>'1001','hotelid'=>'1001','waiterid'=>'1001');
                
                break;
        }
        parent::_init_();
    }
    public function recordCount(){
        $st = $this->params['st'];              //来源
        $hotelid = $this->params['hotelid'];    //餐厅id
        $waiterid= $this->params['waiterid'];   //服务员id
        $traceinfo = $this->traceinfo;
        $client_name = $traceinfo['clientname'];     //客户端类型
        
        
        $download_source_arr = C('DOWLOAD_SOURCE_ARR');  //下载来源数组
        $client_arr = array('android'=>1,'ios'=>2);;              //客户端数组
        if(!key_exists($st, $download_source_arr)){
            $this->to_back(14001);
        }
        if(!key_exists($client_name, $client_arr)){
            $this->to_back(14002);
        }
        
        
        $data = array();
        $data['source_type'] =$download_source_arr[$st] ;
        $data['clientid'] = $client_arr[$client_name];
        $data['deviceid'] = $traceinfo['deviceid'];
        $data['hotelid'] = $hotelid;
        $data['waiterid'] = $waiterid;
        $data['add_time'] = date('Y-m-d H:i:s');
        $m_downd_count = new \Common\Model\DownloadCountModel();
        $ret = $m_downd_count->record($data);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(14003);
        }
    }
}