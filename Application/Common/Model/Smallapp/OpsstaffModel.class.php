<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class OpsstaffModel extends BaseModel{
	protected $tableName='ops_staff';

	public function get_permission_city($staff_info){
	    $m_area = new \Common\Model\AreaModel();
        $fields = "id as area_id,region_name as area_name";
	    $permission = json_decode($staff_info['permission'],true);
	    switch ($permission['hotel_info']['type']){
            case 1:
                $where = array('is_in_hotel'=>1,'is_valid'=>1);
                $permission_city = $m_area->field($fields)->where($where)->order('id asc')->select();
                $tmp = array('area_id'=>0,'area_name'=>'全部');
                array_unshift($permission_city, $tmp);
                foreach ($permission_city as $k=>$v){
                    $staff_list = array();
                    if($v['area_id']){
                        $fields = 'a.id as staff_id,su.remark as staff_name';
                        $staff_list = $this->getStaffinfo($fields,array('a.area_id'=>$v['area_id'],'a.status'=>1,'a.hotel_role_type'=>array('in',array(3,4))));
                        if(!empty($staff_list)){
                            $stmp = array('staff_id'=>0,'staff_name'=>'全部');
                            array_unshift($staff_list, $stmp);
                        }
                    }
                    $permission_city[$k]['staff_list'] = $staff_list;
                }
                break;
            case 2:
            case 4:
                $where = array('is_in_hotel'=>1,'is_valid'=>1,'id'=>array('in',$permission['hotel_info']['area_ids']));
                $permission_city = $m_area->field($fields)->where($where)->order('id asc')->select();
                foreach ($permission_city as $k=>$v){
                    $staff_list = array();
                    if($v['area_id']){
                        $fields = 'a.id as staff_id,su.remark as staff_name';
                        $staff_list = $this->getStaffinfo($fields,array('a.area_id'=>$v['area_id'],'a.status'=>1,'a.hotel_role_type'=>array('in',array(3,4))));
                        if(!empty($staff_list)){
                            $stmp = array('staff_id'=>0,'staff_name'=>'全部');
                            array_unshift($staff_list, $stmp);
                        }
                    }
                    $permission_city[$k]['staff_list'] = $staff_list;
                }
                break;
            case 3:
                $permission_city = array();
                break;
            default:
                $permission_city = array();
        }
        return $permission_city;
    }

    public function getStaffinfo($fields,$where){
        $res_data = $this->alias('a')
            ->join('savor_sysuser su on a.sysuser_id=su.id','left')
            ->field($fields)
            ->where($where)
            ->select();
        return $res_data;
    }

    public function getStaffUserinfo($fields,$where){
        $res_data = $this->alias('a')
            ->join('savor_smallapp_user user on a.openid=user.openid','left')
            ->join('savor_sysuser su on a.sysuser_id=su.id','left')
            ->field($fields)
            ->where($where)
            ->select();
        return $res_data;
    }

    public function checkStaffpermission($staff_info,$area_id,$staff_id){
        $permission = json_decode($staff_info['permission'],true);
        switch ($permission['hotel_info']['type']) {
            case 1:
                $type = 1;
                break;
            case 2:
                $type = 2;
                if(!in_array($area_id,$permission['hotel_info']['area_ids'])){
                    $type = 1001;//系统报错码
                }
                break;
            case 3:
                $type = 3;
                if($staff_id!=$staff_info['id']){
                    $type = 1001;
                }
                break;
            case 4:
                $type = 4;
                if($area_id>0){
                    if(!in_array($area_id,$permission['hotel_info']['area_ids'])){
                        $type = 1001;
                    }
                }
                break;
            default:
                $type = 0;
        }
        return $type;
    }

}