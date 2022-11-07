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
        $m_staff = new \Common\Model\Integral\StaffModel();
        $fileds = 'a.id as staff_id,a.level,a.openid,user.nickName';
        $where = array('merchant.hotel_id'=>$hotel_id,'merchant.status'=>1,'a.status'=>1);
        $res_staffs = $m_staff->getMerchantStaff($fileds,$where);
        $staff_list = array(array('openid'=>'','nickName'=>'全部销售经理','staff_id'=>0,'level'=>0,'is_check'=>0));
        if(!empty($res_staffs)){
            foreach ($res_staffs as $v){
                $is_check = 0;
                if(!empty($sell_openid) && $sell_openid==$v['openid']){
                    $is_check = 1;
                }
                $v['is_check'] = $is_check;
                $staff_list[] = $v;
            }
        }
        $data = array('datalist'=>$staff_list);
        $this->to_back($data);
    }


}