<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class ApprovalHandleController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'process10':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'approval_id'=>1001,'processes_id'=>1001,'status'=>1001,'work_staff_id'=>1002,'receipt_img'=>1002);
                break;
            case 'process11':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'approval_id'=>1001,'processes_id'=>1001,'status'=>1001,'work_staff_id'=>1002);
                break;
            case 'process12':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'approval_id'=>1001,'processes_id'=>1001,'status'=>1001,
                    'work_staff_id'=>1002,'idcodes'=>1002,'latitude'=>1002,'longitude'=>1002);
                break;
            case 'scancode':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'idcode'=>1001,'approval_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function process10(){
        $openid = $this->params['openid'];
        $approval_id = intval($this->params['approval_id']);
        $processes_id = intval($this->params['processes_id']);
        $work_staff_id = intval($this->params['work_staff_id']);
        $status = intval($this->params['status']);
        $receipt_img = $this->params['receipt_img'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ops_staff_id = $res_staff['id'];
        $m_approval = new \Common\Model\Crm\ApprovalModel();
        $res_approval = $m_approval->getInfo(array('id'=>$approval_id));
        $item_id = $res_approval['item_id'];
        $m_approval_process = new \Common\Model\Crm\ApprovalProcessesModel();
        $res_process = $m_approval_process->getInfo(array('id'=>$processes_id,'approval_id'=>$approval_id,'ops_staff_id'=>$ops_staff_id,'is_receive'=>1));
        $message='';
        if(!empty($res_process)){
            $step_order = $res_process['step_order']+1;
            $res_next = $m_approval_process->getInfo(array('approval_id'=>$approval_id,'step_order'=>$step_order));
            $where = array('id'=>$processes_id);
            $message = '处理完毕';
            switch ($status){
                case 1:
                    $hotel_id = $res_approval['hotel_id'];
                    $m_hotel_ext = new \Common\Model\HotelExtModel();
                    $res_ext = $m_hotel_ext->getData('is_salehotel', array('hotel_id'=>$hotel_id));
                    if($res_ext[0]['is_salehotel']==0){
                        $this->to_back(94104);
                    }

                    $m_stock = new \Common\Model\Finance\StockModel();
                    $is_out = $m_stock->checkHotelThreshold($hotel_id,1);
                    if($is_out==0){
                        $this->to_back(94103);
                    }
                    $m_approval_process->updateData($where,array('status'=>$status,'handle_status'=>3,'handle_time'=>date('Y-m-d H:i:s')));
                    if(!empty($res_next)){
                        $m_approval_process->updateData(array('id'=>$res_next['id']),array('is_receive'=>1,'handle_status'=>1));
                    }
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>3));
                    break;
                case 2:
                    $m_approval_process->updateData($where,array('status'=>$status,'handle_status'=>3,'handle_time'=>date('Y-m-d H:i:s')));
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>2));
                    break;
                case 3:
                    if($res_process['step_order']==2){
                        $m_stock = new \Common\Model\Finance\StockModel();
                        $is_out = $m_stock->checkHotelThreshold($res_approval['hotel_id'],1);
                        if($is_out==0){
                            $this->to_back(94103);
                        }
                        $res_approval['now_staff_sysuser_id'] = $res_staff['sysuser_id'];
                        $stock_id = $m_stock->createOut($res_approval);
                        $m_approval->updateData(array('id'=>$approval_id),array('status'=>4,'stock_id'=>$stock_id));
                    }else{
                        $m_approval->updateData(array('id'=>$approval_id),array('status'=>6));
                    }
                    $m_approval_process->updateData($where,array('status'=>$status,'handle_status'=>2,'handle_time'=>date('Y-m-d H:i:s')));
                    break;
                case 4:
                    if(empty($work_staff_id)){
                        $this->to_back(1001);
                    }
                    $m_approval_process->updateData($where,array('status'=>$status,'handle_status'=>3,'handle_time'=>date('Y-m-d H:i:s'),
                        'allot_time'=>date('Y-m-d H:i:s'),'allot_ops_staff_id'=>$work_staff_id
                        ));
                    if(empty($res_next)){
                        $next_process = array('approval_id'=>$approval_id,'step_id'=>0,'step_order'=>$step_order,'area_id'=>$res_staff['area_id'],
                            'is_receive'=>1,'handle_status'=>1,'ops_staff_id'=>$work_staff_id);
                        $m_approval_process->add($next_process);
                    }else{
                        $m_approval_process->updateData(array('id'=>$res_next['id']),array('is_receive'=>1,'handle_status'=>1,'ops_staff_id'=>$work_staff_id));
                    }

                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>10));
                    break;
                case 5:
                    $m_approval_process->updateData($where,array('status'=>$status,'handle_status'=>2,'handle_time'=>date('Y-m-d H:i:s')));
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>8));
                    if($res_approval['stock_id']>0){
                        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
                        $m_stock_record->createReceiveCheckData($res_approval['stock_id'],$openid);
                    }
                    break;
                case 6:
                    $m_approval_process->updateData($where,array('status'=>3,'handle_status'=>2,'handle_time'=>date('Y-m-d H:i:s')));
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>6));
                    break;
                case 9:
                    if(empty($receipt_img)){
                        $this->to_back(1001);
                    }
                    $m_approval_process->updateData($where,array('handle_status'=>3));
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>9,'receipt_img'=>$receipt_img,'receipt_time'=>date('Y-m-d H:i:s')));
                    break;
                case 10:
                    $m_approval_process->updateData(array('id'=>$res_next['id']),array('handle_status'=>2,'status'=>3,'handle_time'=>date('Y-m-d H:i:s')));
                    break;
            }
        }
        $this->to_back(array('message'=>$message));
    }

    public function process11(){
        $openid = $this->params['openid'];
        $approval_id = intval($this->params['approval_id']);
        $processes_id = intval($this->params['processes_id']);
        $work_staff_id = intval($this->params['work_staff_id']);
        $status = intval($this->params['status']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ops_staff_id = $res_staff['id'];
        $m_approval = new \Common\Model\Crm\ApprovalModel();
        $m_approval_process = new \Common\Model\Crm\ApprovalProcessesModel();
        $res_process = $m_approval_process->getInfo(array('id'=>$processes_id,'approval_id'=>$approval_id,'ops_staff_id'=>$ops_staff_id,'is_receive'=>1));
        $message='';
        if(!empty($res_process)){
            $step_order = $res_process['step_order']+1;
            $res_next = $m_approval_process->getInfo(array('approval_id'=>$approval_id,'step_order'=>$step_order));
            $where = array('id'=>$processes_id);
            $message = '处理完毕';
            switch ($status){
                case 1:
                    if(empty($work_staff_id)){
                        $this->to_back(1001);
                    }
                    $m_approval_process->updateData($where,array('status'=>$status,'handle_status'=>3,'handle_time'=>date('Y-m-d H:i:s'),
                        'allot_time'=>date('Y-m-d H:i:s'),'allot_ops_staff_id'=>$work_staff_id
                        ));
                    if(!empty($res_next)){
                        $m_approval_process->updateData(array('id'=>$res_next['id']),array('is_receive'=>1,'handle_status'=>1,'ops_staff_id'=>$work_staff_id));
                    }
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>10));
                    break;
                case 2:
                    $m_approval_process->updateData($where,array('status'=>$status,'handle_status'=>3,'handle_time'=>date('Y-m-d H:i:s')));
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>2));
                    break;
                case 11:
                    $m_approval_process->updateData($where,array('status'=>3,'handle_status'=>2,'handle_time'=>date('Y-m-d H:i:s')));
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>11));
                    break;

            }
        }
        $this->to_back(array('message'=>$message));
    }

    public function process12(){
        $openid = $this->params['openid'];
        $approval_id = intval($this->params['approval_id']);
        $processes_id = intval($this->params['processes_id']);
        $work_staff_id = intval($this->params['work_staff_id']);
        $status = intval($this->params['status']);
        $latitude = $this->params['latitude'];
        $longitude = $this->params['longitude'];
        $idcodes = $this->params['idcodes'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ops_staff_id = $res_staff['id'];
        $m_approval = new \Common\Model\Crm\ApprovalModel();
        $res_approval = $m_approval->getInfo(array('id'=>$approval_id));
        $m_approval_process = new \Common\Model\Crm\ApprovalProcessesModel();
        $res_process = $m_approval_process->getInfo(array('id'=>$processes_id,'approval_id'=>$approval_id,'ops_staff_id'=>$ops_staff_id,'is_receive'=>1));
        $message='';
        if(!empty($res_process)){
            $step_order = $res_process['step_order']+1;
            $res_next = $m_approval_process->getInfo(array('approval_id'=>$approval_id,'step_order'=>$step_order));
            $where = array('id'=>$processes_id);
            $message = '处理完毕';
            switch ($status){
                case 1:
                    $hotel_id = $res_approval['goal_hotel_id'];
                    $m_hotel_ext = new \Common\Model\HotelExtModel();
                    $res_ext = $m_hotel_ext->getData('is_salehotel', array('hotel_id'=>$hotel_id));
                    if($res_ext[0]['is_salehotel']==0){
                        $this->to_back(94104);
                    }

                    $m_stock = new \Common\Model\Finance\StockModel();
                    $is_out = $m_stock->checkHotelThreshold($hotel_id,1);
                    if($is_out==0){
                        $this->to_back(94103);
                    }
                    $m_approval_process->updateData($where,array('status'=>$status,'handle_status'=>3,'handle_time'=>date('Y-m-d H:i:s')));
                    if(!empty($res_next)){
                        $m_approval_process->updateData(array('id'=>$res_next['id']),array('is_receive'=>1,'handle_status'=>1));
                    }
                    $res_approval['now_staff_sysuser_id'] = $res_staff['sysuser_id'];
                    $in_stock_id = $m_stock->createIn($res_approval);
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>5,'in_stock_id'=>$in_stock_id));
                    break;
                case 2:
                    $m_approval_process->updateData($where,array('status'=>$status,'handle_status'=>3,'handle_time'=>date('Y-m-d H:i:s')));
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>2));
                    break;
                case 3:
                    $m_stock = new \Common\Model\Finance\StockModel();
                    $is_out = $m_stock->checkHotelThreshold($res_approval['goal_hotel_id'],1);
                    if($is_out==0){
                        $this->to_back(94103);
                    }
                    $res_approval['now_staff_sysuser_id'] = $res_staff['sysuser_id'];
                    $res_approval['hotel_id'] = $res_approval['goal_hotel_id'];
                    $stock_id = $m_stock->createOut($res_approval);
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>4,'stock_id'=>$stock_id));

                    $m_approval_process->updateData($where,array('status'=>$status,'handle_status'=>2,'handle_time'=>date('Y-m-d H:i:s')));
                    break;
                case 4:
                    if(empty($work_staff_id)){
                        $this->to_back(1001);
                    }
                    $m_approval_process->updateData($where,array('status'=>$status,'handle_status'=>3,'handle_time'=>date('Y-m-d H:i:s'),
                        'allot_time'=>date('Y-m-d H:i:s'),'allot_ops_staff_id'=>$work_staff_id
                    ));
                    if(empty($res_next)){
                        $next_process = array('approval_id'=>$approval_id,'step_id'=>0,'step_order'=>$step_order,'area_id'=>$res_staff['area_id'],
                            'is_receive'=>1,'handle_status'=>1,'ops_staff_id'=>$work_staff_id);
                        $m_approval_process->add($next_process);
                    }else{
                        $m_approval_process->updateData(array('id'=>$res_next['id']),array('is_receive'=>1,'handle_status'=>1,'ops_staff_id'=>$work_staff_id));
                    }

                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>10));
                    break;
                case 7:
                    $all_idcodes = explode(',',$idcodes);
                    if(empty($all_idcodes)){
                        $this->to_back(1001);
                    }
                    $goods_data = json_decode($res_approval['wine_data'],true);
                    $goods_num = array_sum(array_values($goods_data));
                    if($goods_num!=count($all_idcodes)){
                        $this->to_back(94109);
                    }
                    $goods_ids = array_keys($goods_data);
                    $m_stock_record = new \Common\Model\Finance\StockRecordModel();
                    $where = array('goods_id'=>array('in',$goods_ids),'idcode'=>array('in',$all_idcodes),'type'=>2,'dstatus'=>1);
                    $res_record = $m_stock_record->getALLDataList('goods_id,idcode',$where,'id desc','','');
                    $goods_idcodes = array();
                    foreach ($res_record as $v){
                        $goods_idcodes[$v['goods_id']][]=$v['idcode'];
                    }
                    foreach($goods_idcodes as $k=>$v){
                        if($goods_data[$k]!=count($v)){
                            $this->to_back(94109);
                        }
                    }

                    $m_stock_record->updateData(array('idcode'=>array('in',$all_idcodes)),array('dstatus'=>2));
                    $in_stock_id = $res_approval['in_stock_id'];
                    $m_stockdetail = new \Common\Model\Finance\StockDetailModel();
                    $res_detail = $m_stockdetail->getALLDataList('id,stock_id,goods_id,unit_id',array('stock_id'=>$in_stock_id,'status'=>1),'id asc','','');
                    $price_idcodes = array();
                    foreach($res_detail as $v){
                        $now_goods_id = $v['goods_id'];
                        $stock_detail_id = $v['id'];
                        $unit_id = $v['unit_id'];
                        $now_goods_idcodes = $goods_idcodes[$now_goods_id];
                        $batch_no = getMillisecond();
                        foreach ($now_goods_idcodes as $iv){
                            $srwhere = array('idcode'=>$iv,'type'=>1,'price'=>array('gt',0));
                            $res_record = $m_stock_record->getALLDataList('price,total_fee',$srwhere,'id asc','0,1','');
                            $price = $total_fee = 0;
                            if(!empty($res_record)){
                                $price = $res_record[0]['price'];
                                $total_fee = $res_record[0]['total_fee'];
                                $price_idcodes[$iv] = array('price'=>$price,'total_fee'=>$total_fee);
                            }
                            $data = array('stock_id'=>$in_stock_id,'stock_detail_id'=>$stock_detail_id,'goods_id'=>$now_goods_id,'batch_no'=>$batch_no,'idcode'=>$iv,
                                'price'=>$price,'total_fee'=>$total_fee,'unit_id'=>$unit_id,'amount'=>1,'total_amount'=>1,'type'=>1,'op_openid'=>$openid
                            );
                            $res_in = $m_stock_record->getInfo(array('stock_id'=>$in_stock_id,'idcode'=>$iv,'type'=>1,'dstatus'=>1));
                            if(!empty($res_in)){
                                continue;
                            }
                            $m_stock_record->add($data);
                        }
                        $detail_amount = count($now_goods_idcodes);
                        $m_stockdetail->updateData(array('id'=>$stock_detail_id),array('amount'=>$detail_amount,'total_amount'=>$detail_amount));
                    }
                    $m_stock = new \Common\Model\Finance\StockModel();
                    $m_stock->updateData(array('id'=>$in_stock_id),array('status'=>2,'update_time'=>date('Y-m-d H:i:s')));

                    $out_stock_id = $res_approval['stock_id'];
                    $m_stockdetail = new \Common\Model\Finance\StockDetailModel();
                    $res_detail = $m_stockdetail->getALLDataList('id,stock_id,goods_id,unit_id',array('stock_id'=>$out_stock_id,'status'=>1),'id asc','','');
                    foreach($res_detail as $v){
                        $now_goods_id = $v['goods_id'];
                        $stock_detail_id = $v['id'];
                        $unit_id = $v['unit_id'];
                        $now_goods_idcodes = $goods_idcodes[$now_goods_id];
                        $batch_no = getMillisecond();
                        foreach ($now_goods_idcodes as $iv){
                            $price = -$price_idcodes[$iv]['price'];
                            $total_fee = -$price_idcodes[$iv]['total_fee'];

                            $data = array('stock_id'=>$out_stock_id,'stock_detail_id'=>$stock_detail_id,'goods_id'=>$now_goods_id,'batch_no'=>$batch_no,'idcode'=>$iv,
                                'price'=>$price,'total_fee'=>$total_fee,'unit_id'=>$unit_id,'amount'=>-1,'total_amount'=>-1,'type'=>2,'op_openid'=>$openid
                            );
                            $res_in = $m_stock_record->getInfo(array('stock_id'=>$out_stock_id,'idcode'=>$iv,'type'=>2,'dstatus'=>1));
                            if(!empty($res_in)){
                                continue;
                            }
                            $m_stock_record->add($data);
                        }
                        $detail_amount = count($now_goods_idcodes);
                        $m_stockdetail->updateData(array('id'=>$stock_detail_id),array('amount'=>-$detail_amount,'total_amount'=>-$detail_amount));
                    }
                    $m_stock_record->createReceiveCheckData($out_stock_id,$openid,0,4);

                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>7));
                    
                    $res_stock = $m_stock->getInfo(array('id'=>$out_stock_id));
                    if($res_stock['area_id']>0){
                        sendTopicMessage($res_stock['area_id'],70);
                    }
                    break;
                case 9:
                    if(empty($longitude) || empty($latitude)){
                        $this->to_back(1001);
                    }
                    $nearby_m = 200;
                    $bd_lnglat = gpsToBaidu($longitude, $latitude);
                    $latitude = $bd_lnglat['latitude'];
                    $longitude = $bd_lnglat['longitude'];
                    $m_hotel = new \Common\Model\HotelModel();
                    $res_hotel = $m_hotel->getOneById('gps',$res_approval['goal_hotel_id']);
                    $gps_arr = explode(',',$res_hotel['gps']);
                    $dis = geo_distance($latitude,$longitude,$gps_arr[1],$gps_arr[0]);
                    if($dis>$nearby_m){
                        $this->to_back(94112);
                    }
                    $m_stock_record = new \Common\Model\Finance\StockRecordModel();
                    $m_stock_record->createReceiveCheckData($res_approval['stock_id'],$openid,0,5);
                    
                    $m_approval_process->updateData($where,array('handle_status'=>3));
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>9));
                    break;

            }
        }
        $this->to_back(array('message'=>$message));
    }

    public function scancode(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];
        $approval_id = $this->params['approval_id'];

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
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        $m_approval = new \Common\Model\Crm\ApprovalModel();
        $res_approval = $m_approval->getInfo(array('id'=>$approval_id));
        $hotel_id = $res_approval['hotel_id'];

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $where = array('a.idcode'=>$idcode,'a.dstatus'=>1);
        $fileds = 'a.id,a.type,stock.hotel_id,goods.id as goods_id,goods.name as goods_name';
        $res_stock = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
        if(empty($res_stock) || $res_stock[0]['type']==1){
            $this->to_back(94106);
        }elseif($res_stock[0]['type']==7){
            $this->to_back(94107);
        }elseif($res_stock[0]['hotel_id']!=$hotel_id){
            $this->to_back(94108);
        }

        $goods_id = 0;
        if(!empty($res_stock[0]['goods_id'])){
            $goods_id = $res_stock[0]['goods_id'];
        }
        $goods_data = json_decode($res_approval['wine_data'],true);
        $all_goods_ids = array_keys($goods_data);
        if(!in_array($goods_id,$all_goods_ids)){
            $this->to_back(94110);
        }
        $goods_name = '';
        if(!empty($res_stock[0]['goods_name'])){
            $goods_name = $res_stock[0]['goods_name'];
        }
        $this->to_back(array('idcode'=>$idcode,'checked'=>true,'goods_id'=>$goods_id,'goods_name'=>$goods_name,'add_time'=>date('Y-m-d H:i:s')));
    }


}