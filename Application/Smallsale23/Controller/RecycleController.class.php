<?php
namespace Smallsale23\Controller;
use \Common\Controller\CommonController as CommonController;

class RecycleController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getReasons':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'goods_id'=>1001,'num'=>1002);
                break;
            case 'applyOpenReward':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'batch_no'=>1001,'imgs'=>1001);
                break;
            case 'noOpenReward':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'batch_no'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getReasons(){
        $openid = $this->params['openid'];
        $goods_id = intval($this->params['goods_id']);
        $num = intval($this->params['num']);

        $m_goodsconfig = new \Common\Model\Finance\GoodsConfigModel();
        $where = array('goods_id'=>$goods_id,'status'=>1,'type'=>21);
        $field = 'id,name,is_required';
        $res_config = $m_goodsconfig->getDataList($field,$where,'id asc');
        $data = array();
        if(!empty($res_config) && $num){
            foreach ($res_config as $k=>$v){
                $res_config[$k]['img_url']='';
            }
            for($i=0;$i<$num;$i++){
                $data[]=$res_config;
            }
        }
        $this->to_back(array('datalist'=>$data));
    }

    public function applyOpenReward(){
        $openid = $this->params['openid'];
        $imgs = $this->params['imgs'];
        $batch_no = $this->params['batch_no'];
        $json_str = stripslashes(html_entity_decode($imgs));
        $all_img = json_decode($json_str,true);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields, $where);
        if (empty($res_staff)) {
            $this->to_back(93001);
        }
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $fields = 'a.id,a.goods_id,a.wo_reason_type,a.unit_id,a.op_openid,sale.hotel_id,sale.area_id';
        $where = array('a.op_openid'=>$openid,'a.type'=>7,'a.batch_no'=>$batch_no,'a.wo_status'=>2,'a.recycle_status'=>1);
        $res_records = $m_stock_record->alias('a')
            ->field($fields)
            ->join('savor_finance_sale sale on a.id=sale.stock_record_id','left')
            ->where($where)
            ->order('a.id asc')
            ->select();
        $m_userintegral = new \Common\Model\Smallapp\UserIntegralrecordModel();
        foreach ($res_records as $k=>$v){
            if(!empty($all_img[$k])){
                $recycle_img_arr = array();
                foreach ($all_img[$k] as $aiv){
                    if(!empty($aiv['img_url'])){
                        $recycle_img_arr[]=$aiv['img_url'];
                    }
                }
                if(!empty($recycle_img_arr)){
                    $recycle_img = join(',',$recycle_img_arr);
                    $recycle_status = 5;
                }else{
                    $recycle_img = '';
                    $recycle_status = 3;
                }
            }else{
                $recycle_img = '';
                $recycle_status = 3;
            }
            $updata = array('recycle_img'=>$recycle_img,'recycle_status'=>$recycle_status,
                'recycle_time'=>date('Y-m-d H:i:s'));
            $m_stock_record->updateData(array('id'=>$v['id']),$updata);
            if($recycle_status==5){
                $m_userintegral->finishRecycle($v);
            }
        }
        $this->to_back(array());
    }

    public function noOpenReward(){
        $openid = $this->params['openid'];
        $batch_no = $this->params['batch_no'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields, $where);
        if (empty($res_staff)) {
            $this->to_back(93001);
        }
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $where = array('op_openid'=>$openid,'type'=>7,'batch_no'=>$batch_no,'wo_status'=>2,'recycle_status'=>1);
        $updata = array('recycle_status'=>3,'recycle_time'=>date('Y-m-d H:i:s'));
        $m_stock_record->updateData($where,$updata);
        $this->to_back(array());
    }


}