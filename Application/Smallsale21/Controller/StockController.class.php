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

        $where = array('op_openid'=>$openid,'type'=>$type,'status'=>2);
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
        $m_stock_detail = new \Common\Model\Finance\StockDetailModel();
        $where = array('a.stock_id'=>$stock_id);
        $fileds = 'a.id as stock_detail_id,a.goods_id,a.amount,a.stock_amount,goods.name,cate.name as cate_name,spec.name as sepc_name,
        unit.id as unit_id,unit.name as unit_name';
        $res_data = $m_stock_detail->getStockGoods($fileds,$where);
        $datalist = array();
        foreach ($res_data as $v){
            if($v['amount']<0){
                $v['amount'] = abs($v['amount']);
            }
            $datalist[]=$v;
        }
        $this->to_back($datalist);
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
        if($type==10){
            if(!empty($res_stock_record)){
                $this->to_back(93081);
            }
        }else{
            if(empty($res_stock_record)){
                $this->to_back(93082);
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
            if($qr_type==1){//出库 弹框是否拆箱,入库报错
                if($type==20){
                    $res = array('is_unpacking'=>1);
                    $this->to_back($res);
                }else{
                    $this->to_back(93078);
                }
            }else{
                $this->to_back(93079);
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
        if(empty($res_stock_record)){
            $this->to_back(93082);
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
        $res_all_qrcode = $m_qrcode_content->getQrcodeList('id',array('parent_id'=>$qrcode_id),'id asc',0,1);
        if(!empty($res_all_qrcode)){
            $qrcode = encrypt_data($res_all_qrcode[0]['id'],$key);
            $amount = 1;
            $m_unit = new \Common\Model\Finance\UnitModel();
            $res_unit = $m_unit->getInfo(array('id'=>$res_stock_record['unit_id']));
            $total_amount = $res_unit['convert_type']*$amount;

            $batch_no = date('YmdHis');
            //记录拆箱:箱-1,瓶+6
            $add_record_data = array('stock_id'=>$res_stock_record['stock_id'],'stock_detail_id'=>$res_stock_record['stock_detail_id'],'goods_id'=>$res_stock_record['goods_id'],
                'batch_no'=>$batch_no,'idcode'=>$idcode,'price'=>$res_stock_record['price'],'unit_id'=>$res_stock_record['unit_id'],'amount'=>-$amount,'total_amount'=>-$total_amount,'type'=>3,'op_openid'=>$openid
            );
            $m_stock_record->add($add_record_data);

            $batch_no = $batch_no.'01';
            $price = $res_stock_record['price'];
            $add_record_data = array('stock_id'=>$stock_id,'stock_detail_id'=>$stock_detail_id,'goods_id'=>$goods_id,
                'batch_no'=>$batch_no,'idcode'=>$qrcode,'price'=>$price,'unit_id'=>$unit_id,'amount'=>$total_amount,'total_amount'=>$total_amount,'type'=>3,'op_openid'=>$openid
            );
            $m_stock_record->add($add_record_data);

            $all_code[]= array('idcode'=>$qrcode,'add_time'=>date('Y-m-d H:i:s'),'status'=>1);
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
                        $data['price'] = -$res_in['price'];
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


}