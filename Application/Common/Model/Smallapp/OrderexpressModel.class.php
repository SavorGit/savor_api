<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class OrderexpressModel extends BaseModel{
	protected $tableName='smallapp_orderexpress';

	public function getExpress($order_id,$express_id=0){
	    if($express_id){
            $res_express = $this->getInfo(array('id'=>$express_id));
        }else{
            $res_express = $this->getInfo(array('order_id'=>$order_id));
        }

	    $res_data = array();
        if(!empty($res_express)){
            $express = getExpress($res_express['comcode'],$res_express['enum']);
            if(!empty($express) && isset($express['returnCode'])){
                $express = getExpress($res_express['comcode'],$res_express['enum']);
            }
            $all_status = array(
                '0'=>'在途','1'=>'揽收','2'=>'疑难','3'=>'签收',
                '4'=>'退签','5'=>'派件','6'=>'退回','7'=>'转投'
            );
            if(!empty($express) && !isset($express['returnCode'])){
                $express_obj = new \Common\Lib\Express();
                $all_company = $express_obj->getCompany();
                $res_data = array('enum'=>$express['nu'],'company'=>$all_company[$express['com']]['name'],
                    'state'=>$express['state'],'state_str'=>$all_status[$express['state']]);
                if(!empty($express['data'])){
                    $data = array();
                    foreach ($express['data'] as $v){
                        $time = strtotime($v['time']);
                        $express_date = date('m-d',$time);
                        $express_time = date('H:i:s',$time);
                        $info = array('context'=>$v['context'],'time'=>$v['time'],'express_date'=>$express_date,
                            'express_time'=>$express_time,'state_str'=>'');
                        $data[]=$info;
                    }
                    $data[0]['status'] = $res_data['state_str'];
                    $res_data['data']=$data;
                }
            }
        }
        return $res_data;
    }

    public function getExpressList($order_id){
        $express_obj = new \Common\Lib\Express();
        $all_company = $express_obj->getCompany();
        $all_status = array(
            '0'=>'在途','1'=>'揽收','2'=>'疑难','3'=>'签收',
            '4'=>'退签','5'=>'派件','6'=>'退回','7'=>'转投'
        );
        $res_express = $this->getDataList('*',array('order_id'=>$order_id),'id desc');
        $res_data = array();
        if(!empty($res_express)){
            foreach ($res_express as $k=>$v){
                $e_num = $k+1;
                $einfo = array('express_id'=>$v['id'],'name'=>'运单'.$e_num,'data'=>array());
                $express = getExpress($v['comcode'],$v['enum']);
                if(!empty($express) && isset($express['returnCode'])){
                    $express = getExpress($v['comcode'],$v['enum']);
                }
                if(!empty($express) && !isset($express['returnCode'])){
                    $state_str = $all_status[$express['state']];
                    if(!empty($express['data'])){
                        $time = strtotime($express['data'][0]['time']);
                        $express_date = date('m-d',$time);
                        $express_time = date('H:i:s',$time);
                        $info = array('context'=>$express['data'][0]['context'],'time'=>$express['data'][0]['time'],
                            'express_date'=>$express_date, 'express_time'=>$express_time,'state_str'=>$state_str);
                        $einfo['data'][]=$info;
                    }
                }
                $res_data[]=$einfo;
            }
        }
        return $res_data;
    }
}