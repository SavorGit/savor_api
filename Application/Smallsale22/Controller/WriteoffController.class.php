<?php
namespace Smallsale22\Controller;
use \Common\Controller\CommonController as CommonController;

class WriteoffController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'filter':
                $this->params = array('openid'=>1001);
                $this->is_verify = 1;
                break;
            case 'statdata':
                $this->params = array('openid'=>1001,'sdate'=>1002,'edate'=>1002,'wo_status'=>1002,'recycle_status'=>1002);
                $this->is_verify = 1;
            case 'datalist':
                $this->params = array('openid'=>1001,'page'=>1001,'sdate'=>1002,'edate'=>1002,
                    'recycle_sdate'=>1002,'recycle_edate'=>1002,'wo_status'=>1002,'recycle_status'=>1002);
                $this->is_verify = 1;
                break;
                
        }
        parent::_init_();
    }

    public function filter(){
        $openid = $this->params['openid'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id,a.level';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }

        $all_stock_status = C('STOCK_AUDIT_STATUS');
        $stock_status = array(array('name'=>'售酒核销状态','status'=>0));
        foreach ($all_stock_status as $k=>$v){
            if($k!=4){
                $stock_status[]=array('name'=>$v,'status'=>$k);
            }
        }
        $all_stock_recycle_status = C('STOCK_RECYCLE_STATUS');
        $recycle_status = array(array('name'=>'开瓶奖励状态','recycle_status'=>0));
        foreach ($all_stock_recycle_status as $k=>$v){
            $recycle_status[]=array('name'=>$v,'recycle_status'=>$k);
        }

        $start_date = date('Y-m-d',strtotime('-7 day'));
        $end_date = date('Y-m-d');
        $date_range = array($start_date,$end_date);
        $now_month_date = array(date('Y-m-01'),date('Y-m-t'));
        $res_data = array('date_range'=>$date_range,'now_month_date'=>$now_month_date,'stock_status'=>$stock_status,'recycle_status'=>$recycle_status);
        $this->to_back($res_data);
    }

    public function statdata(){
        $openid = $this->params['openid'];
        $sdate = $this->params['sdate'];
        $edate = $this->params['edate'];
        $wo_status = intval($this->params['wo_status']);
        $recycle_status = intval($this->params['recycle_status']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id,a.level';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $salewhere = array('a.hotel_id'=>$res_staff[0]['hotel_id'],'record.wo_reason_type'=>1,'record.wo_status'=>array('in','1,2,4'));
        if($res_staff[0]['level']>1){
            $salewhere['a.sale_openid'] = $openid;
        }
        if(empty($sdate) || empty($edate)){
            $sdate = date('Y-m-01');
            $edate = date('Y-m-t');
        }
        $data_goods_ids = C('DATA_GOODS_IDS');
        $salewhere['a.goods_id'] = array('not in',$data_goods_ids);
        $salewhere['a.add_time'] = array(array('egt',"$sdate 00:00:00"),array('elt',"$edate 23:59:59"));
        $m_sale = new \Common\Model\Finance\SaleModel();
        $fields = 'sum(a.num) as num,record.wo_status';
        $res_salerecord = $m_sale->getSaleStockRecordList($fields,$salewhere,'record.wo_status','','');
        $sale_num = $approval_num = 0;
        foreach ($res_salerecord as $v){
            $now_num = intval($v['num']);
            if($v['wo_status']==2){
                $approval_num=$now_num;
            }
            $sale_num+=$now_num;
        }
        $salewhere['record.recycle_status'] = 2;
        $fields = 'sum(a.num) as num';
        $res_salerecord = $m_sale->getSaleStockRecordList($fields,$salewhere,'','','');
        $recycle_num = intval($res_salerecord[0]['num']);
        $m_userintegral = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $iwhere = array('openid'=>$openid,'hotel_id'=>$res_staff[0]['hotel_id'],'type'=>array('in','17,25'),'status'=>1);
        $res_uirecord = $m_userintegral->getALLDataList('sum(integral) as all_integral,type',$iwhere,'id desc','','type');
        $sale_integral = $recycle_integral = 0;
        foreach ($res_uirecord as $v){
            if($v['type']==17){
                $sale_integral = $v['all_integral'];
            }elseif($v['type']==25){
                $recycle_integral = $v['all_integral'];
            }
        }
        $is_stat = 1;
        if($wo_status || $recycle_status){
            $is_stat = 0;
        }
        $tips = '';
        $no_recycle_num = $sale_num-$recycle_num;
        if($no_recycle_num>0){
            $tips = "本月有{$no_recycle_num}瓶酒没有收回瓶盖，回收后才能获得开瓶奖励哦～";
        }
        $this->to_back(array('is_stat'=>$is_stat,'sale_num'=>$sale_num,'approval_num'=>$approval_num,'recycle_num'=>$recycle_num,
            'sale_integral'=>$sale_integral,'recycle_integral'=>$recycle_integral,'tips'=>$tips));
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $sdate = $this->params['sdate'];
        $edate = $this->params['edate'];
        $recycle_sdate = $this->params['recycle_sdate'];
        $recycle_edate = $this->params['recycle_edate'];
        $wo_status = intval($this->params['wo_status']);
        $recycle_status = intval($this->params['recycle_status']);
        $page = intval($this->params['page']);
        $pagesize = 10;

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id,a.level';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $offset = ($page-1)*$pagesize;
        $limit = "$offset,$pagesize";
        $salewhere = array('a.hotel_id'=>$res_staff[0]['hotel_id']);
        if($res_staff[0]['level']>1){
            $salewhere['a.sale_openid'] = $openid;
        }
        if($wo_status){
            $salewhere['record.wo_status'] = $wo_status;
        }
        if($recycle_status){
            $salewhere['record.recycle_status'] = $recycle_status;
        }
        $data_goods_ids = C('DATA_GOODS_IDS');
        $where['a.goods_id'] = array('not in',$data_goods_ids);
        if(!empty($sdate) && !empty($edate)){
            $salewhere['a.add_time'] = array(array('egt',"$sdate 00:00:00"),array('elt',"$edate 23:59:59"));
        }elseif(!empty($recycle_sdate) && !empty($recycle_edate)){
            $salewhere['record.recycle_audit_time'] = array(array('egt',"$recycle_sdate 00:00:00"),array('elt',"$recycle_edate 23:59:59"));
        }elseif(empty($sdate) || empty($edate)){
            $sdate = date('Y-m-d',strtotime('-7 day'));
            $edate = date('Y-m-d');
            $salewhere['a.add_time'] = array(array('egt',"$sdate 00:00:00"),array('elt',"$edate 23:59:59"));
        }

        $m_sale = new \Common\Model\Finance\SaleModel();
        $fields = 'a.idcode,a.add_time,a.hotel_id,a.ptype,a.type,a.settlement_price,a.residenter_id,a.sale_openid,a.num,a.stock_record_id,
        record.wo_data_imgs,record.reason,goods.id as goods_id,goods.name as goods_name,cate.name as cate_name,spec.name as spec_name,unit.name as unit_name,
        record.wo_status,record.recycle_status,record.recycle_time,record.wo_reason_type,record.wo_time,user.nickName,user.avatarUrl,hotel.name as hotel_name,ext.residenter_id as now_residenter_id';
        $res_salerecord = $m_sale->getSaleStockRecordList($fields,$salewhere,'',$limit,'a.id desc');
        $data_list = array();
        if(!empty($res_salerecord)){
            $m_userintegral = new \Common\Model\Smallapp\UserIntegralrecordModel();
            $m_goodsconfig = new \Common\Model\Finance\GoodsConfigModel();
            $m_media = new \Common\Model\MediaModel();
            $all_reasons = C('STOCK_REASON');
            $all_status = C('STOCK_AUDIT_STATUS');
            $all_recycle_status = C('STOCK_RECYCLE_ALL_STATUS');
            foreach ($res_salerecord as $v){
                $stock_record_id = $v['stock_record_id'];

                $res_goods = array(
                    array('id'=>$v['stock_record_id'],'idcode'=>$v['idcode'],'goods_id'=>$v['goods_id'],'goods_name'=>$v['goods_name'],
                        'cate_name'=>$v['cate_name'],'spec_name'=>$v['spec_name'],'unit_name'=>$v['unit_name'],'status'=>$v['wo_status'],
                        'recycle_status'=>$v['recycle_status'],'recycle_time'=>$v['recycle_time'],'reason'=>$v['reason'],'add_time'=>$v['add_time']
                    )
                );
                $cwhere = array('goods_id'=>$res_goods[0]['goods_id'],'status'=>1,'type'=>array('in','10,20'));
                $res_config = $m_goodsconfig->getDataList('id,name,media_id,open_integral,type',$cwhere,'id asc');
                $demo_img = '';
                $entity = array();
                if(!empty($res_config)){
                    foreach ($res_config as $cv){
                        $img_url = '';
                        if($cv['media_id']>0){
                            $res_media = $m_media->getMediaInfoById($cv['media_id']);
                            $img_url = $res_media['oss_addr'];
                        }
                        if($cv['type']==10){
                            $demo_img = $img_url;
                        }elseif($cv['type']==20){
                            $entity[]=array('name'=>$cv['name'],'img_url'=>$img_url);
                        }
                    }
                }
                $status_str = '售卖奖励'.$all_status[$v['wo_status']];
                $recycle_status_str = '开瓶奖励'.$all_recycle_status[$v['recycle_status']];
                $reason = isset($all_reasons[$v['wo_reason_type']])?$all_reasons[$v['wo_reason_type']]['name']:'';
                $iwhere = array('openid'=>$openid,'jdorder_id'=>$stock_record_id,'type'=>array('in','17,25'));
                $res_uirecord = $m_userintegral->getALLDataList('sum(integral) as all_integral,status,type',$iwhere,'id asc','','type');
                $integreal_list = array();
                if($v['wo_status']==3) {
                    $is_wobutton = 0;
                    if($v['sale_openid']==$openid){
                        $is_wobutton = 1;
                    }
                    $integreal_list[]=array('status_str'=>$status_str,'income_str'=>'','integreal'=>'',
                        'reason'=>$v['reason'],'is_wobutton'=>$is_wobutton);
                }else{
                    foreach ($res_uirecord as $uirv){
                        $wo_reason_str = '';
                        $is_wobutton = 0;
                        switch ($uirv['type']){
                            case 17:
                                $i_status_str = $status_str;
                                break;
                            case 25:
                                $i_status_str = $recycle_status_str;
                                if($v['recycle_status']==6){
                                    $wo_reason_str = $v['reason'];
                                }
                                break;
                            default:
                                $i_status_str = '';
                        }
                        if($uirv['status']==1){
                            $income_str = '收益';
                        }else{
                            $income_str = '预估收益';
                        }
                        $integreal_list[]=array('status_str'=>$i_status_str,'income_str'=>$income_str,'integreal'=>$uirv['all_integral'],
                            'reason'=>$wo_reason_str,'is_wobutton'=>$is_wobutton);
                    }
                }

                $data_list[]=array('nickName'=>$v['nickName'],'avatarUrl'=>$v['avatarUrl'],'reason'=>$reason,'status'=>$v['wo_status'],'status_str'=>$status_str,
                    'recycle_status'=>$v['recycle_status'],'recycle_status_str'=>$recycle_status_str,'num'=>$v['num'],'add_time'=>$v['add_time'],'wo_reason_type'=>$v['wo_reason_type'],
                    'goods'=>$res_goods,'entity'=>$entity,'demo_img'=>$demo_img,'stock_record_id'=>$stock_record_id,'integreal_list'=>$integreal_list);
            }
        }

        $this->to_back(array('datalist'=>$data_list));
    }

}
