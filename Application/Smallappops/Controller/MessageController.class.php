<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class MessageController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'datalist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'page'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'message_id'=>1001);
                break;
            case 'hotels':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001);
                break;
        }
        parent::_init_();
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $type = intval($this->params['type']);
        $page = intval($this->params['page']);
        $pagesize = 20;

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $offset = ($page-1)*$pagesize;
        $datalist = array();

        $m_message = new \Common\Model\Smallapp\MessageModel();
        $where = array('a.ops_staff_id'=>$res_staff['id'],'a.type'=>$type);
        $fields = 'a.id,a.staff_openid,a.ops_staff_id,a.hotel_id,a.content_id,a.read_status,a.add_time,hotel.name as hotel_name';
        $res_message = $m_message->getMessageInfo($fields,$where,'a.id desc',"$offset,$pagesize");
        if(!empty($res_message)){
            $day = date('Y.m.d');
            $yesterday = date('Y.m.d',strtotime('-1 day'));
            $date_map = array("$day"=>'今天',"$yesterday"=>'昨天');
            $color_stock_check_status = array(
                '21'=>array('name'=>'','color'=>'green'),
                '22'=>array('name'=>'盘赢','color'=>'orange2'),
                '23'=>array('name'=>'盘亏','color'=>'red'),
                '24'=>array('name'=>'盘赢+盘亏','color'=>'red'),
            );
            $m_stock_check = new \Common\Model\Smallapp\StockcheckModel();
            $tmp_datas = array();
            foreach ($res_message as $v){
                $mdate = date('Y.m.d',strtotime($v['add_time']));

                $hotel_name = $v['hotel_name'];
                $stockcheck_id = $v['content_id'];
                $res_stock_check = $m_stock_check->getInfo(array('id'=>$stockcheck_id));
                $color = '';
                $content = '';
                if(isset($color_stock_check_status[$res_stock_check['stock_check_success_status']])){
                    $color = $color_stock_check_status[$res_stock_check['stock_check_success_status']]['color'];
                    $content = '盘点任务已完成';
                    if(!empty($color_stock_check_status[$res_stock_check['stock_check_success_status']]['name'])){
                        $content.="，{$color_stock_check_status[$res_stock_check['stock_check_success_status']]['name']}，请尽快处理";
                    }
                }
                $tmp_datas[$mdate][]=array('message_id'=>$v['id'],'name'=>$hotel_name,'stockcheck_id'=>$stockcheck_id,'color'=>$color,'read_status'=>$v['read_status'],
                    'ops_staff_id'=>$v['ops_staff_id'],'content'=>$content,'add_time'=>date('H:i',strtotime($v['add_time'])));
            }
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(22);
            $cache_key = C('SAPP_OPS').'msgschotels:'.$res_staff['id'];
            $res_msghotel = $redis->get($cache_key);
            if(!empty($res_msghotel)){
                $hotel_ids = explode(',',$res_msghotel);
                $num = count($hotel_ids);
                $content = "{$num}家餐厅盘点异常，请及时处理";
                $msg = array('message_id'=>0,'name'=>'异常盘点汇总','stockcheck_id'=>0,'color'=>'green','read_status'=>1,
                    'ops_staff_id'=>$res_staff['id'],'content'=>$content,'add_time'=>'9:00');
                if(isset($tmp_datas[$day])){
                    $day_msg = $tmp_datas[$day];
                    array_unshift($day_msg,$msg);
                    $tmp_datas[$day] = $day_msg;
                }else{
                    $tmp_datas[$day][] = $msg;
                }
            }


            foreach ($tmp_datas as $k=>$v){
                $day = isset($date_map[$k])?$date_map[$k]:$k;
                $datalist[]=array('day'=>$day,'list'=>$v);
            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function detail(){
        $openid = $this->params['openid'];
        $message_id = intval($this->params['message_id']);

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_message = new \Common\Model\Smallapp\MessageModel();
        $res_message = $m_message->getInfo(array('id'=>$message_id));
        $stockcheck_id = $res_message['content_id'];
        $m_stock_check = new \Common\Model\Smallapp\StockcheckModel();
        $res_info = $m_stock_check->getInfo(array('id'=>$stockcheck_id));

        $m_stock_check_record = new \Common\Model\Smallapp\StockcheckRecordModel();
        $fields = 'record.goods_id,record.idcode,record.is_check,record.type,goods.name as goods_name';
        $res_list = $m_stock_check_record->getCheckRecordList($fields,array('record.stockcheck_id'=>$stockcheck_id),'record.id desc');
        $idcodes = $other_idcodes = array();
        foreach ($res_list as $v){
            $checked = false;
            if($v['is_check'] || $v['type']==2){
                $checked = true;
            }
            $v['checked'] = $checked;
            if($v['type']==1){
                $idcodes[]=$v;
            }else{
                $other_idcodes[]=$v;
            }
        }
        $where = array('a.id'=>$res_info['staff_id'],'merchant.status'=>1);
        $fields = 'a.id,user.nickName';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getOneById('name',$res_info['hotel_id']);
        $resp_data = array('hotel_id'=>$res_info['hotel_id'],'hotel_name'=>$res_hotel['name'],'stock_check_status'=>$res_info['stock_check_status'],'stock_check_success_status'=>$res_info['stock_check_success_status'],
            'is_handle_stock_check'=>$res_info['is_handle_stock_check'],'stock_check_num'=>$res_info['stock_check_num'],'stock_check_hadnum'=>$res_info['stock_check_hadnum'],
            'nickName'=>$res_staff[0]['nickName'],'add_time'=>$res_info['add_time'],'idcodes'=>$idcodes,'other_idcodes'=>$other_idcodes);
        $m_message->updateData(array('id'=>$message_id),array('read_status'=>2));
        $this->to_back($resp_data);
    }

    public function hotels(){
        $openid   = $this->params['openid'];
        $page = intval($this->params['page']);
        $pagesize = 20;

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(22);
        $cache_key = C('SAPP_OPS').'msgschotels:'.$res_staff['id'];
        $res_msghotel = $redis->get($cache_key);
        $datalist = array();
        if(!empty($res_msghotel)){
            $start = ($page-1)*$pagesize;
            $limit = $start.','.$pagesize;
            $m_hotels = new \Common\Model\HotelModel();
            $fields = 'hotel.id as hotel_id,hotel.name as hotel_name,hotel.addr';
            $datalist = $m_hotels->getHotelDataList($fields,array('hotel.id'=>array('in',$res_msghotel)),'',$limit);
        }
        $this->to_back(array('datalist'=>$datalist));
    }


}