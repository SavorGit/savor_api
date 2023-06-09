<?php
namespace Common\Model\Crm;
use Common\Model\BaseModel;

class SalerecordModel extends BaseModel{
	protected $tableName='crm_salerecord';

    public function getRecordList($fields,$where,$orderby,$limit='',$group=''){
        $data = $this->alias('record')
            ->field($fields)
            ->join('savor_ops_staff staff on record.ops_staff_id=staff.id','left')
            ->join('savor_smallapp_user user on staff.openid=user.openid','left')
            ->join('savor_sysuser sysuser on staff.sysuser_id=sysuser.id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }

    public function getStockCheckRecordList($fields,$where,$orderby,$limit='',$group=''){
        $data = $this->alias('record')
            ->field($fields)
            ->join('savor_hotel hotel on hotel.id=record.signin_hotel_id','left')
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->join('savor_area_info area on area.id=hotel.area_id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }

    public function getRecordData($fields,$where,$orderby,$limit='',$group=''){
        $data = $this->alias('record')
            ->field($fields)
            ->join('savor_hotel hotel on hotel.id=record.signin_hotel_id','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->group($group)
            ->select();
        return $data;
    }

    public function getSignProcess($hotel_id,$salerecord_id=0){
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getOneById('*',$hotel_id);
        $sign_progress = array();
        if($res_hotel['htype']==20 || $salerecord_id){
            $confsign_progress = C('SIGN_PROCESS');
            $m_salerecord = new \Common\Model\Crm\SalerecordModel();
            if($salerecord_id){
                $where = array('id'=>$salerecord_id);
            }else{
                $where = array('signin_hotel_id'=>$hotel_id,'type'=>1,'sign_progress_id'=>array('gt',0));
            }
            $res_salereocrd = $m_salerecord->getALLDataList('id,sign_progress_id,sign_progress',$where,'id desc','0,1','');
            $sign_progress_id = 0;
            if(!empty($res_salereocrd)){
                $sign_progress_id = $res_salereocrd[0]['sign_progress_id'];
            }
            $m_hotelcontract = new \Common\Model\Finance\ContractHotelModel();
            foreach ($confsign_progress as $v){
                $is_check = 0;
                if($sign_progress_id>=$v['id']){
                    $is_check = 1;
                }
                if($v['id']==2){
                    $v['user'] = array('contractor'=>$res_hotel['contractor'],'mobile'=>$res_hotel['mobile'],'job'=>$res_hotel['job'],'gender'=>$res_hotel['gender']);
                }
                if($v['id']==8){
                    $tips = '';
                    $res_contract = $m_hotelcontract->getContractData('a.id',array('a.hotel_id'=>$hotel_id,'contract.type'=>20),'a.id desc');
                    if(empty($res_contract)){
                        $tips = '代销合同暂未上传，请联系财务。';
                    }
                    $v['tips'] = $tips;
                }
                $v['is_check'] = $is_check;
                $sign_progress[]=$v;
            }
        }
        return $sign_progress;
    }
}