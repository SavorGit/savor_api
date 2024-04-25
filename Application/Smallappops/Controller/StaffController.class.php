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
            case 'opsstafflist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'type'=>1001);
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
        $fileds = 'a.id as staff_id,a.level,a.openid,user.nickName,user.mobile';
        $where = array('merchant.hotel_id'=>$hotel_id,'merchant.status'=>1,'a.status'=>1);
        $res_staffs = $m_staff->getMerchantStaff($fileds,$where,'a.level asc');
        $staff_list = array();
        if(!empty($res_staffs)){
            $staff_list[]=array('openid'=>'','nickName'=>'全部销售经理','staff_id'=>0,'level'=>0,'is_check'=>0);
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

    public function opsstafflist(){
        $openid = $this->params['openid'];
        $type = intval($this->params['type']);//9

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $area_id = $res_staff['area_id'];
        $fields = 'a.id as staff_id,su.remark as staff_name';
        $staff_list = $m_opsstaff->getStaffinfo($fields,array('a.area_id'=>$area_id,'a.status'=>1,'a.hotel_role_type'=>$type));
        if(!empty($staff_list)){
            $stmp = array('staff_id'=>0,'staff_name'=>'全部');
            array_unshift($staff_list, $stmp);
        }
        $data = array('datalist'=>$staff_list);
        $this->to_back($data);
    }


}