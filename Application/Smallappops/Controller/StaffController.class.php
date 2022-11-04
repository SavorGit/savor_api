<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class StaffController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'hotelstafflist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'sell_openid'=>1002);
                break;
        }
        parent::_init_();
    }

    public function hotelstafflist(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $sell_openid = $this->params['sell_openid'];

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('hotel_id'=>$hotel_id,'status'=>1));
        $merchant_id = intval($res_merchant['id']);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staffs = $m_staff->getDataList('id,openid,level',array('merchant_id'=>$merchant_id,'status'=>1),'level asc');
        $datalist = array(array('openid'=>'','nickName'=>'全部销售经理','staff_id'=>0,'level'=>0,'is_check'=>0));
        if(!empty($res_staffs)){
            $m_user = new \Common\Model\Smallapp\UserModel();
            foreach ($res_staffs as $v){
                $where = array('openid'=>$v['openid']);
                $fields = 'openid,nickName';
                $res_user = $m_user->getOne($fields,$where);
                $res_user['staff_id'] = $v['id'];
                $res_user['level'] = intval($v['level']);
                $is_check = 0;
                if(!empty($sell_openid) && $sell_openid==$v['openid']){
                    $is_check = 1;
                }
                $res_user['is_check'] = $is_check;
                $datalist[] = $res_user;
            }
        }
        $data = array('datalist'=>$datalist);
        $this->to_back($data);
    }


}