<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class OrderexpressModel extends BaseModel{
	protected $tableName='smallapp_orderexpress';

	public function getExpress($order_id){
	    $res_express = $this->getInfo(array('order_id'=>$order_id));
	    $res_data = array();
	    if(!empty($res_express)){
            $express = getExpress($res_express['comcode'],$res_express['enum']);
            if(!empty($express)){
                $express = getExpress($res_express['comcode'],$res_express['enum']);
            }
            $all_status = array(
                '0'=>'在途','1'=>'揽收','2'=>'疑难','3'=>'签收',
                '4'=>'退签','5'=>'派件','6'=>'退回','7'=>'转投'
            );
            if(!empty($express)){
                $express = new \Common\Lib\Express();
                $all_company = $express->getCompany();
                $res_data = array('enum'=>$express['nu'],'company'=>$all_company[$express['com']]['name'],
                    'state'=>$express['state'],'state_str'=>$all_status[$express['state']]);
                if(!empty($express['data'])){
                    $data = array();
                    foreach ($express['data'] as $v){
                        $data[]=array('context'=>$v['context'],'time'=>$v['time'],'status'=>'');
                    }
                    $data[0]['status'] = $res_data['state_str'];
                    $res_data['data']=$data;
                }
            }
        }
        return $res_data;
    }
}