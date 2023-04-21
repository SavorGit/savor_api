<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class StockcheckController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'scancode':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'idcode'=>1001,'hotel_id'=>1001);
                break;
            case 'getidcodelist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'idcode'=>1001);
                break;
            case 'location':
                $this->is_verify = 1;
                $this->valid_fields = array('latitude'=>1001,'longitude'=>1001,'openid'=>1001,'hotel_id'=>1001);
                break;
            case 'addcheckrecord':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'idcodes'=>1001,'other_idcodes'=>1002,
                    'content'=>1002,'review_uid'=>1002,'cc_uids'=>1002);
                break;
            case 'getrecordcodelist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'salerecord_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function scancode(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];
        $hotel_id = $this->params['hotel_id'];

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

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $where = array('a.idcode'=>$idcode,'a.dstatus'=>1);
        $fileds = 'a.id,stock.hotel_id,goods.id as goods_id,goods.name as goods_name';
        $res_stock = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
        $idcode_hotel_id = intval($res_stock[0]['hotel_id']);
        if($hotel_id!=$idcode_hotel_id){
            $this->to_back(94006);
        }
        $this->to_back(array('idcode'=>$idcode,'goods_id'=>$res_stock[0]['goods_id'],'goods_name'=>$res_stock[0]['goods_name']));
    }


    public function getidcodelist(){
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

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $where = array('a.idcode'=>$idcode,'a.dstatus'=>1);
        $fileds = 'a.id,stock.hotel_id';
        $res_stock = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
        $hotel_id = intval($res_stock[0]['hotel_id']);

        $where = array('stock.hotel_id'=>$hotel_id,'a.dstatus'=>1);
        $fileds = 'a.idcode,goods.id as goods_id,goods.name as goods_name,GROUP_CONCAT(a.type) as all_type';
        $res_allidcodes = $m_stock_record->getStockRecordList($fileds,$where,'','','a.idcode');
        $datalist = array();
        foreach ($res_allidcodes as $v){
            $all_types = explode(',',$v['all_type']);
            if(in_array(5,$all_types) && !in_array(7,$all_types)){
                $checked=false;
                if($v['idcode']==$idcode){
                    $checked=true;
                }
                $datalist[]=array('idcode'=>$v['idcode'],'goods_id'=>$v['goods_id'],'goods_name'=>$v['goods_name'],'checked'=>$checked);
            }
        }
        $res_data = array('hotel_id'=>$hotel_id,'datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function location(){
        $latitude = $this->params['latitude'];
        $longitude = $this->params['longitude'];
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $bd_lnglat = getgeoByTc($latitude, $longitude);
        $latitude = $bd_lnglat[0]['y'];
        $longitude = $bd_lnglat[0]['x'];

        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getOneById('id,gps',$hotel_id);
        $gps_arr = explode(',',$res_hotel['gps']);
        $dis = geo_distance($latitude,$longitude,$gps_arr[1],$gps_arr[0]);
        if($dis>200){
            $this->to_back(94007);
        }
        $this->to_back(array('dis'=>$dis));
    }

    public function addcheckrecord(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $idcodes = $this->params['idcodes'];
        $other_idcodes = $this->params['other_idcodes'];
        $content = $this->params['content'];
        $review_uid = $this->params['review_uid'];
        $cc_uids = $this->params['cc_uids'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $now_idcodes = explode(',',$idcodes);
        $now_other_idcodes = array();
        $stock_check_error = 1;
        if(!empty($other_idcodes)){
            $now_other_idcodes = explode(',',$other_idcodes);
            $stock_check_error = 2;
        }
        $where = array('stock.hotel_id'=>$hotel_id,'a.dstatus'=>1);
        $fileds = 'a.idcode,goods.id as goods_id,GROUP_CONCAT(a.type) as all_type';
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $res_allidcodes = $m_stock_record->getStockRecordList($fileds,$where,'','','a.idcode');
        $check_list = array();
        $stock_check_num=$stock_check_hadnum=0;
        foreach ($res_allidcodes as $v){
            $all_types = explode(',',$v['all_type']);
            if(in_array(5,$all_types) && !in_array(7,$all_types)){
                $stock_check_num++;
                $is_check = 0;
                if(in_array($v['idcode'],$now_idcodes)){
                    $is_check = 1;
                    $stock_check_hadnum++;
                }
                $check_list[]=array('idcode'=>$v['idcode'],'goods_id'=>$v['goods_id'],'is_check'=>$is_check,'type'=>1);
            }
            if(in_array($v['idcode'],$now_other_idcodes)){
                $check_list[]=array('idcode'=>$v['idcode'],'goods_id'=>$v['goods_id'],'is_check'=>0,'type'=>2);
            }
        }
        $stock_check_status = 1;
        if($stock_check_num==$stock_check_hadnum){
            $stock_check_status = 2;
        }
        $stock_check_errornum = count($now_other_idcodes);

        $ops_staff_id = $res_staff['id'];
        $add_data = array('ops_staff_id'=>$ops_staff_id,'signin_hotel_id'=>$hotel_id,'signin_time'=>date('Y-m-d H:i:s'),
            'status'=>2,'stock_check_num'=>$stock_check_num,'stock_check_hadnum'=>$stock_check_hadnum,'stock_check_status'=>$stock_check_status,
            'stock_check_error'=>$stock_check_error,'stock_check_errornum'=>$stock_check_errornum,'type'=>2);
        if(!empty($content)){
            $add_data['content'] = $content;
        }
        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $salerecord_id = $m_salerecord->add($add_data);
        if(!empty($check_list)){
            $m_stock_check_record = new \Common\Model\Crm\StockcheckRecordModel();
            foreach ($check_list as $k=>$v){
                $check_list[$k]['salerecord_id']=$salerecord_id;
                $check_list[$k]['hotel_id']=$hotel_id;
            }
            $m_stock_check_record->addAll($check_list);
        }

        $add_remind = array(array('salerecord_id'=>$salerecord_id,'type'=>4,'remind_user_id'=>$ops_staff_id));
        if(!empty($review_uid)){
            $add_remind[] = array('salerecord_id'=>$salerecord_id,'type'=>1,'remind_user_id'=>$review_uid);
        }
        if(!empty($cc_uids)){
            $arr_cc_uids = explode(',',$cc_uids);
            foreach ($arr_cc_uids as $v){
                $remind_user_id = intval($v);
                if($remind_user_id>0){
                    $add_remind[] = array('salerecord_id'=>$salerecord_id,'type'=>2,'remind_user_id'=>$remind_user_id);
                }
            }
        }
        $m_saleremind = new \Common\Model\Crm\SalerecordRemindModel();
        $m_saleremind->addAll($add_remind);

        $this->to_back(array('salerecord_id'=>$salerecord_id));
    }

    public function getrecordcodelist(){
        $openid = $this->params['openid'];
        $salerecord_id = intval($this->params['salerecord_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_stock_check_record = new \Common\Model\Crm\StockcheckRecordModel();
        $fields = 'record.goods_id,record.idcode,record.is_check,record.type,goods.name as goods_name';
        $res_list = $m_stock_check_record->getCheckRecordList($fields,array('record.salerecord_id'=>$salerecord_id),'record.id desc');
        $idcodes = $other_idcodes = array();
        foreach ($res_list as $v){
            if($v['type']==1){
                $idcodes[]=$v;
            }else{
                $other_idcodes[]=$v;
            }
        }
        $this->to_back(array('idcodes'=>$idcodes,'other_idcodes'=>$other_idcodes));
    }
}