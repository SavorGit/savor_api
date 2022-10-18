<?php
namespace Smallsale22\Controller;
use \Common\Controller\CommonController as CommonController;

class LotteryController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'datalist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                break;
            case 'startLottery':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'box_mac'=>1001,'syslottery_id'=>1001,
                    'people_num'=>1001,'start_time'=>1002);
                break;
            case 'scanGoodsCode':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'idcode'=>1001);
                break;
            case 'startSellwineLottery':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'idcode'=>1001,'hotel_id'=>1001,'room_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }

        $m_syslottery = new \Common\Model\Smallapp\SyslotteryModel();
        $where = array('hotel_id'=>$hotel_id,'status'=>1,'type'=>2);
        $orderby = 'id desc';
        $fields = 'id as syslottery_id,prize as name';
        $res_data = $m_syslottery->getDataList($fields,$where,$orderby);
        $this->to_back($res_data);
    }

    public function startLottery(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $box_mac = $this->params['box_mac'];
        $syslottery_id = $this->params['syslottery_id'];
        $people_num = $this->params['people_num'];
        $start_time = $this->params['start_time'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_syslottery = new \Common\Model\Smallapp\SyslotteryModel();
        $res_sinfo = $m_syslottery->getInfo(array('id'=>$syslottery_id));
        $now_time = time();
        if(empty($start_time)){
            $start_time = date('Y-m-d H:i:s');
            $end_time = date('Y-m-d H:i:s',$now_time+1800);
            $status = 2;
        }else{
            $start_time = date("Y-m-d $start_time:00");
            $stime = strtotime($start_time);
            if($stime<$now_time){
                $this->to_back(93075);
            }
            $end_time = date('Y-m-d H:i:s',$stime+1800);
            $status = 0;
        }

        $add_activity_data = array('hotel_id'=>$hotel_id,'openid'=>$openid,'name'=>'幸运抽奖','prize'=>$res_sinfo['prize'],
            'box_mac'=>$box_mac,'people_num'=>$people_num,'start_time'=>$start_time,'end_time'=>$end_time,
            'type'=>11,'status'=>$status);
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $activity_id = $m_activity->add($add_activity_data);

        $m_lotteryprize = new \Common\Model\Smallapp\SyslotteryPrizeModel();
        $res_lottery_prize = $m_lotteryprize->getDataList('*',array('syslottery_id'=>$syslottery_id),'id desc');

        $prize_data = array();
        $is_prizepool = 0;
        foreach ($res_lottery_prize as $pv){
            $prize_data[]=array('activity_id'=>$activity_id,'name'=>$pv['name'],'money'=>$pv['money'],'image_url'=>$pv['image_url'],
                'prizepool_prize_id'=>$pv['prizepool_prize_id'],'probability'=>$pv['probability'],'type'=>$pv['type']
            );
            if($pv['prizepool_prize_id']>0){
                $is_prizepool = 1;
            }
        }
        $m_activityprize = new \Common\Model\Smallapp\ActivityprizeModel();
        $m_activityprize->addAll($prize_data);

        if($is_prizepool==0){
            //分配中奖信息
            $lottery_num = $people_num*2;
            $position = array();
            for($i=1;$i<=$lottery_num;$i++){
                $position[]=$i;
            }
            shuffle($position);
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(1);
            $cache_key = C('SAPP_LUCKYLOTTERY_POSITION').$activity_id;
            $position = array_slice($position,0,$people_num);
            $redis->set($cache_key,json_encode($position),86400);
        }

        $code_url = '';
        if($status==2){
            $m_box = new \Common\Model\BoxModel();
            $where = array('a.mac'=>$box_mac,'a.state'=>1,'a.flag'=>0,'d.id'=>$hotel_id);
            $fileds = 'a.id as box_id,d.name,ext.hotel_cover_media_id';
            $res_box = $m_box->getBoxInfo($fileds,$where);

            $hotel_logo = '';
            if($res_box[0]['hotel_cover_media_id']>0){
                $m_media = new \Common\Model\MediaModel();
                $res_media = $m_media->getMediaInfoById($res_box[0]['hotel_cover_media_id']);
                $hotel_logo = $res_media['oss_addr'];
            }
            $headPic = base64_encode($hotel_logo);
            $host_name = C('HOST_NAME');
            $code_url = $host_name."/basedata/forscreenQrcode/getBoxQrcode?box_id={$res_box[0]['box_id']}&box_mac={$box_mac}&data_id={$activity_id}&type=46";
            $message = array('action'=>138,'countdown'=>120,'nickName'=>$res_box[0]['name'],'headPic'=>$headPic,'codeUrl'=>$code_url);
            $m_netty = new \Common\Model\NettyModel();
            $res_netty = $m_netty->pushBox($box_mac,json_encode($message));
            if($res_netty['error_code']){
                $this->to_back($res_netty['error_code']);
            }
        }
        $this->to_back(array('activity_id'=>$activity_id,'qrcode_url'=>$code_url));
    }


    public function scanGoodsCode(){
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
        $record_info = $m_stock_record->getALLDataList('*',array('idcode'=>$idcode,'dstatus'=>1),'id desc','0,1','');
        $m_stock = new \Common\Model\Finance\StockModel();
        $res_stock = $m_stock->getInfo(array('id'=>$record_info[0]['stock_id']));
        if($res_stock['io_type']!=22){
            $this->to_back(93093);
        }
        $goods_id = $record_info[0]['goods_id'];
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goods = $m_goods->getInfo(array('finance_goods_id'=>$goods_id,'is_lottery'=>1));
        if(empty($res_goods)){
            $this->to_back(93202);
        }

        $m_unit = new \Common\Model\Finance\UnitModel();
        $res_unit = $m_unit->getInfo(array('id'=>$record_info[0]['unit_id']));
        $m_finance_goods = new \Common\Model\Finance\GoodsModel();
        $fileds = 'goods.name,cate.name as cate_name,spec.name as sepc_name';
        $res_finance_goods = $m_finance_goods->getGoodsInfo($fileds,array('goods.id'=>$goods_id));
        $res = array('idcode'=>$idcode,'add_time'=>date('Y-m-d H:i:s'),'goods_id'=>$goods_id,'name'=>$res_finance_goods[0]['name'],
            'cate_name'=>$res_finance_goods[0]['cate_name'],'sepc_name'=>$res_finance_goods[0]['sepc_name'],'unit_name'=>$res_unit['name']
        );

        if($record_info[0]['type']==5){
            $this->to_back($res);
        }else{
            if($record_info[0]['type']==7 && $record_info[0]['wo_status']==4){
                $this->to_back($res);
            }elseif($record_info[0]['type']==6){
                $this->to_back(93095);
            }else{
                $this->to_back(93096);
            }
        }
    }

    public function startSellwineLottery(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];
        $hotel_id = $this->params['hotel_id'];
        $room_id = $this->params['room_id'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_syslottery = new \Common\Model\Smallapp\SyslotteryModel();
        $where = array('hotel_id'=>$hotel_id,'status'=>1,'type'=>5);
        $orderby = 'id desc';
        $fields = 'id as syslottery_id,prize as name';
        $res_syslottery = $m_syslottery->getDataList($fields,$where,$orderby,0,1);
        if($res_syslottery['total']<=0){
            $this->to_back(93209);
        }

        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getALLDataList('*',array('idcode'=>$idcode),'id desc','0,1','');
        $is_new_activity = 0;
        if(!empty($res_activity)){
            $now_time = date('Y-m-d H:i:s');
            if($now_time>$res_activity[0]['end_time']){
                $this->to_back(93210);
            }
            $activity_id = $res_activity[0]['id'];
        }else{
            $is_new_activity = 1;
            $now_syslottery_id = $res_syslottery['list'][0]['syslottery_id'];
            $prize = $res_syslottery['list'][0]['name'];
            $m_lotteryprize = new \Common\Model\Smallapp\SyslotteryPrizeModel();
            $res_lottery_prize = $m_lotteryprize->getDataList('*',array('syslottery_id'=>$now_syslottery_id,'status'=>1),'id desc');
            $prize_data = array();
            foreach ($res_lottery_prize as $pv){
                $prize_data[]=array('name'=>$pv['name'],'money'=>$pv['money'],'image_url'=>$pv['image_url'],
                    'probability'=>$pv['probability'],'prizepool_prize_id'=>$pv['prizepool_prize_id'],'type'=>$pv['type']
                );
            }
            $now_time = time();
            $start_time = date('Y-m-d H:i:s');
            $end_time = date('Y-m-d H:i:s',$now_time+1800);
            $status = 2;
            $add_activity_data = array('hotel_id'=>$hotel_id,'openid'=>$openid,'name'=>'幸运抽奖','prize'=>$prize,
                'room_id'=>$room_id,'people_num'=>1,'start_time'=>$start_time,'end_time'=>$end_time,'idcode'=>$idcode,
                'syslottery_id'=>$now_syslottery_id,'type'=>14,'status'=>$status);
            $activity_id = $m_activity->add($add_activity_data);
            $all_prize_data = array();
            foreach ($prize_data as $pv){
                $all_prize_data[]=array('activity_id'=>$activity_id,'name'=>$pv['name'],'money'=>$pv['money'],'image_url'=>$pv['image_url'],
                    'probability'=>$pv['probability'],'prizepool_prize_id'=>$pv['prizepool_prize_id'],'type'=>$pv['type']
                );
            }
            $m_activityprize = new \Common\Model\Smallapp\ActivityprizeModel();
            $m_activityprize->addAll($all_prize_data);
        }


        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getHotelById('hotel.name,ext.hotel_cover_media_id',array('hotel.id'=>$hotel_id));
        $headPic = '';
        if($res_hotel['hotel_cover_media_id']>0){
            $m_media = new \Common\Model\MediaModel();
            $res_media = $m_media->getMediaInfoById($res_hotel['hotel_cover_media_id']);
            $headPic = base64_encode($res_media['oss_addr']);
        }
        $host_name = C('HOST_NAME');
        $code_url = '';
        $m_box = new \Common\Model\BoxModel();
        $bwhere = array('hotel.id'=>$hotel_id,'room.id'=>$room_id,'box.state'=>1,'box.flag'=>0);
        $fileds = 'box.id as box_id,box.mac as box_mac';
        $res_box = $m_box->getBoxByCondition($fileds,$bwhere);
        if(!empty($res_box)){
            foreach ($res_box as $v){
                $code_url = $host_name."/basedata/forscreenQrcode/getBoxQrcode?box_id={$v['box_id']}&box_mac={$v['box_mac']}&data_id={$activity_id}&type=49";
                $message = array('action'=>138,'countdown'=>120,'nickName'=>$res_hotel['name'],'headPic'=>$headPic,'codeUrl'=>$code_url);
                if($is_new_activity){
                    $m_netty = new \Common\Model\NettyModel();
                    $m_netty->pushBox($v['box_mac'],json_encode($message));
                }
            }
        }else{
            $code_url = $host_name."/basedata/forscreenQrcode/getBoxQrcode?box_id=0&box_mac=0&data_id={$activity_id}&type=49";
        }

        if($is_new_activity){
            $m_stock_record = new \Common\Model\Finance\StockRecordModel();
            $batch_no = date('YmdHis');
            $res_record = $m_stock_record->getALLDataList('*',array('idcode'=>$idcode,'dstatus'=>1),'id desc','0,1','');
            if(!empty($res_record)){
                $reason_type = 0;
                $data_imgs = '';

                $add_data = $res_record[0];
                $goods_ids[]=$add_data['goods_id'];
                if($add_data['type']==7){
                    if($add_data['wo_reason_type']==0){
                        $up_data = array('op_openid'=>$openid,'batch_no'=>$batch_no,'wo_reason_type'=>$reason_type,
                            'wo_data_imgs'=>$data_imgs,'wo_status'=>4,'wo_num'=>$add_data['wo_num']+1,'update_time'=>date('Y-m-d H:i:s')
                        );
                        $m_stock_record->updateData(array('id'=>$add_data['id']),$up_data);
                    }
                }else{
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
                    $add_data['wo_status'] = 4;
                    $add_data['wo_num'] = 1;
                    $add_data['update_time'] = date('Y-m-d H:i:s');
                    $add_data['add_time'] = date('Y-m-d H:i:s');
                    $m_stock_record->add($add_data);
                }
            }
        }
        $this->to_back(array('activity_id'=>$activity_id,'qrcode_url'=>$code_url));
    }




}