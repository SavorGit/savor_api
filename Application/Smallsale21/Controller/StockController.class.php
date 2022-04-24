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
            case 'stocklist':
                $this->params = array('openid'=>1001,'area_id'=>1001,'type'=>1001);
                $this->is_verify = 1;
                break;
            case 'getGoodsByStockid':
                $this->params = array('openid'=>1001,'stock_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'scancode':
                $this->params = array('openid'=>1001,'unit_id'=>1001,'idcode'=>1001,'type'=>1001,'goods_id'=>1001);
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
        }
        parent::_init_();
    }

    public function arealist(){
        $all_stock_city = array(
            array('id'=>1,'name'=>'北京库房','is_select'=>1),
            array('id'=>9,'name'=>'上海库房','is_select'=>0),
            array('id'=>236,'name'=>'广州库房','is_select'=>0),
            array('id'=>246,'name'=>'深圳库房','is_select'=>0),
        );
        $this->to_back($all_stock_city);
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
        $m_stock = new \Common\Model\Finance\StockModel();
        $where = array('area_id'=>$area_id,'type'=>$type);
        $where['status'] = array('in',array(1,0));
        $res_stock = $m_stock->getDataList('*',$where,'id asc');
        $inprogress_list = $finish_list = array();
        if(!empty($res_stock)){
            $m_duser = new \Common\Model\Finance\DepartmentUserModel();
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
            $m_duser = new \Common\Model\Finance\DepartmentUserModel();
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
        $where = array('idcode'=>$idcode,'type'=>1);
        $res_stock_record = $m_stock_record->getInfo($where);
        $now_unit_id = 0;
        if($type==10){
            if(!empty($res_stock_record)){
                $this->to_back(93081);
            }
        }else{
            if(empty($res_stock_record)){
                if(!empty($res_qrcode['parent_id'])){
                    $p_idcode = encrypt_data($res_qrcode['parent_id'],$key);
                    $where = array('idcode'=>$p_idcode,'type'=>1);
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
                $where = array('idcode'=>$idcode,'type'=>3);
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
        $res_stock_record = $m_stock_record->getInfo(array('idcode'=>$idcode,'type'=>1));
        $p_idcode = '';
        $p_idnum = 0;
        if(empty($res_stock_record) && $res_qrcode['type']==2){
            if(!empty($res_qrcode['parent_id'])){
                $p_idnum = $res_qrcode['parent_id'];
                $p_idcode = encrypt_data($res_qrcode['parent_id'],$key);
                $where = array('idcode'=>$p_idcode,'type'=>1);
                $res_p_stock_record = $m_stock_record->getInfo($where);
                if(empty($res_p_stock_record)){
                    $this->to_back(93082);
                }
                $res_stock_record = $res_p_stock_record;
            }
        }

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $res_stock_outrecord = $m_stock_record->getInfo(array('idcode'=>$idcode,'type'=>array('in',array(2,3))));
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
        if($type==1 && !empty($res_detail['purchase_detail_id'])){
            $m_purchasedetail = new \Common\Model\Finance\PurchaseDetailModel();
            $res_purchase = $m_purchasedetail->getInfo(array('id'=>$res_detail['purchase_detail_id']));
            $total_fee = $res_purchase['price'];
            $price = sprintf("%.2f",$total_fee/$total_amount);//单瓶价格
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
                        $res_in = $m_stock_record->getInfo(array('idcode'=>$idcode,'type'=>1));
                        if(!empty($res_in)){
                            $data['price'] = -$res_in['price'];
                        }else{
                            $where = array('idcode'=>$idcode,'type'=>3);
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
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $m_stock_record->delData(array('idcode'=>$idcode,'type'=>$type));
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
        $record_info = $m_stock_record->getInfo(array('idcode'=>$idcode,'type'=>2));
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
                $where = array('stock_id'=>$stock_id,'type'=>2);
                $res_records = $m_stock_record->getDataList('idcode,add_time',$where,'id desc');
                $res_data['goods_list'] = $res_records;
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
        $where = array('stock_id'=>$stock_id,'type'=>2);
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
        $record_info = $m_stock_record->getInfo(array('idcode'=>$idcode,'type'=>2));
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
                $where = array('stock_id'=>$stock_id,'type'=>4);
                $res_records = $m_stock_record->getDataList('idcode,add_time',$where,'id desc');
                $goods_list = array();
                foreach ($res_records as $v){
                    $res_data = $m_stock_record->getInfo(array('idcode'=>$v['idcode'],'type'=>6));
                    if(empty($res_data)){
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
        $where = array('stock_id'=>$stock_id,'type'=>4);
        $res_records = $m_stock_record->getDataList('*',$where,'id desc');
        $add_datas = array();
        $batch_no = date('YmdHis');
        foreach ($res_records as $v){
            $res_data = $m_stock_record->getInfo(array('idcode'=>$v['idcode'],'type'=>6));
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



}
