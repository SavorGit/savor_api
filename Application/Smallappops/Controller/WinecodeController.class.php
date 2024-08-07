<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class WinecodeController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'scanQuery':
                $this->params = array('openid'=>1001,'idcode'=>1001);
                $this->is_verify = 1;
                break;
            case 'imageQuery':
                $this->params = array('openid'=>1001,'img_url'=>1001);
                $this->is_verify = 1;
                break;
            case 'auditRecycle':
                $this->params = array('openid'=>1001,'idcode'=>1001,'status'=>1001,'reason'=>1002,'recycle_img'=>1002);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function scanQuery(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        $m_finnace_winecode = new \Common\Model\Finance\WinecodeModel();
        if(!empty($res_qrcode)){
            $res_winedata = $m_finnace_winecode->getInfo(array('idcode'=>$idcode));
        }else{
            $res_winedata = $m_finnace_winecode->getInfo(array('winecode'=>$idcode));
        }
        $goods_info = $this->query_data($res_winedata);

        $this->to_back($goods_info);
    }

    public function imageQuery(){
        $openid = $this->params['openid'];
        $img_url = $this->params['img_url'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $ali_ocr = new \Common\Lib\AliyunOCR();
        $oss_host = get_oss_host('http');
        $res_ocr = $ali_ocr->RecognizeGeneral($oss_host.$img_url);
        $res_data = json_decode($res_ocr['Data'],true);
        $goods_info = array();
        if(!empty($res_data['prism_wordsInfo'])){
            $first_num = 0;
            $end_num = 0;
            $code = '';
            foreach ($res_data['prism_wordsInfo'] as $k=>$v){
                $position6 = strpos($v['word'], "06");
                $position7 = strpos($v['word'], "07");
                $is_first = 0;
                if($position6!==false && $position6==0){
                    $is_first = 1;
                }elseif($position7!==false && $position7==0){
                    $is_first = 1;
                }
                if($is_first==1){
                    $first_num = $k;
                    $code.=$v['word'];
                }else{
                    if(!empty($code)){
                        $code.=$v['word'];
                    }
                }
                $end_words = "XXXX";
                $position = strpos($v['word'], $end_words);
                if($position!==false){
                    $end_num = $k;
                    break;
                }
            }
            if(!empty($code)){
                $m_finnace_winecode = new \Common\Model\Finance\WinecodeModel();
                $res_winedata = $m_finnace_winecode->getInfo(array('winecode'=>$code));
                if(!empty($res_winedata)){
                    $goods_info = $this->query_data($res_winedata);
                }
            }
        }
        $this->to_back($goods_info);
    }

    public function auditRecycle(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];
        $status = intval($this->params['status']);//2审核通过,6审核不通过
        $reason = $this->params['reason'];
        $recycle_img = $this->params['recycle_img'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        $message = '';
        if(!empty($res_qrcode) && in_array($res_staff['hotel_role_type'],array(6,7))){
            $m_sale = new \Common\Model\Finance\SaleModel();
            $where = array('a.idcode'=>$idcode,'record.wo_status'=>2,'record.recycle_status'=>array('in','1,4,5'));
            $fields = 'a.stock_record_id,a.hotel_id,a.area_id,a.goods_id,record.id,record.wo_reason_type,record.unit_id,record.op_openid';
            $res_sale = $m_sale->getSaleStockRecordList($fields,$where);
            if(!empty($res_sale[0]['stock_record_id'])){
                $stock_record_id = $res_sale[0]['stock_record_id'];
                $hotel_id = $res_sale[0]['hotel_id'];

                $up_record = array('recycle_audit_ops_staff_id'=>$res_staff['id'],'recycle_audit_time'=>date('Y-m-d H:i:s'));
                $m_integralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
                $rwhere = array('jdorder_id'=>$stock_record_id,'type'=>25);
                $res_recordinfo = $m_integralrecord->getALLDataList('id,openid,integral,hotel_id,status',$rwhere,'id desc','0,2','');
                $m_stock_record = new \Common\Model\Finance\StockRecordModel();
                if($status==2){
                    $up_record['recycle_status']=2;
                    $up_record['is_open_reward']=1;
                    if(!empty($recycle_img)){
                        $up_record['recycle_img']=$recycle_img;
                    }
                    $m_stock_record->updateData(array('id'=>$stock_record_id),$up_record);
                    if(!empty($res_recordinfo[0]['id'])){
                        if($res_recordinfo[0]['status']==2){
                            $where = array('hotel_id'=>$hotel_id,'status'=>1);
                            $m_merchant = new \Common\Model\Integral\MerchantModel();
                            $res_merchant = $m_merchant->getInfo($where);
                            $is_integral = $res_merchant['is_integral'];
                            $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
                            foreach ($res_recordinfo as $rv){
                                $record_id = $rv['id'];
                                $m_integralrecord->updateData(array('id'=>$record_id),array('status'=>1,'integral_time'=>date('Y-m-d H:i:s')));
                                $now_integral = $rv['integral'];
                                if($is_integral==1){
                                    $res_integral = $m_userintegral->getInfo(array('openid'=>$rv['openid']));
                                    if(!empty($res_integral)){
                                        $userintegral = $res_integral['integral']+$now_integral;
                                        $m_userintegral->updateData(array('id'=>$res_integral['id']),array('integral'=>$userintegral,'update_time'=>date('Y-m-d H:i:s')));
                                    }else{
                                        $m_userintegral->add(array('openid'=>$rv['openid'],'integral'=>$now_integral));
                                    }
                                }else{
                                    $where = array('id'=>$res_merchant['id']);
                                    $m_merchant->where($where)->setInc('integral',$now_integral);
                                }
                            }
                        }
                    }else{
                        $m_userintegral = new \Common\Model\Smallapp\UserIntegralrecordModel();
                        $m_userintegral->finishRecycle($res_sale[0],1);
                    }
                    $message = '审核通过';

                    $m_approval_process = new \Common\Model\Crm\ApprovalProcessesModel();
                    $m_approval_process->handleProcessStatus(0,12,$hotel_id);
                }elseif($status==6){
                    $up_record['recycle_status']=6;
                    if(!empty($reason)){
                        $up_record['reason']=$reason;
                    }
                    $m_stock_record->updateData(array('id'=>$stock_record_id),$up_record);

                    if(!empty($res_recordinfo[0]['id'])){
                        $del_record_ids = array();
                        foreach ($res_recordinfo as $rv){
                            $del_record_ids[] = $rv['id'];
                        }
                        $m_integralrecord->delData(array('id'=>array('in',$del_record_ids)));
                    }
                    $message = '审核不通过';
                }
            }
        }
        $this->to_back(array('message'=>$message));
    }

    private function query_data($wine_info){
        $idcode = $wine_info['idcode'];
        $winecode = $wine_info['winecode'];
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $fileds = 'a.idcode,a.recycle_status,a.type,a.add_time,stock.hotel_id,goods.id as goods_id,goods.name as goods_name,cate.name as cate_name,spec.name as spec_name,unit.name as unit_name';
        $where = array('a.idcode'=>$idcode,'a.dstatus'=>1);
        $res_records = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
        $goods_info = array();
        if(!empty($res_records)){
            $goods_info = $res_records[0];
            $record_types = C('STOCK_RECORD_TYPE');
            $goods_info['type_str'] = $record_types[$goods_info['type']];
            $all_recycle_status = C('STOCK_RECYCLE_ALL_STATUS');
            $goods_info['recycle_status_str'] = isset($all_recycle_status[$goods_info['recycle_status']])?$all_recycle_status[$goods_info['recycle_status']]:'';
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getOneById('name',$goods_info['hotel_id']);
            $goods_info['hotel_name'] = $res_hotel['name'];
            $goods_info['winecode'] = $winecode;
        }
        return $goods_info;
    }
}
