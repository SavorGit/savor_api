<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class CrmdataController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'filter':
                $this->valid_fields = array('openid'=>1001);
                $this->is_verify = 1;
                break;
            case 'statsigndata':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'area_id'=>1002,'staff_id'=>1002,'start_date'=>1001,'end_date'=>1001);
                break;
            case 'signprocess':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'area_id'=>1002,'staff_id'=>1002);
                break;
            case 'signhotels':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'source'=>1001,'area_id'=>1002,'staff_id'=>1002,
                    'sign_progress_id'=>1002,'start_date'=>1002,'end_date'=>1002,'page'=>1001);
                break;

        }
        parent::_init_();
    }

    public function filter(){
        $openid = $this->params['openid'];

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $month_list = array(
            array('name'=>'本月','start_time'=>date('Y-m-01'),'end_time'=>date('Y-m-d')),
            array('name'=>'上月','start_time'=>date('Y-m-01',strtotime("last day of -1 month")),'end_time'=>date('Y-m-31',strtotime("last day of -1 month"))),
        );
        $calender = array('start_time'=>'2022-12-15','end_time'=>date('Y-m-d'));
        $this->to_back(array('month_list'=>$month_list,'calender'=>$calender));
    }

    public function statsigndata(){
        $openid   = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $start_date = $this->params['start_date'];
        $end_date   = $this->params['end_date'];

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $start_time = date('Y-m-d 00:00:00',strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59',strtotime($end_date));
        $hotel_role_type = $res_staff['hotel_role_type'];//酒楼角色类型1全国,2城市,3个人,4城市和个人,5全国财务,6城市财务
        $permission = json_decode($res_staff['permission'],true);

        $where = array('record.type'=>1,'record.status'=>2,'record.visit_type'=>array('in','184,171'),'record.signin_hotel_id'=>array('gt',0));
        $test_hotels = C('TEST_HOTEL');
        $where['hotel.id'] = array('not in',$test_hotels);
        $where['hotel.htype'] = 20;
        if($area_id){
            $where['hotel.area_id'] = $area_id;
        }else{
            if(in_array($hotel_role_type,array(2,4,6))){
                $where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
            }elseif($hotel_role_type==3){
                $where['hotel.area_id'] = $res_staff['area_id'];
            }
        }
        if($staff_id>0){
            $where['record.ops_staff_id'] = $staff_id;
        }
        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $fields = 'min(record.id) as id';
        $res_data = $m_salerecord->getRecordData($fields,$where,'','','record.signin_hotel_id');
        $new_hotel_num = 0;
        if(!empty($res_data)){
            $all_ids = array();
            foreach ($res_data as $v){
                $all_ids[]=$v['id'];
            }
            $new_where = $where;
            $new_where['record.id'] = array('in',$all_ids);
            $new_where['record.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
            $fields = 'count(DISTINCT record.signin_hotel_id) as new_hotel_num';
            $res_data = $m_salerecord->getRecordData($fields,$new_where,'');
            $new_hotel_num = intval($res_data[0]['new_hotel_num']);
        }

        $where['record.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $fields = 'count(DISTINCT record.signin_hotel_id) as hotel_num,count(record.id) as visit_num,
        avg((UNIX_TIMESTAMP(record.signout_time)-UNIX_TIMESTAMP(record.signin_time))/60) as visit_time,count(DISTINCT record.ops_staff_id) as user_num,count(DISTINCT(DATE(record.add_time))) as day_num';
        $res_data = $m_salerecord->getRecordData($fields,$where,'');
        $hotel_num = intval($res_data[0]['hotel_num']);
        $visit_num = intval($res_data[0]['visit_num']);
        $visit_time = intval($res_data[0]['visit_time']);
        $user_num = intval($res_data[0]['user_num']);
        $day_num = intval($res_data[0]['day_num']);
        $visit_frequency = round($visit_num/$user_num/$day_num,1);
        $visit_data = array(
            array('name'=>'拜访餐厅数','value'=>$hotel_num,'unit'=>'家','source'=>1),
            array('name'=>'拜访新餐厅数','value'=>$new_hotel_num,'unit'=>'家','source'=>2),
            array('name'=>'拜访总次数','value'=>$visit_num,'unit'=>'次'),
            array('name'=>'单次拜访时长','value'=>$visit_time,'unit'=>'分钟'),
            array('name'=>'拜访频次','value'=>$visit_frequency,'unit'=>'家/天/人'),
        );

        $sign_where = array('a.sign_progress_id'=>array('in','7,8'),'hotel.id'=>array('not in',$test_hotels),'hotel.htype'=>20);
        if($area_id){
            $sign_where['hotel.area_id'] = $area_id;
        }else{
            if(in_array($hotel_role_type,array(2,4,6))){
                $sign_where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
            }elseif($hotel_role_type==3){
                $sign_where['hotel.area_id'] = $res_staff['area_id'];
            }
        }
        if($staff_id>0){
            $sign_where['a.ops_staff_id'] = $staff_id;
        }
        $sign_where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $m_signhotel = new \Common\Model\Crm\SignhotelModel();
        $fields = 'count(a.id) as hotel_num,sum(a.visit_num) as visit_num,
        sum((UNIX_TIMESTAMP(a.end_time)-UNIX_TIMESTAMP(a.start_time))/86400) as sign_day';
        $res_data = $m_signhotel->getSignData($fields,$sign_where,'');
        $hotel_num=$visit_frequency=$sign_day=0;
        if(!empty($res_data)){
            $hotel_num = $res_data[0]['hotel_num'];
            $sign_day = round($res_data[0]['sign_day']/$res_data[0]['hotel_num'],1);
            $visit_frequency = round($res_data[0]['sign_day']/$res_data[0]['visit_num'],1);
        }
        $sign_data = array(
            array('name'=>'成功签约数','value'=>$hotel_num,'unit'=>'家','source'=>3),
            array('name'=>'单店拜访频次','value'=>$visit_frequency,'unit'=>'天/次'),
            array('name'=>'签约成功周期','value'=>$sign_day,'unit'=>'天'),
        );
        $this->to_back(array('visit_data'=>$visit_data,'sign_data'=>$sign_data,'desc'=>C('STATSIGNDATA_DESC')));
    }

    public function signprocess(){
        $openid   = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $hotel_role_type = $res_staff['hotel_role_type'];//酒楼角色类型1全国,2城市,3个人,4城市和个人,5全国财务,6城市财务
        $permission = json_decode($res_staff['permission'],true);

        $test_hotels = C('TEST_HOTEL');
        $sign_where = array('hotel.id'=>array('not in',$test_hotels),'hotel.htype'=>20);
        if($area_id){
            $sign_where['hotel.area_id'] = $area_id;
        }else{
            if(in_array($hotel_role_type,array(2,4,6))){
                $sign_where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
            }elseif($hotel_role_type==3){
                $sign_where['hotel.area_id'] = $res_staff['area_id'];
            }
        }
        if($staff_id>0){
            $sign_where['a.ops_staff_id'] = $staff_id;
        }
        $m_signhotel = new \Common\Model\Crm\SignhotelModel();
        $fields = 'a.sign_progress_id,count(a.id) as hotel_num';
        $res_data = $m_signhotel->getSignData($fields,$sign_where,'','','a.sign_progress_id');
        $sign_hotel = array();
        foreach ($res_data as $v){
            $sign_hotel[$v['sign_progress_id']]=$v['hotel_num'];
        }
        $sign_process = C('SIGN_PROCESS');
        $datalist = array();
        $all_hotel_num = 0;
        $sign_hotel_num = 0;
        foreach ($sign_process as $v){
            if($v['id']>=8){
                continue;
            }
            $percent = $v['percent'];
            $hotel_num = 0;
            if(isset($sign_hotel[$v['id']])){
                $hotel_num = $sign_hotel[$v['id']];
                $tmp_sign_num = ($percent/100)*$hotel_num;
                $sign_hotel_num+=$tmp_sign_num;
            }
            $all_hotel_num+=$hotel_num;
            $v['source'] = 4;
            $v['percent'] = $percent.'%';
            $v['unit'] = '家';
            $v['hotel_num'] = $hotel_num;
            $datalist[]=$v;
        }
        $this->to_back(array('all_hotel_num'=>$all_hotel_num,'datalist'=>$datalist,'sign_hotel_num'=>$sign_hotel_num));
    }

    public function signhotels(){
        $openid   = $this->params['openid'];
        $source = intval($this->params['source']);//1拜访餐厅,2拜访新餐厅,3签约餐厅,4签约进度餐厅
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $page = intval($this->params['page']);
        $sign_progress_id = intval($this->params['sign_progress_id']);
        $start_date = $this->params['start_date'];
        $end_date   = $this->params['end_date'];
        $pagesize = 20;

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $start_time = date('Y-m-d 00:00:00',strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59',strtotime($end_date));
        $hotel_role_type = $res_staff['hotel_role_type'];//酒楼角色类型1全国,2城市,3个人,4城市和个人,5全国财务,6城市财务
        $permission = json_decode($res_staff['permission'],true);

        $start = ($page-1)*$pagesize;
        $limit = $start.','.$pagesize;
        $test_hotels = C('TEST_HOTEL');
        $res_hotel_data = array();
        if($source==1){
            $where = array('record.type'=>1,'record.status'=>2,'record.visit_type'=>array('in','184,171'),'record.signin_hotel_id'=>array('gt',0));
            $where['hotel.id'] = array('not in',$test_hotels);
            $where['hotel.htype'] = 20;
            if($area_id){
                $where['hotel.area_id'] = $area_id;
            }else{
                if(in_array($hotel_role_type,array(2,4,6))){
                    $where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
                }elseif($hotel_role_type==3){
                    $where['hotel.area_id'] = $res_staff['area_id'];
                }
            }
            if($staff_id>0){
                $where['record.ops_staff_id'] = $staff_id;
            }
            $where['record.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
            $m_salerecord = new \Common\Model\Crm\SalerecordModel();
            $fields = 'hotel.id as hotel_id,hotel.name as hotel_name,hotel.addr';
            $res_hotel_data = $m_salerecord->getRecordData($fields,$where,'',$limit,'record.signin_hotel_id');
        }elseif($source==2){
            $where = array('record.type'=>1,'record.status'=>2,'record.visit_type'=>array('in','184,171'),'record.signin_hotel_id'=>array('gt',0));
            $where['hotel.id'] = array('not in',$test_hotels);
            $where['hotel.htype'] = 20;
            if($area_id){
                $where['hotel.area_id'] = $area_id;
            }else{
                if(in_array($hotel_role_type,array(2,4,6))){
                    $where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
                }elseif($hotel_role_type==3){
                    $where['hotel.area_id'] = $res_staff['area_id'];
                }
            }
            if($staff_id>0){
                $where['record.ops_staff_id'] = $staff_id;
            }
            $m_salerecord = new \Common\Model\Crm\SalerecordModel();
            $fields = 'min(record.id) as id';
            $res_data = $m_salerecord->getRecordData($fields,$where,'','','record.signin_hotel_id');
            if(!empty($res_data)){
                $all_ids = array();
                foreach ($res_data as $v){
                    $all_ids[]=$v['id'];
                }
                $where['record.id'] = array('in',$all_ids);
                $where['record.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
                $fields = 'hotel.id as hotel_id,hotel.name as hotel_name,hotel.addr';
                $res_hotel_data = $m_salerecord->getRecordData($fields,$where,'',$limit,'record.signin_hotel_id');
            }
        }elseif($source==3){
            $sign_where = array('a.sign_progress_id'=>array('in','7,8'),'hotel.id'=>array('not in',$test_hotels),'hotel.htype'=>20);
            if($area_id){
                $sign_where['hotel.area_id'] = $area_id;
            }else{
                if(in_array($hotel_role_type,array(2,4,6))){
                    $sign_where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
                }elseif($hotel_role_type==3){
                    $sign_where['hotel.area_id'] = $res_staff['area_id'];
                }
            }
            if($staff_id>0){
                $sign_where['a.ops_staff_id'] = $staff_id;
            }
            $sign_where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
            $m_signhotel = new \Common\Model\Crm\SignhotelModel();
            $fields = 'hotel.id as hotel_id,hotel.name as hotel_name,hotel.addr';
            $res_hotel_data = $m_signhotel->getSignData($fields,$sign_where,'',$limit);
        }elseif($source==4){
            $sign_where = array('a.sign_progress_id'=>$sign_progress_id,'hotel.id'=>array('not in',$test_hotels),'hotel.htype'=>20);
            if($area_id){
                $sign_where['hotel.area_id'] = $area_id;
            }else{
                if(in_array($hotel_role_type,array(2,4,6))){
                    $sign_where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
                }elseif($hotel_role_type==3){
                    $sign_where['hotel.area_id'] = $res_staff['area_id'];
                }
            }
            if($staff_id>0){
                $sign_where['a.ops_staff_id'] = $staff_id;
            }
            $m_signhotel = new \Common\Model\Crm\SignhotelModel();
            $fields = 'hotel.id as hotel_id,hotel.name as hotel_name,hotel.addr';
            $res_hotel_data = $m_signhotel->getSignData($fields,$sign_where,'',$limit);
        }
        $this->to_back(array('datalist'=>$res_hotel_data));
    }
}
