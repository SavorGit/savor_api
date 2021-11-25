<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class BasicdataController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'hotel':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'staff_id'=>1001);
                break;

        }
        parent::_init_();
    }
    public function hotel(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $this->check_permission($res_staff,$area_id,$staff_id);
        if($type==0){
            $this->to_back(1001);
        }
        $m_box = new \Common\Model\BoxModel();
        $fileds = 'a.mac,ext.hotel_id,ext.maintainer_id,ext.mac_addr';
        $hotel_box_types = array_keys(C('HEART_HOTEL_BOX_TYPE'));
        $where = array('a.state'=>1,'a.flag'=>0,'d.state'=>1,'d.flag'=>0);
        $where['d.hotel_box_type'] = array('in',$hotel_box_types);
        if($area_id){
            $where['d.area_id'] = $area_id;
        }
        if($staff_id){
            $where['ext.maintainer_id'] = $staff_id;
        }
        $res_box = $m_box->getBoxInfo($fileds,$where);


    }

    private function check_permission($staff_info,$area_id,$staff_id){
        $permission = json_decode($staff_info['permission'],true);
        switch ($permission['hotel_info']['type']) {
            case 1:
                if($area_id==0 && $staff_id==0){
                    $this->to_back(1001);
                }
                $type = 1;
                break;
            case 2:
                if(!in_array($area_id,$permission['hotel_info']['area_ids'])){
                    $this->to_back(1001);
                }
                $type = 2;
                break;
            case 3:
                if($staff_id!=$staff_info['id']){
                    $this->to_back(1001);
                }
                $type = 3;
                break;
            default:
                $type = 0;
        }
        return $type;
    }

}