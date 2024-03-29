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
                    'video_path'=>1002,'content'=>1002,'review_uid'=>1002,'cc_uids'=>1002,'is_check_error'=>1002,'type'=>1002);
                break;
            case 'getrecordcodelist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'salerecord_id'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'salerecord_id'=>1001,'content'=>1002,'review_uid'=>1002,'cc_uids'=>1002);
                break;
            case 'edit':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'salerecord_id'=>1001);
                break;
            case 'statcheckdata':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'stat_date'=>1002);
                break;
            case 'hotelchecklist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'page'=>1001,'pagesize'=>1002);
                break;
            case 'hotelfilter':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'area_id'=>1002,'stat_date'=>1002,'hotel_id'=>1002,'check_status'=>1002);
                break;
            case 'stathotellist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'stat_date'=>1001,'page'=>1001,'area_id'=>1002,'hotel_id'=>1002,'check_status'=>1002);
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
        $fileds = 'a.id,a.type,stock.hotel_id,goods.id as goods_id,goods.name as goods_name';
        $res_stock = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
        $desc = '';
        if(empty($res_stock) || $res_stock[0]['type']==1){
            $desc = '未出库到餐厅';
        }elseif($res_stock[0]['type']==7){
            $desc = '酒水已经核销';
        }elseif($res_stock[0]['hotel_id']!=$hotel_id){
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getOneById('id,name',$res_stock[0]['hotel_id']);
            $desc = "属于{$res_hotel['name']}的酒水";
        }
        $goods_id = 0;
        if(!empty($res_stock[0]['goods_id'])){
            $goods_id = $res_stock[0]['goods_id'];
        }
        $goods_name = '';
        if(!empty($res_stock[0]['goods_name'])){
            $goods_name = $res_stock[0]['goods_name'];
        }
        $this->to_back(array('idcode'=>$idcode,'goods_id'=>$goods_id,'goods_name'=>$goods_name,'desc'=>$desc));
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
        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $checkwhere = array('signin_hotel_id'=>$hotel_id,'type'=>2,'stock_check_status'=>2);
        $checkwhere["date_format(add_time,'%Y-%m')"] = date('Y-m');
        $res_salerecord = $m_salerecord->getInfo($checkwhere);
        if(!empty($res_salerecord)){
            $this->to_back(94008);
        }
        $idcodes = array();
        $other_idcodes = array();
        if($hotel_id>0){
            $where = array('stock.hotel_id'=>$hotel_id,'stock.type'=>20,'stock.io_type'=>22,'a.dstatus'=>1);
            $fileds = 'a.idcode,goods.id as goods_id,goods.name as goods_name,GROUP_CONCAT(a.type) as all_type';
            $res_allidcodes = $m_stock_record->getStockRecordList($fileds,$where,'','','a.idcode');
            foreach ($res_allidcodes as $v){
                $all_types = explode(',',$v['all_type']);
                if(count($all_types)==1 && $all_types[0]==3){
                    continue;
                }
                if(!in_array(6,$all_types) && !in_array(7,$all_types)){
                    $checked=false;
                    if($v['idcode']==$idcode){
                        $checked=true;
                    }
                    $idcodes[]=array('idcode'=>$v['idcode'],'goods_id'=>$v['goods_id'],'goods_name'=>$v['goods_name'],'checked'=>$checked);
                }else{
                    if($v['idcode']==$idcode){
                        $checked=true;
                        if(in_array(7,$all_types)){
                            $cwhere = array('a.idcode'=>$v['idcode'],'a.dstatus'=>1);
                            $res_code = $m_stock_record->getStockRecordList('a.type,a.wo_status',$cwhere,'a.id desc','0,1');
                            if($res_code[0]['wo_status']==3){
                                $idcodes[]=array('idcode'=>$v['idcode'],'goods_id'=>$v['goods_id'],'goods_name'=>$v['goods_name'],'checked'=>$checked);
                            }else{
                                $desc = '酒水已经核销';
                                $other_idcodes[]=array('idcode'=>$v['idcode'],'goods_id'=>$v['goods_id'],'goods_name'=>$v['goods_name'],'checked'=>$checked,'desc'=>$desc);
                            }
                        }else{
                            $desc = '';
                            $other_idcodes[]=array('idcode'=>$v['idcode'],'goods_id'=>$v['goods_id'],'goods_name'=>$v['goods_name'],'checked'=>$checked,'desc'=>$desc);
                        }
                    }else{
                        if(in_array(7,$all_types)){
                            $cwhere = array('a.idcode'=>$v['idcode'],'a.dstatus'=>1);
                            $res_code = $m_stock_record->getStockRecordList('a.type,a.wo_status',$cwhere,'a.id desc','0,1');
                            if($res_code[0]['wo_status']==3){
                                $checked=false;
                                $idcodes[]=array('idcode'=>$v['idcode'],'goods_id'=>$v['goods_id'],'goods_name'=>$v['goods_name'],'checked'=>$checked);
                            }
                        }
                    }
                }
            }
        }
        $res_data = array('hotel_id'=>$hotel_id,'idcodes'=>$idcodes,'other_idcodes'=>$other_idcodes);
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
//        $bd_lnglat = getgeoByTc($latitude, $longitude);
//        $latitude = $bd_lnglat[0]['y'];
//        $longitude = $bd_lnglat[0]['x'];
        $bd_lnglat = gpsToBaidu($longitude, $latitude);
        $latitude = $bd_lnglat['latitude'];
        $longitude = $bd_lnglat['longitude'];

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
        $video_path = $this->params['video_path'];
        $type = intval($this->params['type']);//类型1保存2提交
        $is_check_error = intval($this->params['is_check_error']);
        if(empty($type)){
            $type = 2;
        }
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
        $where = array('stock.hotel_id'=>$hotel_id,'stock.type'=>20,'stock.io_type'=>22,'a.dstatus'=>1);
        $fileds = 'a.idcode,goods.id as goods_id,GROUP_CONCAT(a.type) as all_type';
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $res_allidcodes = $m_stock_record->getStockRecordList($fileds,$where,'','','a.idcode');
        $check_list = array();
        $stock_check_num=$stock_check_hadnum=0;
        foreach ($res_allidcodes as $v){
            $all_types = explode(',',$v['all_type']);
            if(count($all_types)==1 && $all_types[0]==3){
                continue;
            }
            if(!in_array(6,$all_types) && !in_array(7,$all_types)){
                $stock_check_num++;
                $is_check = 0;
                if(in_array($v['idcode'],$now_idcodes)){
                    $is_check = 1;
                    $stock_check_hadnum++;
                }
                $check_list[]=array('idcode'=>$v['idcode'],'goods_id'=>$v['goods_id'],'hotel_id'=>$hotel_id,'idcode_hotel_id'=>$hotel_id,
                    'is_check'=>$is_check,'type'=>1,'desc'=>'');
            }else{
                if(in_array(7,$all_types)){
                    $cwhere = array('a.idcode'=>$v['idcode'],'a.dstatus'=>1);
                    $res_code = $m_stock_record->getStockRecordList('a.type,a.wo_status',$cwhere,'a.id desc','0,1');
                    if($res_code[0]['wo_status']==3){
                        $stock_check_num++;
                        $is_check = 0;
                        if(in_array($v['idcode'],$now_idcodes)){
                            $is_check = 1;
                            $stock_check_hadnum++;
                        }
                        $check_list[]=array('idcode'=>$v['idcode'],'goods_id'=>$v['goods_id'],'hotel_id'=>$hotel_id,'idcode_hotel_id'=>$hotel_id,
                            'is_check'=>$is_check,'type'=>1,'desc'=>'');
                    }
                }
            }
        }
        if(!empty($now_other_idcodes)){
            $m_hotel = new \Common\Model\HotelModel();
            foreach ($now_other_idcodes as $v){
                $where = array('a.idcode'=>$v,'a.dstatus'=>1);
                $fileds = 'a.id,a.type,stock.hotel_id,goods.id as goods_id';
                $res_srecord = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
                $desc = '';
                if($res_srecord[0]['type']==1){
                    $desc = '未出库到餐厅';
                }elseif($res_srecord[0]['type']==7){
                    $desc = '酒水已经核销';
                }elseif($res_srecord[0]['hotel_id']!=$hotel_id){
                    $res_hotel = $m_hotel->getOneById('id,name',$res_srecord[0]['hotel_id']);
                    $desc = "属于{$res_hotel['name']}的酒水";
                }
                $check_list[]=array('idcode'=>$v,'goods_id'=>$res_srecord[0]['goods_id'],'hotel_id'=>$hotel_id,
                    'idcode_hotel_id'=>$res_srecord[0]['hotel_id'],'is_check'=>0,'type'=>2,'desc'=>$desc);
            }
        }
        $stock_check_errornum = count($now_other_idcodes);
        $stock_check_success_status = 0;
        $stock_check_status = 1;
        if($type==2){
            if($is_check_error==1){
                $stock_check_status = 2;
                if($stock_check_errornum==0){
                    $stock_check_success_status = 23;
                }else{
                    $stock_check_success_status = 24;
                }
            }else{
                if($stock_check_num==$stock_check_hadnum){
                    $stock_check_status = 2;
                    if($stock_check_errornum==0){
                        $stock_check_success_status = 21;
                    }else{
                        $stock_check_success_status = 22;
                    }
                }
            }
            $status = 2;
        }else{
            $status = 1;
        }

        $ops_staff_id = $res_staff['id'];
        $add_data = array('ops_staff_id'=>$ops_staff_id,'signin_hotel_id'=>$hotel_id,'signin_time'=>date('Y-m-d H:i:s'),
            'status'=>$status,'stock_check_num'=>$stock_check_num,'stock_check_hadnum'=>$stock_check_hadnum,'stock_check_status'=>$stock_check_status,'stock_check_success_status'=>$stock_check_success_status,
            'stock_check_error'=>$stock_check_error,'stock_check_errornum'=>$stock_check_errornum,'type'=>2);
        if(!empty($content)){
            $add_data['content'] = $content;
        }
        if(!empty($video_path)){
            $add_data['video_path'] = $video_path;
        }
        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $salerecord_id = $m_salerecord->add($add_data);
        if(!empty($check_list)){
            $m_stock_check_record = new \Common\Model\Crm\StockcheckRecordModel();
            foreach ($check_list as $k=>$v){
                $check_list[$k]['salerecord_id']=$salerecord_id;
            }
            $m_stock_check_record->addAll($check_list);
        }

        $add_remind = array(array('salerecord_id'=>$salerecord_id,'type'=>5,'remind_user_id'=>$ops_staff_id));
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
        $fields = 'record.goods_id,record.idcode,record.is_check,record.type,record.desc,goods.name as goods_name';
        $res_list = $m_stock_check_record->getCheckRecordList($fields,array('record.salerecord_id'=>$salerecord_id),'record.id desc');
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
        $this->to_back(array('idcodes'=>$idcodes,'other_idcodes'=>$other_idcodes));
    }

    public function detail(){
        $openid = $this->params['openid'];
        $salerecord_id = intval($this->params['salerecord_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $res_info = $m_salerecord->getInfo(array('id'=>$salerecord_id));

        $m_stock_check_record = new \Common\Model\Crm\StockcheckRecordModel();
        $fields = 'record.goods_id,record.idcode,record.is_check,record.type,goods.name as goods_name';
        $res_list = $m_stock_check_record->getCheckRecordList($fields,array('record.salerecord_id'=>$salerecord_id),'record.id desc');
        $idcodes = $other_idcodes = array();
        foreach ($res_list as $v){
            $checked = false;
            if($v['is_check']){
                $checked = true;
            }
            $v['checked'] = $checked;
            if($v['type']==1){
                $idcodes[]=$v;
            }else{
                $other_idcodes[]=$v;
            }
        }
        $m_salerecord_remind = new \Common\Model\Crm\SalerecordRemindModel();
        $fields = 'a.remind_user_id as staff_id,a.type,staff.job,sysuser.remark as staff_name,user.avatarUrl,user.nickName';
        $res_remind = $m_salerecord_remind->getList($fields,array('a.salerecord_id'=>$salerecord_id,'a.type'=>array('in','1,2')),'a.id desc');
        $all_remind_user = array();
        foreach ($res_remind as $v){
            $all_remind_user[$v['type']][]=$v;
        }
        $cc_users = $review_users = array();
        if(isset($all_remind_user[1])){
            $review_users = $all_remind_user[1];
        }
        if(isset($all_remind_user[2])){
            $cc_users = $all_remind_user[2];
        }
        $resp_data = array('hotel_id'=>$res_info['signin_hotel_id'],'content'=>$res_info['content'],'idcodes'=>$idcodes,
            'other_idcodes'=>$other_idcodes,'cc_users'=>$cc_users,'review_users'=>$review_users,'video_path'=>$res_info['video_path']);
        $this->to_back($resp_data);
    }

    public function edit(){
        $openid = $this->params['openid'];
        $salerecord_id = intval($this->params['salerecord_id']);
        $review_uid = intval($this->params['review_uid']);
        $cc_uids = $this->params['cc_uids'];
        $content = trim($this->params['content']);
        $type = intval($this->params['type']);//类型1保存2提交
        $is_check_error = intval($this->params['is_check_error']);//是否确认盘亏 1是 0否

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $res_info = $m_salerecord->getInfo(array('id'=>$salerecord_id,'ops_staff_id'=>$res_staff['id']));
        $res_data = array('status'=>1,'salerecord_id'=>$salerecord_id);
        if(!empty($res_info)){
            $updata = array('content'=>$content,'update_time'=>date('Y-m-d H:i:s'));
            $res_data['status'] = 2;
            if($type==2){
                $stock_check_errornum = $res_info['stock_check_errornum'];
                $stock_check_success_status = 0;
                $stock_check_status = 1;
                if($is_check_error==1){
                    $stock_check_status = 2;
                    if($stock_check_errornum==0){
                        $stock_check_success_status = 23;
                    }else{
                        $stock_check_success_status = 24;
                    }
                }else{
                    if($res_info['stock_check_num']==$res_info['stock_check_hadnum']){
                        $stock_check_status = 2;
                        if($stock_check_errornum==0){
                            $stock_check_success_status = 21;
                        }else{
                            $stock_check_success_status = 22;
                        }
                    }
                }
                $updata['stock_check_status'] = $stock_check_status;
                $updata['stock_check_success_status'] = $stock_check_success_status;
                $status = 2;
            }else{
                $status = 1;
            }
            $updata['status']=$status;
            $m_salerecord->updateData(array('id'=>$salerecord_id),$updata);

            $m_saleremind = new \Common\Model\Crm\SalerecordRemindModel();
            $m_saleremind->delData(array('salerecord_id'=>$salerecord_id,'type'=>array('in','1,2')));
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
            if(!empty($add_remind)){
                $m_saleremind->addAll($add_remind);
            }
        }
        $this->to_back($res_data);
    }

    public function statcheckdata(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $stat_date = $this->params['stat_date'];
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        if(empty($stat_date)){
            $stat_date = date('Y-m');
        }
        $m_hotel = new \Common\Model\HotelModel();
        $fields = 'count(hotel.id) as num';
        $hotel_where = array('hotel.state'=>1,'hotel.flag'=>0,'ext.is_salehotel'=>1,'ext.is_salehotel_stock'=>1);
        $hotel_where['hotel.id'] = array('not in',C('TEST_HOTEL'));
        if($area_id){
            $hotel_where['hotel.area_id'] = $area_id;
        }
        $res_hotel = $m_hotel->getHotelDataList($fields,$hotel_where,'hotel.id desc');
        $hotel_num = intval($res_hotel[0]['num']);
        $m_sale_record = new \Common\Model\Crm\SalerecordModel();
        $fields = 'COUNT(DISTINCT record.signin_hotel_id) as num';
        $check_where = $hotel_where;
        $check_where['record.type'] = 2;
        $check_where['record.stock_check_status'] = 2;
        $start_time = $stat_date.'-01 00:00:00';
        $end_time = $stat_date.'-31 23:59:59';
        $check_where['record.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $res_check_hotel = $m_sale_record->getStockCheckRecordList($fields,$check_where,'');
        $check_hotel_num = intval($res_check_hotel[0]['num']);
        $hotel_finish_percent = intval(($check_hotel_num/$hotel_num)*100).'%';

        $m_finance_goods = new \Common\Model\Finance\GoodsModel();
        $res_goods = $m_finance_goods->getDataList('id,name,brand_id',array(),'id desc');
        $goods_ids = $test_goods_ids = array();
        foreach ($res_goods as $v){
            if($v['brand_id']==11){
                $test_goods_ids[]=$v['id'];
            }else{
                $goods_ids[]=$v['id'];
            }
        }

        $redis = new \Common\Lib\SavorRedis();
        $redis->select(9);
        $goods_num = 0;
        if($area_id==0){
            $cache_key = C('FINANCE_GOODSSTOCK');
            $res_goods_stock = $redis->get($cache_key);
            $goods_stock = json_decode($res_goods_stock,true);
            foreach ($goods_stock as $v){
                if(in_array($v['id'],$test_goods_ids)){
                    continue;
                }
                $goods_num+=$v['stock_num'];
            }
        }else{
            $hcache_key = C('FINANCE_HOTELSTOCK');
            $res_hotel = $m_hotel->getHotelDataList('hotel.id',$hotel_where,'hotel.id desc');
            foreach ($res_hotel as $v){
                $hotel_key = $hcache_key.':'.$v['id'];
                $res_cache_stock = $redis->get($hotel_key);
                if(!empty($res_cache_stock)){
                    $hotel_stock = json_decode($res_cache_stock,true);
                    foreach ($hotel_stock['goods_list'] as $gv){
                        $goods_num+=$gv['stock_num'];
                    }
                }
            }
        }
        $fields = 'record.stock_check_num as num';
        $res_check_hotel = $m_sale_record->getStockCheckRecordList($fields,$check_where,'','','record.signin_hotel_id');
        $goods_check_num = 0;
        if(!empty($res_check_hotel)){
            foreach ($res_check_hotel as $chv){
                $goods_check_num+=$chv['num'];
            }
        }
        $goods_finish_percent = intval(($goods_check_num/$goods_num)*100).'%';

        $month_list = array(array('name'=>'本月','value'=>date('Y-m')));
        for($i=1;$i<12;$i++){
            $name = date('Y年m月',strtotime("last day of -$i month"));
            $value = date('Y-m',strtotime("last day of -$i month"));
            $month_list[]=array('name'=>$name,'value'=>$value);
        }

        $this->to_back(array('month_list'=>$month_list,'hotel_num'=>$hotel_num,'check_hotel_num'=>$check_hotel_num,'hotel_finish_percent'=>$hotel_finish_percent,
                'goods_num'=>$goods_num,'goods_check_num'=>$goods_check_num,'goods_finish_percent'=>$goods_finish_percent)
        );
    }

    public function hotelfilter(){
        $openid = $this->params['openid'];

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $hotel_role_type = $res_staff['hotel_role_type'];//酒楼角色类型1全国,2城市,3个人,4城市和个人,5全国财务,6城市财务
        $permission = json_decode($res_staff['permission'],true);

        $m_hotel = new \Common\Model\HotelModel();
        $hotel_where = array('hotel.state'=>1,'hotel.flag'=>0,'ext.is_salehotel'=>1,'ext.is_salehotel_stock'=>1);
        $test_hotels = C('TEST_HOTEL');
        $hotel_where['hotel.id'] = array('not in',$test_hotels);
        if(in_array($hotel_role_type,array(2,4,6))){
            $hotel_where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
        }elseif($hotel_role_type==3){
            $hotel_where['hotel.area_id'] = $res_staff['area_id'];
        }
        $fields = 'hotel.id as hotel_id,hotel.name as hotel_name';
        $hotel_list = $m_hotel->getHotelDataList($fields,$hotel_where,'hotel.id desc');

        $month_list = array(array('name'=>'本月','value'=>date('Y-m')));
        for($i=1;$i<12;$i++){
            $name = date('Y年m月',strtotime("last day of -$i month"));
            $value = date('Y-m',strtotime("last day of -$i month"));
            $month_list[]=array('name'=>$name,'value'=>$value);
        }
        $check_status = array(
            array('name'=>'全部','value'=>0),
            array('name'=>'盘点完成','value'=>2),
            array('name'=>'盘点完成(异常)','value'=>3),
            array('name'=>'盘点未完成','value'=>1),
        );
        $this->to_back(array('hotel_list'=>$hotel_list,'check_status'=>$check_status,'month_list'=>$month_list));
    }

    public function stathotellist(){
        $openid = $this->params['openid'];
        $stat_date = $this->params['stat_date'];
        $page = intval($this->params['page']);
        $pagesize = $this->params['pagesize'];
        $area_id = intval($this->params['area_id']);
        $hotel_id = intval($this->params['hotel_id']);
        $check_status = intval($this->params['check_status']);

        if(empty($pagesize)){
            $pagesize = 10;
        }
        $start = ($page-1)*$pagesize;
        $limit = "$start,$pagesize";
        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $hotel_role_type = $res_staff['hotel_role_type'];//酒楼角色类型1全国,2城市,3个人,4城市和个人,5全国财务,6城市财务
        $permission = json_decode($res_staff['permission'],true);

        $start_time = $stat_date.'-01 00:00:00';
        $end_time = $stat_date.'-31 23:59:59';
        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $id_where = array('record.type'=>2,'record.add_time'=>array(array('egt',$start_time),array('elt',$end_time)),'hotel.state'=>1,'hotel.flag'=>0,'ext.is_salehotel'=>1,'ext.is_salehotel_stock'=>1);
        $test_hotels = C('TEST_HOTEL');
        $id_where['hotel.id'] = array('not in',$test_hotels);
        if($area_id){
            $id_where['hotel.area_id'] = $area_id;
        }else{
            if(in_array($hotel_role_type,array(2,4,6))){
                $id_where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
            }elseif($hotel_role_type==3){
                $id_where['hotel.area_id'] = $res_staff['area_id'];
            }
        }

        $res_ids = $m_salerecord->getStockCheckRecordList('max(record.id) as id',$id_where,'','','record.signin_hotel_id');
        $datalist = array();
        if(!empty($res_ids)){
            $ids = array();
            foreach ($res_ids as $v){
                $ids[]=$v['id'];
            }
            $where = array('record.id'=>array('in',$ids));
            if($hotel_id){
                $where['record.signin_hotel_id'] = $hotel_id;
            }
            switch ($check_status){
                case 1:
                    $where['record.stock_check_status'] = 1;
                    break;
                case 2:
                    $where['record.stock_check_status'] = 2;
                    $where['record.stock_check_success_status'] = array('in','0,21');
                    break;
                case 3:
                    $where['record.stock_check_status'] = 2;
                    $where['record.stock_check_success_status'] = array('in','22,23,24');
                    break;
            }
            $rfields = 'hotel.id as hotel_id,hotel.name as hotel_name,record.id as salerecord_id,record.stock_check_num,record.stock_check_hadnum,record.stock_check_errornum as ying_num';
            $res_data = $m_salerecord->getStockCheckRecordList($rfields,$where,'record.id desc',$limit,'');
            foreach ($res_data as $v){
                $kui_num = $v['stock_check_num'] - $v['stock_check_hadnum'];
                $stock_check_percent='';
                if(!empty($v['stock_check_num']) && !empty($v['stock_check_hadnum'])){
                    $stock_check_percent = intval(($v['stock_check_hadnum']/$v['stock_check_num'])*100);
                    $stock_check_percent = $stock_check_percent.'%';
                }
                $v['kui_num'] = $kui_num;
                $v['stock_check_percent'] = $stock_check_percent;
                $datalist[]=$v;
            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function hotelchecklist(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $pagesize = $this->params['pagesize'];
        $hotel_id = intval($this->params['hotel_id']);
        if(empty($pagesize)){
            $pagesize = 20;
        }
        $start = ($page-1)*$pagesize;
        $limit = "$start,$pagesize";
        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $where = array('record.signin_hotel_id'=>$hotel_id,'record.type'=>2);

        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $fields = 'record.*,staff.id as staff_id,staff.job,sysuser.remark as staff_name,user.avatarUrl,user.nickName';
        $res_salerecord = $m_salerecord->getRecordList($fields,$where,'record.id desc',$limit,'');
        $datalist = array();
        if(!empty($res_salerecord)){
            $m_comment = new \Common\Model\Crm\CommentModel();
            $m_hotel = new \Common\Model\HotelModel();
            foreach ($res_salerecord as $v){
                $salerecord_id = $v['id'];
                $record_info = $v;
                $staff_id = $v['staff_id'];
                $staff_name = !empty($v['staff_name']) ? $v['staff_name'] :'小热点';
                $avatarUrl = $v['avatarUrl'];
                $job = $v['job'];
                $now = time();
                $diff_time = $now - strtotime($record_info['add_time']);
                if($diff_time<=86400){
                    $add_time = viewTimes(strtotime($record_info['add_time']));
                }else{
                    $add_time = date('m月d日 H:i',strtotime($record_info['add_time']));
                }
                $res_hotel = $m_hotel->getOneById('name',$record_info['signin_hotel_id']);
                $hotel_name = $res_hotel['name'];

                $comment_num = 0;
                $res_comment = $m_comment->getDataList('count(*) as num',array('salerecord_id'=>$salerecord_id,'status'=>1),'');
                if(!empty($res_comment[0]['num'])){
                    $comment_num = intval($res_comment[0]['num']);
                }
                if(!empty($record_info['content'])){
                    $record_info['content'] = text_substr($record_info['content'], 100,'...');
                }
                $stock_check_percent='';
                if(!empty($record_info['stock_check_num']) && !empty($record_info['stock_check_hadnum'])){
                    $stock_check_percent = intval(($record_info['stock_check_hadnum']/$record_info['stock_check_num'])*100);
                    $stock_check_percent = $stock_check_percent.'%';
                }

                $info = array('salerecord_id'=>$salerecord_id,'staff_id'=>$staff_id,'staff_name'=>$staff_name,'avatarUrl'=>$avatarUrl,'job'=>$job,
                    'add_time'=>$add_time,'content'=>$record_info['content'],'hotel_id'=>$record_info['signin_hotel_id'],
                    'hotel_name'=>$hotel_name,'comment_num'=>$comment_num,'stock_check_success_status'=>$record_info['stock_check_success_status'],
                    'stock_check_num'=>$record_info['stock_check_num'],'stock_check_hadnum'=>$record_info['stock_check_hadnum'],'stock_check_percent'=>$stock_check_percent,
                    'stock_check_status'=>$record_info['stock_check_status'],'stock_check_error'=>$record_info['stock_check_error'],
                );
                $datalist[]=$info;

            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }
}