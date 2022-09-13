<?php
namespace Smallsale21\Controller;
use \Common\Controller\CommonController as CommonController;

class StockController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'arealist':
                $this->is_verify = 0;
                break;
            case 'hotelstock':
                $this->params = array('openid'=>1001,'hotel_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'stocklist':
                $this->params = array('openid'=>1001,'area_id'=>1001,'type'=>1001);
                $this->is_verify = 1;
                break;
            case 'getGoodsByStockid':
                $this->params = array('openid'=>1001,'stock_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'scancode':
                $this->params = array('openid'=>1001,'unit_id'=>1001,'idcode'=>1001,'type'=>1001,'goods_id'=>1001,'stock_detail_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'unpacking':
                $this->params = array('openid'=>1001,'stock_detail_id'=>1001,'idcode'=>1001);
                $this->is_verify = 1;
                break;
            case 'getRecords':
                $this->params = array('openid'=>1001,'stock_detail_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'finishGoods':
                $this->params = array('openid'=>1001,'type'=>1001,'stock_detail_id'=>1001,'goods_codes'=>1001);
                $this->is_verify = 1;
                break;
            case 'finish':
                $this->params = array('openid'=>1001,'stock_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'delGoodscode':
                $this->params = array('openid'=>1001,'idcode'=>1001,'type'=>1001);
                $this->is_verify = 1;
                break;
            case 'getReceiveOutlist':
                $this->params = array('openid'=>1001);
                $this->is_verify = 1;
                break;
            case 'scanReceive':
                $this->params = array('openid'=>1001,'idcode'=>1001,'stock_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'finishReceive':
                $this->params = array('openid'=>1001,'stock_id'=>1001,'goods_codes'=>1001);
                $this->is_verify = 1;
                break;
            case 'scanCheck':
                $this->params = array('openid'=>1001,'idcode'=>1001,'stock_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'finishCheck':
                $this->params = array('openid'=>1001,'stock_id'=>1001,'goods_codes'=>1001,'check_img'=>1001);
                $this->is_verify = 1;
                break;
            case 'scanReportedloss':
                $this->params = array('openid'=>1001,'idcode'=>1001);
                $this->is_verify = 1;
                break;
            case 'finishReportedloss':
                $this->params = array('openid'=>1001,'goods_codes'=>1001,'reason'=>1001);
                $this->is_verify = 1;
                break;
            case 'getWriteoffReasonByGoods':
                $this->params = array('goods_id'=>1001,'type'=>1002);
                $this->is_verify = 1;
                break;
            case 'scanWriteoff':
                $this->params = array('openid'=>1001,'idcode'=>1001,'goods_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'finishWriteoff':
                $this->params = array('openid'=>1001,'goods_codes'=>1001,'reason_type'=>1001,'data_imgs'=>1001);
                $this->is_verify = 1;
                break;
            case 'getWriteoffList':
                $this->params = array('openid'=>1001,'page'=>1001);
                $this->is_verify = 1;
                break;
            case 'scanReplaceCode':
                $this->params = array('openid'=>1001,'idcode'=>1001,'type'=>1001);
                $this->is_verify = 1;
                break;
            case 'isHaveStockHotel':
                $this->params = array('openid'=>1001,'hotel_id'=>1001);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function arealist(){
        $all_stock_city = array(
            array('id'=>1,'name'=>'北京库房','is_select'=>1),
            array('id'=>9,'name'=>'上海库房','is_select'=>0),
            array('id'=>236,'name'=>'广州库房','is_select'=>0),
            array('id'=>246,'name'=>'深圳库房','is_select'=>0),
            array('id'=>248,'name'=>'佛山库房','is_select'=>0),
        );
        $this->to_back($all_stock_city);
    }

    public function hotelstock(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }

        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getOneById('name',$hotel_id);
        $res_data = array('hotel_id'=>$hotel_id,'hotel_name'=>$res_hotel['name']);

        $m_stock_detail = new \Common\Model\Finance\StockDetailModel();
        $where = array('stock.hotel_id'=>$hotel_id,'stock.type'=>20);
        $fileds = 'a.goods_id,sum(a.stock_total_amount) as stock_num,goods.name,cate.name as cate_name,spec.name as sepc_name,a.unit_id,unit.name as unit_name';
        $group = 'a.goods_id,a.unit_id';
        $res_stock = $m_stock_detail->getStockGoodsByHotelId($fileds,$where,$group);
        $goods_list = array();
        if(!empty($res_stock)){
            $m_record = new \Common\Model\Finance\StockRecordModel();
            foreach ($res_stock as $v){
                $out_num = $unpack_num = $wo_num = $report_num = 0;
                $goods_id = $v['goods_id'];
                $unit_id = $v['unit_id'];
                $rfileds = 'sum(a.total_amount) as total_amount,a.type';
                $rwhere = array('stock.hotel_id'=>$hotel_id,'a.goods_id'=>$goods_id,'a.unit_id'=>$unit_id,'a.dstatus'=>1);
                $rwhere['a.type'] = array('in',array(2,3));
                $rgroup = 'a.type';
                $res_record = $m_record->getStockRecordList($rfileds,$rwhere,'a.id desc','',$rgroup);
                foreach ($res_record as $rv){
                    switch ($rv['type']){
                        case 2:
                            $out_num = abs($rv['total_amount']);
                            break;
                        case 3:
                            $unpack_num = $rv['total_amount'];
                            break;
                    }
                }
                $rwhere['a.type']=7;
                $rwhere['a.wo_status']= array('in',array(1,2,4));
                $res_worecord = $m_record->getStockRecordList($rfileds,$rwhere,'a.id desc','','');
                $wo_num = $res_worecord[0]['total_amount'];

                $rwhere['a.type']=6;
                unset($rwhere['a.wo_status']);
                $rwhere['a.status']= array('in',array(1,2));
                $res_worecord = $m_record->getStockRecordList($rfileds,$rwhere,'a.id desc','','');
                $report_num = $res_worecord[0]['total_amount'];

//                $stock_num = $out_num+$unpack_num+$wo_num+$report_num;
                $stock_num = $out_num+$wo_num+$report_num;
                $v['stock_num']=$stock_num;
                $goods_list[]=$v;
            }
        }
        $res_data['goods_list'] = $goods_list;
        $this->to_back($res_data);
    }

    public function stocklist(){
        $openid = $this->params['openid'];
        $area_id = $this->params['area_id'];
        $type = $this->params['type'];//10入库,20出库

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_duser = new \Common\Model\Finance\DepartmentUserModel();
        $m_stock = new \Common\Model\Finance\StockModel();
        $where = array('area_id'=>$area_id,'type'=>$type);
        $where['status'] = array('in',array(1,0));
        $res_stock = $m_stock->getDataList('*',$where,'id asc');
        $inprogress_list = $finish_list = array();
        if(!empty($res_stock)){
            foreach ($res_stock as $v){
                $res_duser = $m_duser->getInfo(array('id'=>$v['department_user_id']));
                $info = array('stock_id'=>$v['id'],'name'=>$v['name'],'add_time'=>$v['add_time'],'user_name'=>$res_duser['name'],'status'=>1);
                $inprogress_list[]=$info;
            }
        }

        $where = array('op_openid'=>$openid,'type'=>$type);
        $where['status'] = array('in',array(2,3,4));
        $res_stock = $m_stock->getDataList('*',$where,'id asc');
        if(!empty($res_stock)){
            foreach ($res_stock as $v){
                $res_duser = $m_duser->getInfo(array('id'=>$v['department_user_id']));
                $info = array('stock_id'=>$v['id'],'name'=>$v['name'],'add_time'=>$v['add_time'],'user_name'=>$res_duser['name'],'status'=>2);
                $finish_list[]=$info;
            }
        }
        $res_data = array('inprogress_list'=>$inprogress_list,'finish_list'=>$finish_list);
        $this->to_back($res_data);
    }

    public function getGoodsByStockid(){
        $openid = $this->params['openid'];
        $stock_id = intval($this->params['stock_id']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }

        $m_stock = new \Common\Model\Finance\StockModel();
        $res_stock = $m_stock->getInfo(array('id'=>$stock_id));
        $m_duser = new \Common\Model\Finance\DepartmentUserModel();
        $res_duser = $m_duser->getInfo(array('id'=>$res_stock['department_user_id']));
        $res_data = array('stock_id'=>$res_stock['id'],'name'=>$res_stock['name'],'add_time'=>$res_stock['add_time'],'user_name'=>$res_duser['name']);

        $m_stock_detail = new \Common\Model\Finance\StockDetailModel();
        $where = array('a.stock_id'=>$stock_id);
        $fileds = 'a.id as stock_detail_id,a.goods_id,a.amount,a.stock_amount,goods.name,cate.name as cate_name,spec.name as sepc_name,
        unit.id as unit_id,unit.name as unit_name';
        $res_goodsdata = $m_stock_detail->getStockGoods($fileds,$where);
        $datalist = array();
        foreach ($res_goodsdata as $v){
            if($v['amount']<0){
                $v['amount'] = abs($v['amount']);
            }
            $datalist[]=$v;
        }
        $res_data['goods_list'] = $datalist;
        $this->to_back($res_data);
    }

    public function scancode(){
        $openid = $this->params['openid'];
        $unit_id = intval($this->params['unit_id']);
        $idcode = $this->params['idcode'];
        $type = $this->params['type'];//类型 10入库,20出库
        $goods_id = intval($this->params['goods_id']);
        $stock_detail_id = intval($this->params['stock_detail_id']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }

        $io_type = 0;
        if($stock_detail_id>0){
            $m_stockdetail = new \Common\Model\Finance\StockDetailModel();
            $res_detail = $m_stockdetail->getStockDetailInfo('stock.io_type',array('a.id'=>$stock_detail_id));
            if(!empty($res_detail)){
                $io_type = $res_detail['io_type'];
            }
        }

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $where = array('idcode'=>$idcode,'dstatus'=>1);
        $res_stock_record_type = $m_stock_record->getALLDataList('type',$where,'id desc','0,1','');
        if(!empty($res_stock_record_type[0]['type']) && $res_stock_record_type[0]['type']>3){
            $type_error_codes = array('4'=>93088,'5'=>93089,'6'=>93095,'7'=>93094);
            if(in_array($io_type,array(12,13))){
                $type_error_codes = array('6'=>93095,'7'=>93094);
            }
            if(isset($type_error_codes[$res_stock_record_type[0]['type']])){
                $this->to_back($type_error_codes[$res_stock_record_type[0]['type']]);
            }
        }

        $where = array('idcode'=>$idcode,'type'=>1,'dstatus'=>1);
        $res_stock_record = $m_stock_record->getInfo($where);
        $now_unit_id = 0;
        if($type==10){
            if(in_array($io_type,array(12,13))){
                if(!empty($res_stock_record_type[0]['type'])){
                    $m_stock_record->updateData(array('idcode'=>$idcode),array('dstatus'=>2));
                }
            }else{
                if(!empty($res_stock_record)){
                    $this->to_back(93081);
                }
            }
        }else{
            if(empty($res_stock_record)){
                if(!empty($res_qrcode['parent_id'])){
                    $p_idcode = encrypt_data($res_qrcode['parent_id'],$key);
                    $where = array('idcode'=>$p_idcode,'type'=>1,'dstatus'=>1);
                    $res_p_stock_record = $m_stock_record->getInfo($where);
                    if(empty($res_p_stock_record)){
                        $this->to_back(93082);
                    }
                    $now_unit_id = $res_p_stock_record['unit_id'];
                    $res_stock_record = $res_p_stock_record;
                }
            }else{
                $now_unit_id = $res_stock_record['unit_id'];
            }
            if($res_stock_record['goods_id']!=$goods_id){
                $this->to_back(93083);
            }
        }
        $qr_type = 0;
        if(!empty($res_qrcode)){
            $qr_type = $res_qrcode['type'];//1箱码,2瓶码
        }
        $unit_type = -1;
        $m_unit = new \Common\Model\Finance\UnitModel();
        $res_unit = $m_unit->getInfo(array('id'=>$unit_id));
        if(!empty($res_unit)){
            if($res_unit['type']==1 && $res_unit['convert_type']==1){
                $unit_type = 2;
            }else{
                $unit_type = 1;
            }
        }
        if($qr_type!=$unit_type){
            if($qr_type==1){
                $this->to_back(93078);
            }else{
                $this->to_back(93079);
            }
        }elseif($type==20){
            if($unit_id!=$now_unit_id){
                $where = array('idcode'=>$idcode,'type'=>3,'dstatus'=>1);
                $res_uppack = $m_stock_record->getInfo($where);
                if(empty($res_uppack)){
                    $res = array('is_unpacking'=>1);
                    $this->to_back($res);
                }
            }
        }

        $res = array('idcode'=>$idcode,'add_time'=>date('Y-m-d H:i:s'),'status'=>1,'is_unpacking'=>0);
        $this->to_back($res);
    }

    public function unpacking(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];
        $stock_detail_id = intval($this->params['stock_detail_id']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $res_stock_record = $m_stock_record->getInfo(array('idcode'=>$idcode,'type'=>1,'dstatus'=>1));
        $p_idcode = '';
        $p_idnum = 0;
        if(empty($res_stock_record) && $res_qrcode['type']==2){
            if(!empty($res_qrcode['parent_id'])){
                $p_idnum = $res_qrcode['parent_id'];
                $p_idcode = encrypt_data($res_qrcode['parent_id'],$key);
                $where = array('idcode'=>$p_idcode,'type'=>1,'dstatus'=>1);
                $res_p_stock_record = $m_stock_record->getInfo($where);
                if(empty($res_p_stock_record)){
                    $this->to_back(93082);
                }
                $res_stock_record = $res_p_stock_record;
            }
        }

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $res_stock_outrecord = $m_stock_record->getInfo(array('idcode'=>$idcode,'type'=>array('in',array(2,3)),'dstatus'=>1));
        if(!empty($res_stock_outrecord)){
            $this->to_back(93085);
        }

        $m_stockdetail = new \Common\Model\Finance\StockDetailModel();
        $res_detail = $m_stockdetail->getInfo(array('id'=>$stock_detail_id));
        if(empty($res_detail)){
            $this->to_back(93084);
        }

        $stock_id = $res_detail['stock_id'];
        $goods_id = $res_detail['goods_id'];
        $unit_id = $res_detail['unit_id'];

        $all_code = array();
        if(!empty($p_idcode)){
            $amount = 1;
            $m_unit = new \Common\Model\Finance\UnitModel();
            $res_unit = $m_unit->getInfo(array('id'=>$res_stock_record['unit_id']));
            $total_amount = $res_unit['convert_type']*$amount;

            $batch_no = date('YmdHis');
            $price = $res_stock_record['price'];
            $total_fee = $total_amount*$price;

            //记录拆箱:箱-1,瓶+6
            $add_record_data = array('stock_id'=>$res_stock_record['stock_id'],'stock_detail_id'=>$res_stock_record['stock_detail_id'],'goods_id'=>$res_stock_record['goods_id'],
                'batch_no'=>$batch_no,'idcode'=>$p_idcode,'price'=>-$price,'unit_id'=>$res_stock_record['unit_id'],'amount'=>-$amount,'total_amount'=>-$total_amount,
                'total_fee'=>-$total_fee,'type'=>3,'op_openid'=>$openid
            );
            $m_stock_record->add($add_record_data);

            $batch_no = $batch_no.'01';
            $res_all_codes = $m_qrcode_content->getDataList('id',array('parent_id'=>$p_idnum),'id asc');
            foreach ($res_all_codes as $v){
                $now_idcode = encrypt_data($v['id'],$key);
                $add_record_data = array('stock_id'=>$stock_id,'stock_detail_id'=>$stock_detail_id,'goods_id'=>$goods_id,
                    'batch_no'=>$batch_no,'idcode'=>$now_idcode,'price'=>$price,'unit_id'=>$unit_id,'amount'=>$amount,'total_amount'=>$amount,
                    'total_fee'=>$price,'type'=>3,'op_openid'=>$openid
                );
                $m_stock_record->add($add_record_data);
            }
            $all_code[]= array('idcode'=>$idcode,'add_time'=>date('Y-m-d H:i:s'),'status'=>1);
        }
        $this->to_back($all_code);
    }

    public function finishGoods(){
        $openid = $this->params['openid'];
        $stock_detail_id = intval($this->params['stock_detail_id']);
        $goods_codes = $this->params['goods_codes'];
        $type = $this->params['type'];//类型 1入库,2出库

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_stockdetail = new \Common\Model\Finance\StockDetailModel();
        $res_detail = $m_stockdetail->getInfo(array('id'=>$stock_detail_id));
        if(empty($res_detail)){
            $this->to_back(93084);
        }
        $stock_id = $res_detail['stock_id'];
        $goods_id = $res_detail['goods_id'];
        $unit_id = $res_detail['unit_id'];

        $amount = 1;
        $m_unit = new \Common\Model\Finance\UnitModel();
        $res_unit = $m_unit->getInfo(array('id'=>$unit_id));
        $total_amount = $res_unit['convert_type']*$amount;

        $price = 0;
        $total_fee = 0;
        if($type==1){
            if(!empty($res_detail['purchase_detail_id'])){
                $m_purchasedetail = new \Common\Model\Finance\PurchaseDetailModel();
                $res_purchase = $m_purchasedetail->getInfo(array('id'=>$res_detail['purchase_detail_id']));
                $total_fee = $res_purchase['price'];
                $price = sprintf("%.2f",$total_fee/$total_amount);//单瓶价格
            }else{
                $m_stock_record = new \Common\Model\Finance\StockRecordModel();
                $res_record = $m_stock_record->getALLDataList('price,total_fee',array('goods_id'=>$goods_id,'unit_id'=>$unit_id,'type'=>1),'id asc','0,1','');
                if(!empty($res_record)){
                    $price = $res_record[0]['price'];
                    $total_fee = $res_record[0]['total_fee'];
                }
            }
        }
        $all_idcodes = explode(',',$goods_codes);
        if(!empty($all_idcodes)){
            $idcode_num = 0;
            $m_stock_record = new \Common\Model\Finance\StockRecordModel();
            $key = C('QRCODE_SECRET_KEY');
            $batch_no = date('YmdHis');
            foreach ($all_idcodes as $v){
                $idcode = $v;
                if(!empty($idcode)){
                    $idcode_num++;
                    $data = array('stock_id'=>$stock_id,'stock_detail_id'=>$stock_detail_id,'goods_id'=>$goods_id,'batch_no'=>$batch_no,'idcode'=>$idcode,
                        'price'=>$price,'total_fee'=>$total_fee,'unit_id'=>$unit_id,'amount'=>$amount,'total_amount'=>$total_amount,'type'=>$type,'op_openid'=>$openid
                    );
                    $res_in = array();
                    if($type==2){
                        $res_in = $m_stock_record->getInfo(array('idcode'=>$idcode,'type'=>1,'dstatus'=>1));
                        if(!empty($res_in)){
                            $data['price'] = -$res_in['price'];
                        }else{
                            $where = array('idcode'=>$idcode,'type'=>3,'dstatus'=>1);
                            $res_in = $m_stock_record->getInfo($where);
                            if(!empty($res_in)){
                                $data['price'] = -$res_in['price'];
                            }
                        }
                        $data['total_fee'] = $data['price']*$total_amount;
                        $data['amount'] = -$amount;
                        $data['total_amount'] = -$total_amount;
                    }
                    $m_stock_record->add($data);
                    if($type==2 && !empty($res_in)){
                        $m_stock_record->updateData(array('id'=>$res_in['id']),array('status'=>1,'update_time'=>date('Y-m-d H:i:s')));
                    }
                }
            }
            if($idcode_num>0){
                $detail_amount = $amount*$idcode_num;
                $detail_total_amount = $total_amount*$idcode_num;
                if($type==2){
                    $detail_amount = -$detail_amount;
                    $detail_total_amount = -$detail_total_amount;
                }
                $updata = array('amount'=>$res_detail['amount']+$detail_amount,'total_amount'=>$res_detail['total_amount']+$detail_total_amount);
                $m_stockdetail->updateData(array('id'=>$stock_detail_id),$updata);
                $m_stock = new \Common\Model\Finance\StockModel();
                $m_stock->updateData(array('id'=>$stock_id),array('status'=>1,'update_time'=>date('Y-m-d H:i:s')));
            }
        }
        $this->to_back(array('stock_detail_id'=>$stock_detail_id));
    }

    public function getRecords(){
        $openid = $this->params['openid'];
        $stock_detail_id = intval($this->params['stock_detail_id']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_stockdetail = new \Common\Model\Finance\StockDetailModel();
        $res_detail = $m_stockdetail->getInfo(array('id'=>$stock_detail_id));
        if(empty($res_detail)){
            $this->to_back(93084);
        }

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $where = array('stock_detail_id'=>$stock_detail_id);
        $where['type'] = array('neq',3);
        $res = $m_stock_record->getDataList('idcode,add_time',$where,'id desc');
        $res_data = array();
        if(!empty($res)){
            foreach ($res as $k=>$v){
                $v['status'] = 2;
                $res_data[]=$v;
            }
        }
        $this->to_back($res_data);
    }

    public function delGoodscode(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];
        $type = intval($this->params['type']);//1入库,2出库

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        $where = array('idcode'=>$idcode,'type'=>$type,'dstatus'=>1);
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $res_record = $m_stock_record->getInfo($where);
        if(!empty($res_record)){
            $m_stock_record->delData(array('id'=>$res_record['id']));

            $stock_detail_id = $res_record['stock_detail_id'];
            $m_stock_detail = new \Common\Model\Finance\StockDetailModel();
            $res_detail = $m_stock_detail->getInfo(array('id'=>$stock_detail_id));
            $amount = abs($res_detail['amount']);
            $total_amount = abs($res_detail['total_amount']);

            $now_amount = 1;
            $m_unit = new \Common\Model\Finance\UnitModel();
            $res_unit = $m_unit->getInfo(array('id'=>$res_record['unit_id']));
            $now_total_amount = $res_unit['convert_type']*$now_amount;

            $detail_amount = $amount-$now_amount;
            $detail_total_amount = $total_amount-$now_total_amount;
            if($type==2){
                $detail_amount = -$detail_amount;
                $detail_total_amount = -$detail_total_amount;
            }
            $updata = array('amount'=>$detail_amount,'total_amount'=>$detail_total_amount);
            $m_stock_detail->updateData(array('id'=>$stock_detail_id),$updata);
        }
        $this->to_back(array());
    }

    public function finish(){
        $openid = $this->params['openid'];
        $stock_id = intval($this->params['stock_id']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_stock = new \Common\Model\Finance\StockModel();
        $up_data = array('status'=>2,'op_openid'=>$openid);
        $m_stock->updateData(array('id'=>$stock_id),$up_data);
        $this->to_back(array());
    }

    public function getReceiveOutlist(){
        $openid = $this->params['openid'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_stock = new \Common\Model\Finance\StockModel();
        $where = array('receive_openid'=>$openid,'type'=>20);
        $where['status'] = 3;
        $res_stock = $m_stock->getDataList('*',$where,'id asc');
        $data_list = array();
        if(!empty($res_stock)){
            $m_duser = new \Common\Model\Finance\DepartmentUserModel();
            foreach ($res_stock as $v){
                $res_duser = $m_duser->getInfo(array('id'=>$v['department_user_id']));
                $info = array('stock_id'=>$v['id'],'name'=>$v['name'],'add_time'=>$v['add_time'],'user_name'=>$res_duser['name']);
                $data_list[]=$info;
            }
        }
        $res_data = array('datalist'=>$data_list);
        $this->to_back($res_data);
    }

    public function scanReceive(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];
        $stock_id = intval($this->params['stock_id']);//如为0,则是第一次扫码

        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $record_info = $m_stock_record->getInfo(array('idcode'=>$idcode,'type'=>2,'dstatus'=>1));
        $now_stock_id = $record_info['stock_id'];

        $is_first = 0;
        if($stock_id==0){
            $stock_id = $now_stock_id;
            $is_first = 1;
        }
        if($stock_id!=$now_stock_id){
            $this->to_back(93086);
        }

        $m_stock = new \Common\Model\Finance\StockModel();
        $res_stock = $m_stock->getInfo(array('id'=>$stock_id));
        if($res_stock['status']==2){
            $res_data = array('stock_id'=>$stock_id,'idcode'=>$idcode,'goods_list'=>array());
            if($is_first){
                $where = array('stock_id'=>$stock_id,'type'=>2,'dstatus'=>1);
                $res_records = $m_stock_record->getDataList('idcode,add_time',$where,'id desc');
                $goods_list = array();
                foreach ($res_records as $v){
                    $v['checked'] = false;
                    $goods_list[]=$v;
                }
                $res_data['goods_list'] = $goods_list;
            }
            $this->to_back($res_data);
        }else{
            switch ($res_stock['status']){
                case 0:
                case 1:
                    $this->to_back(93087);
                    break;
                case 3:
                    $this->to_back(93088);
                    break;
                case 4:
                    $this->to_back(93089);
                    break;
            }
        }
    }

    public function finishReceive(){
        $openid = $this->params['openid'];
        $stock_id = intval($this->params['stock_id']);
        $goods_codes = $this->params['goods_codes'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_stock = new \Common\Model\Finance\StockModel();
        $res_stock = $m_stock->getInfo(array('id'=>$stock_id));
        if($res_stock['status']==3){
            $this->to_back(93088);
        }
        $all_idcodes = explode(',',$goods_codes);
        $idcode_nums = 0;
        if(!empty($all_idcodes)){
            $idcode_nums = count($all_idcodes);
        }

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $where = array('stock_id'=>$stock_id,'type'=>2,'dstatus'=>1);
        $res_records = $m_stock_record->getDataList('*',$where,'id desc');
        $goods_code_num = count($res_records);
        if($idcode_nums!=$goods_code_num){
            $this->to_back(93090);
        }
        $batch_no = date('YmdHis');
        foreach ($res_records as $v){
            unset($v['id'],$v['update_time']);
            $v['price'] = abs($v['price']);
            $v['total_fee'] = abs($v['total_fee']);
            $v['amount'] = abs($v['amount']);
            $v['total_amount'] = abs($v['total_amount']);

            $v['type'] = 4;
            $v['op_openid'] = $openid;
            $v['batch_no'] = $batch_no;
            $v['add_time'] = date('Y-m-d H:i:s');

            $m_stock_record->add($v);
        }

        $up_data = array('status'=>3,'receive_openid'=>$openid,'update_time'=>date('Y-m-d H:i:s'));
        $m_stock->updateData(array('id'=>$stock_id),$up_data);
        $this->to_back(array());
    }

    public function scanCheck(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];
        $stock_id = intval($this->params['stock_id']);//如为0,则是第一次扫码

        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $record_info = $m_stock_record->getInfo(array('idcode'=>$idcode,'type'=>2,'dstatus'=>1));
        $now_stock_id = $record_info['stock_id'];

        $is_first = 0;
        if($stock_id==0){
            $stock_id = $now_stock_id;
            $is_first = 1;
        }
        if($stock_id!=$now_stock_id){
            $this->to_back(93086);
        }

        $m_stock = new \Common\Model\Finance\StockModel();
        $res_stock = $m_stock->getInfo(array('id'=>$stock_id));
        if($res_stock['status']==3){
            $res_data = array('stock_id'=>$stock_id,'idcode'=>$idcode,'goods_list'=>array());
            if($is_first){
                $where = array('stock_id'=>$stock_id,'type'=>4,'dstatus'=>1);
                $res_records = $m_stock_record->getDataList('idcode,add_time',$where,'id desc');
                $goods_list = array();
                foreach ($res_records as $v){
                    $res_datainfo = $m_stock_record->getInfo(array('idcode'=>$v['idcode'],'type'=>6,'dstatus'=>1));
                    if(empty($res_datainfo)){
                        $v['checked'] = false;
                        $goods_list[]=$v;
                    }
                }
                $res_data['goods_list'] = $goods_list;
            }
            $this->to_back($res_data);
        }else{
            switch ($res_stock['status']){
                case 0:
                case 1:
                    $this->to_back(93087);
                    break;
                case 2:
                    $this->to_back(93091);
                    break;
                case 4:
                    $this->to_back(93089);
                    break;
            }
        }
    }

    public function finishCheck(){
        $openid = $this->params['openid'];
        $stock_id = intval($this->params['stock_id']);
        $goods_codes = $this->params['goods_codes'];
        $check_img = $this->params['check_img'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_stock = new \Common\Model\Finance\StockModel();
        $res_stock = $m_stock->getInfo(array('id'=>$stock_id));
        if($res_stock['status']==4){
            $this->to_back(93089);
        }
        $all_idcodes = explode(',',$goods_codes);
        $now_idcodes = array();

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $where = array('stock_id'=>$stock_id,'type'=>4,'dstatus'=>1);
        $res_records = $m_stock_record->getDataList('*',$where,'id desc');
        $add_datas = array();
        $batch_no = date('YmdHis');
        foreach ($res_records as $v){
            $res_data = $m_stock_record->getInfo(array('idcode'=>$v['idcode'],'type'=>6,'dstatus'=>1));
            if(empty($res_data)){
                unset($v['id'],$v['update_time']);
                $v['price'] = abs($v['price']);
                $v['total_fee'] = abs($v['total_fee']);
                $v['amount'] = abs($v['amount']);
                $v['total_amount'] = abs($v['total_amount']);
                $v['type'] = 5;
                $v['op_openid'] = $openid;
                $v['batch_no'] = $batch_no;
                $v['add_time'] = date('Y-m-d H:i:s');
                $add_datas[]=$v;
                $now_idcodes[]=$v['idcode'];
            }
        }
        $code_diff = array_diff($all_idcodes,$now_idcodes);
        if(!empty($code_diff)){
            $this->to_back(93092);
        }
        $m_stock_record->addAll($add_datas);

        $up_data = array('status'=>4,'check_openid'=>$openid,'check_img'=>$check_img,'update_time'=>date('Y-m-d H:i:s'));
        $m_stock->updateData(array('id'=>$stock_id),$up_data);
        $this->to_back(array());
    }

    public function scanReportedloss(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];

        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $fileds = 'a.idcode,goods.id as goods_id,goods.name as goods_name,cate.name as cate_name,
        spec.name as spec_name,unit.name as unit_name,a.type,a.status,stock.serial_number,stock.name as stock_name,a.op_openid,a.add_time';
        $where = array('a.idcode'=>$idcode,'a.dstatus'=>1);
        $res_records = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
        $goods_info = array();
        if(!empty($res_records)){
            $goods_info = $res_records[0];
            if($goods_info['type']==6 && in_array($goods_info['status'],array(1,2))){
                $error_status = array('1'=>93099,'2'=>93095);
                $this->to_back($error_status[$goods_info['status']]);
            }
            $record_types = C('STOCK_RECORD_TYPE');
            $goods_info['type_str'] = $record_types[$goods_info['type']];
            $m_user = new \Common\Model\Smallapp\UserModel();
            $res_user = $m_user->getOne('nickName',array('openid'=>$goods_info['op_openid']),'id desc');
            $goods_info['op_uname'] = $res_user['nickName'];
        }
        $this->to_back($goods_info);
    }

    public function userstocklist(){
        $openid = $this->params['openid'];
        $type = $this->params['type'];//10入库,20出库 30领取 40验收

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_stock = new \Common\Model\Finance\StockModel();
        if($type==30){
            $where = array('receive_openid'=>$openid);
        }elseif($type==40){
            $where = array('check_openid'=>$openid);
        }else{
            $where = array('op_openid'=>$openid,'type'=>$type);
        }
        $where['status'] = array('in',array(2,3,4));
        $res_stock = $m_stock->getDataList('*',$where,'id asc');

        $data_list = array();
        if(!empty($res_stock)){
            $m_duser = new \Common\Model\Finance\DepartmentUserModel();
            $m_stock_record = new \Common\Model\Finance\StockRecordModel();
            $m_stock_detail = new \Common\Model\Finance\StockDetailModel();
            foreach ($res_stock as $v){
                $stock_id = $v['id'];
                $res_duser = $m_duser->getInfo(array('id'=>$v['department_user_id']));
                $info = array('stock_id'=>$stock_id,'name'=>$v['name'],'add_time'=>$v['add_time'],'user_name'=>$res_duser['name']);
                if($type==30){
                    $dfields = 'sum(stock_total_amount) as stock_num';
                    $res_detail = $m_stock_detail->getALLDataList($dfields,array('stock_id'=>$stock_id),'id desc','','');
                    $stock_num = intval($res_detail[0]['stock_num']);
                    $check_num = 0;
                    $rfields = 'sum(total_amount) as total_amount';
                    $res_record = $m_stock_record->getALLDataList($rfields,array('stock_id'=>$stock_id,'type'=>7),'id desc','','');
                    if(!empty($res_record[0]['total_amount'])){
                        $check_num = abs($res_record[0]['total_amount']);
                    }
                    $last_num = $stock_num-$check_num;
                    if($last_num>0){
                        $data_list[]=$info;
                    }
                }else{
                    $data_list[]=$info;
                }
            }
        }
        $res_data = array('data_list'=>$data_list);
        $this->to_back($res_data);
    }

    public function getStockGoods(){
        $openid = $this->params['openid'];
        $stock_id = intval($this->params['stock_id']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $where = array('stock_id'=>$stock_id,'type'=>2);
        $res_records = $m_stock_record->getDataList('idcode,add_time',$where,'id desc');
        $goods_list = array();
        foreach ($res_records as $v){
            $status=0;//1已核销,2已报损
            $res_rinfo = $m_stock_record->getInfo(array('idcode'=>$v['idcode'],'type'=>7));
            if(!empty($res_rinfo)){
                $status=1;
            }else{
                $res_rinfo = $m_stock_record->getInfo(array('idcode'=>$v['idcode'],'type'=>6));
                if(!empty($res_rinfo)){
                    $status=2;
                }
            }
            $v['checked'] = false;
            $v['status'] = $status;
            $goods_list[]=$v;
        }
        $res_data = array('stock_id'=>$stock_id,'goods_list'=>$goods_list);

        $this->to_back($res_data);
    }

    public function finishReportedloss(){
        $openid = $this->params['openid'];
        $goods_codes = $this->params['goods_codes'];
        $reason = trim($this->params['reason']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $all_idcodes = explode(',',$goods_codes);
        if(!empty($all_idcodes)){
            $m_stock_record = new \Common\Model\Finance\StockRecordModel();
            foreach ($all_idcodes as $v){
                $idcode = $v;
                $res_record = $m_stock_record->getALLDataList('*',array('idcode'=>$idcode,'dstatus'=>1),'id desc','0,1','');
                if(!empty($res_record)){
                    $batch_no = date('YmdHis');
                    $add_data = $res_record[0];
                    if($add_data['type']==7){
                        continue;
                    }
                    if($add_data['type']==6){
                        $up_data = array('type'=>6,'status'=>1,'op_openid'=>$openid,'batch_no'=>$batch_no,
                            'reason'=>$reason,'update_time'=>date('Y-m-d H:i:s'));
                        $m_stock_record->updateData(array('id'=>$add_data['id']),$up_data);
                    }else{
                        unset($add_data['id'],$add_data['update_time']);
                        $add_data['price'] = -abs($add_data['price']);
                        $add_data['total_fee'] = -abs($add_data['total_fee']);
                        $add_data['amount'] = -abs($add_data['amount']);
                        $add_data['total_amount'] = -abs($add_data['total_amount']);
                        $add_data['type'] = 6;
                        $add_data['status'] = 1;
                        $add_data['op_openid'] = $openid;
                        $add_data['batch_no'] = $batch_no;
                        $add_data['reason'] = $reason;
                        $add_data['add_time'] = date('Y-m-d H:i:s');
                        $m_stock_record->add($add_data);
                    }
                }
            }
        }
        $this->to_back(array());
    }

    public function getWriteoffReasonByGoods(){
        $goods_id = intval($this->params['goods_id']);
        $type = intval($this->params['type']);//类型 1售卖,2品鉴酒,3活动
        if($type>0){
            $now_type = $type;
        }else{
            $now_type = 1;
        }
        $m_goodsconfig = new \Common\Model\Finance\GoodsConfigModel();
        $where = array('goods_id'=>$goods_id,'status'=>1,'type'=>$now_type);
        $field = 'id,name,is_required';
        $res_config = $m_goodsconfig->getDataList($field,$where,'id asc');
        $data = array();
        if(!empty($res_config)){
            foreach ($res_config as $v){
                $v['img_url']='';
                $data[]=$v;
            }
        }
        $entity = array();
        $where = array('goods_id'=>$goods_id,'status'=>1,'type'=>20);
        $field = 'id,name,media_id';
        $res_config = $m_goodsconfig->getDataList($field,$where,'id asc');
        if(!empty($res_config)){
            $m_media = new \Common\Model\MediaModel();
            foreach ($res_config as $v){
                $res_media = $m_media->getMediaInfoById($v['media_id']);
                $entity[]=array('name'=>$v['name'],'img_url'=>$res_media['oss_addr']);
            }
        }

        $res_data = array('reasons'=>array_values(C('STOCK_REASON')),'entity'=>$entity,'datas'=>$data);
        $this->to_back($res_data);
    }

    public function scanWriteoff(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];
        $goods_id = intval($this->params['goods_id']);//如为0,则是第一次扫码

        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $record_info = $m_stock_record->getALLDataList('*',array('idcode'=>$idcode,'dstatus'=>1),'id desc','0,1','');
        $m_stock = new \Common\Model\Finance\StockModel();
        $res_stock = $m_stock->getInfo(array('id'=>$record_info[0]['stock_id']));
        if($res_stock['io_type']!=22){
            $this->to_back(93093);
        }
        if($record_info[0]['type']==5){
            if($goods_id>0 && $goods_id!=$record_info[0]['goods_id']){
                $this->to_back(93097);
            }
            $res = array('idcode'=>$idcode,'add_time'=>date('Y-m-d H:i:s'),'goods_id'=>$record_info[0]['goods_id']);
            $this->to_back($res);
        }else{
            if($record_info[0]['type']==7){
                if($record_info[0]['wo_status']==1){
                    $this->to_back(93098);
                }elseif($record_info[0]['wo_status']==2){
                    $this->to_back(93094);
                }else{
                    $res = array('idcode'=>$idcode,'add_time'=>date('Y-m-d H:i:s'),'goods_id'=>$record_info[0]['goods_id']);
                    $this->to_back($res);
                }
            }elseif($record_info[0]['type']==6){
                $this->to_back(93095);
            }else{
                $this->to_back(93096);
            }
        }
    }

    public function finishWriteoff(){
        $openid = $this->params['openid'];
        $goods_codes = $this->params['goods_codes'];
        $reason_type = intval($this->params['reason_type']);
        $data_imgs = $this->params['data_imgs'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $goods_ids = array();
        $all_idcodes = explode(',',$goods_codes);
        if(!empty($all_idcodes)){
            $m_stock_record = new \Common\Model\Finance\StockRecordModel();
            $batch_no = date('YmdHis');
            foreach ($all_idcodes as $v){
                $idcode = $v;
                $res_record = $m_stock_record->getALLDataList('*',array('idcode'=>$idcode,'dstatus'=>1),'id desc','0,1','');
                if(!empty($res_record)){
                    $add_data = $res_record[0];
                    $goods_ids[]=$add_data['goods_id'];
                    $is_new = 0;
                    if($add_data['type']==7){
                        switch ($add_data['wo_status']){
                            case 1:
                            case 4:
                                $up_data = array('op_openid'=>$openid,'batch_no'=>$batch_no,'wo_reason_type'=>$reason_type,
                                    'wo_data_imgs'=>$data_imgs,'wo_status'=>1,'wo_num'=>$add_data['wo_num']+1,'update_time'=>date('Y-m-d H:i:s')
                                );
                                $m_stock_record->updateData(array('id'=>$add_data['id']),$up_data);
                                break;
                            case 3:
                                $up_data = array('dstatus'=>2,'update_time'=>date('Y-m-d H:i:s'));
                                $m_stock_record->updateData(array('id'=>$add_data['id']),$up_data);
                                $is_new = 1;
                                break;
                        }
                    }else{
                        $is_new = 1;
                    }
                    if($is_new==1){
                        unset($add_data['id'],$add_data['update_time']);
                        $add_data['price'] = -abs($add_data['price']);
                        $add_data['total_fee'] = -abs($add_data['total_fee']);
                        $add_data['amount'] = -abs($add_data['amount']);
                        $add_data['total_amount'] = -abs($add_data['total_amount']);
                        $add_data['type'] = 7;
                        $add_data['op_openid'] = $openid;
                        $add_data['batch_no'] = $batch_no;
                        $add_data['wo_reason_type'] = $reason_type;
                        $add_data['wo_data_imgs'] = $data_imgs;
                        $add_data['wo_status'] = 1;
                        $add_data['wo_num'] = 1;
                        $add_data['update_time'] = date('Y-m-d H:i:s');
                        $add_data['add_time'] = date('Y-m-d H:i:s');
                        $m_stock_record->add($add_data);
                    }
                }
            }
        }
        $message = '提交成功';
        if(!empty($goods_ids)){
            $m_goodsconfig = new \Common\Model\Finance\GoodsConfigModel();
            $configwhere = array('goods_id'=>array('in',$goods_ids),'type'=>10);
            $configwhere['integral'] = array('gt',0);
            $res_goods = $m_goodsconfig->getDataList('*',$configwhere,'id desc');
            if(!empty($res_goods)){
                $message = '提交成功，审核通过后24小时内将为你发放对应积分奖励。';
            }
        }
        $this->to_back(array('message'=>$message));
    }

    public function getWriteoffList(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $pagesize = 10;

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $offset = ($page-1)*$pagesize;
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $limit = "$offset,$pagesize";
        $order = 'id desc';
        $where = array('op_openid'=>$openid,'type'=>7);
        $fields = 'batch_no,add_time,wo_status as status,wo_reason_type as reason_type';
        $res_records = $m_stock_record->getALLDataList($fields,$where,$order,$limit,'batch_no');
        $data_list = array();
        if(!empty($res_records)){
            $m_goodsconfig = new \Common\Model\Finance\GoodsConfigModel();
            $m_media = new \Common\Model\MediaModel();
            $all_reasons = C('STOCK_REASON');
            $all_status = C('STOCK_AUDIT_STATUS');
            $fileds = 'a.idcode,goods.id as goods_id,goods.name as goods_name,cate.name as cate_name,
            spec.name as spec_name,unit.name as unit_name,a.wo_status as status,a.add_time';
            foreach ($res_records as $v){
                $reason = '';
                if(isset($all_reasons[$v['reason_type']])){
                    $reason = $all_reasons[$v['reason_type']]['name'];
                }
                $batch_no = $v['batch_no'];
                $where = array('a.batch_no'=>$batch_no,'a.type'=>7);
                $res_goods = $m_stock_record->getStockRecordList($fileds,$where,'a.id asc','','');

                $entity = array();
                $cwhere = array('goods_id'=>$res_goods[0]['goods_id'],'status'=>1,'type'=>20);
                $res_config = $m_goodsconfig->getDataList('id,name,media_id',$cwhere,'id asc');
                if(!empty($res_config)){
                    foreach ($res_config as $cv){
                        $res_media = $m_media->getMediaInfoById($cv['media_id']);
                        $entity[]=array('name'=>$cv['name'],'img_url'=>$res_media['oss_addr']);
                    }
                }

                $data_list[]=array('reason'=>$reason,'status'=>$v['status'],
                    'status_str'=>$all_status[$v['status']],'num'=>count($res_goods),'add_time'=>$v['add_time'],
                    'goods'=>$res_goods,'entity'=>$entity);
            }
        }
        $this->to_back($data_list);
    }

    public function scanReplaceCode(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];
        $type = $this->params['type'];//1老码,2新码

        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        if($res_qrcode['type']!=1){
            $res_data = array('tips'=>'扫码失败，目前只支持扫描箱码进行替换');
            $this->to_back($res_data);
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $fileds = 'a.idcode,goods.id as goods_id,goods.name as goods_name,cate.name as cate_name,
        spec.name as spec_name,unit.name as unit_name,a.type,a.status,stock.serial_number,stock.name as stock_name,a.op_openid,a.add_time';
        $where = array('a.idcode'=>$idcode,'a.dstatus'=>1);
        $res_records = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
        $res_data = array('tips'=>'');
        if($type==1){
            if(!empty($res_records)){
                $goods_info = $res_records[0];
                $record_types = C('STOCK_RECORD_TYPE');
                $goods_info['type_str'] = $record_types[$goods_info['type']];
                $m_user = new \Common\Model\Smallapp\UserModel();
                $res_user = $m_user->getOne('nickName',array('openid'=>$goods_info['op_openid']),'id desc');
                $goods_info['op_uname'] = $res_user['nickName'];
                $res_data = array_merge($res_data,$goods_info);
            }else{
                $this->to_back(93101);
            }
        }else{
            if(!empty($res_records)){
                $this->to_back(93100);
            }else{
                $res_data['idcode'] = $idcode;
            }
        }
        $this->to_back($res_data);
    }


    public function finishReplaceCode(){
        $openid = $this->params['openid'];
        $old_code = $this->params['old_code'];
        $new_code = $this->params['new_code'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($old_code,false,$key);
        $old_qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_oldqrcode = $m_qrcode_content->getInfo(array('id'=>$old_qrcode_id));
        if(empty($res_oldqrcode)){
            $this->to_back(93080);
        }

        $qrcode_id = decrypt_data($new_code,false,$key);
        $new_qrcode_id = intval($qrcode_id);
        $res_newqrcode = $m_qrcode_content->getInfo(array('id'=>$new_qrcode_id));
        if(empty($res_newqrcode)){
            $this->to_back(93080);
        }

        if($res_oldqrcode['type']==1 && $res_newqrcode['type']==1){
            $m_stock_record = new \Common\Model\Finance\StockRecordModel();
            $m_stock_record->updateData(array('idcode'=>$old_code),array('idcode'=>$new_code));

            $all_new_codes = array();
            $res_ncodes = $m_qrcode_content->getDataList('*',array('parent_id'=>$new_qrcode_id),'id desc');
            foreach ($res_ncodes as $v){
                $all_new_codes[]=encrypt_data($v['id'],$key);
            }
            $res_ocodes = $m_qrcode_content->getDataList('*',array('parent_id'=>$old_qrcode_id),'id desc');
            foreach ($res_ocodes as $k=>$v){
                $o_code = encrypt_data($v['id'],$key);
                $n_code = $all_new_codes[$k];
                $m_stock_record->updateData(array('idcode'=>$o_code),array('idcode'=>$n_code));
            }
        }
        $this->to_back(array('tips'=>'提交成功'));
    }

    public function isHaveStockHotel(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];

        $is_pop_time = 0;
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(9);
        $cache_key = C('FINANCE_HOTELSTOCK');
        $result  = $redis->get($cache_key);
        $hotel_arr = json_decode($result,true);
        if(!empty($hotel_arr[$hotel_id])){
            $now_time = time();
            $s_time = strtotime(date('Y-m-d 18:45:00'));
            $end_time = strtotime(date('Y-m-d 19:15:00'));
            if($now_time>=$s_time && $now_time<=$end_time){
                $is_pop_time = 1;
            }
        }
        $data = array('is_pop_time'=>$is_pop_time);
        $this->to_back($data);
    }

}
