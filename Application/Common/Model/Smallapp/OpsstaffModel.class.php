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
            case 5:
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
            case 6:
            case 8:
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
    
    public function get_permission_work_city($staff_info){
        $m_area = new \Common\Model\AreaModel();
        $fields = "id as area_id,region_name as area_name";
        $permission = json_decode($staff_info['permission'],true);
        switch ($permission['hotel_info']['type']){
            case 1:
            case 5:
                $where = array('is_in_hotel'=>1,'is_valid'=>1);
                $permission_city = $m_area->field($fields)->where($where)->order('id asc')->select();
                $tmp = array('area_id'=>0,'area_name'=>'全部');
                array_unshift($permission_city, $tmp);
                foreach ($permission_city as $k=>$v){
                    $staff_list = array();
                    if($v['area_id']){
                        $fields = 'a.id as staff_id,su.remark as staff_name';
                        $mps = [];
                        $mps['a.area_id'] = $v['area_id'];
                        $mps['a.status'] = 1;
                        $mps['a.hotel_role_type'] = array('in',array(3,4));
                        
                        $wheres = " (a.area_id=".$v['area_id']." and a.status=1 and a.hotel_role_type in(3,4)) or (a.area_id=".$v['area_id']." and a.status=1  and a.is_operrator=1)";
                        $staff_list = $this->getStaffinfo($fields,$wheres);
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
            case 6:
            case 8:
                $where = array('is_in_hotel'=>1,'is_valid'=>1,'id'=>array('in',$permission['hotel_info']['area_ids']));
                $permission_city = $m_area->field($fields)->where($where)->order('id asc')->select();
                foreach ($permission_city as $k=>$v){
                    $staff_list = array();
                    if($v['area_id']){
                        $fields = 'a.id as staff_id,su.remark as staff_name';
                        $wheres = " (a.area_id=".$v['area_id']." and a.status=1 and a.hotel_role_type in(3,4)) or (a.area_id=".$v['area_id']." and a.status=1  and a.is_operrator=1)";
                        $staff_list = $this->getStaffinfo($fields,$wheres);
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

    public function get_check_city($staff_info){
        $m_area = new \Common\Model\AreaModel();
        $fields = "id as area_id,region_name as area_name";
        $permission = json_decode($staff_info['permission'],true);
        switch ($permission['hotel_info']['type']){
            case 1:
            case 5:
                $where = array('is_in_hotel'=>1,'is_valid'=>1);
                $permission_city = $m_area->field($fields)->where($where)->order('id asc')->select();
                $tmp = array('area_id'=>0,'area_name'=>'全部');
                array_unshift($permission_city, $tmp);
                break;
            case 2:
            case 4:
            case 6:
                $where = array('is_in_hotel'=>1,'is_valid'=>1,'id'=>array('in',$permission['hotel_info']['area_ids']));
                $permission_city = $m_area->field($fields)->where($where)->order('id asc')->select();
                break;
            case 3:
                $where = array('is_in_hotel'=>1,'is_valid'=>1,'id'=>$staff_info['area_id']);
                $permission_city = $m_area->field($fields)->where($where)->order('id asc')->select();
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
                if($area_id>0 && !in_array($area_id,$permission['hotel_info']['area_ids'])){
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

    public function check_edit_salestaff($staff,$hotel_id){
        $is_edit_staff = 0;
        $hotel_role_type = $staff['hotel_role_type'];//酒楼角色类型1全国,2城市,3个人,4城市和个人,5全国财务,6城市财务
        $permission = json_decode($staff['permission'],true);
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getHotelById('hotel.area_id,ext.maintainer_id',array('hotel.id'=>$hotel_id));
        if($staff['is_operrator']==1){
            if($staff['sysuser_id']==$res_hotel['maintainer_id']){
                $is_edit_staff = 1;
            }
        }else{
            switch ($hotel_role_type){
                case 1:
                    $is_edit_staff = 1;
                    break;
                case 2:
                    if(in_array($res_hotel['area_id'],$permission['hotel_info']['area_ids'])){
                        $is_edit_staff = 1;
                    }
                    break;
                case 3:
                    if($staff['sysuser_id']==$res_hotel['maintainer_id']){
                        $is_edit_staff = 1;
                    }
                    break;
                case 4:
                    if(in_array($res_hotel['area_id'],$permission['hotel_info']['area_ids'])){
                        $is_edit_staff = 1;
                    }elseif($staff['sysuser_id']==$res_hotel['maintainer_id']){
                        $is_edit_staff = 1;
                    }
                    break;
            }
        }

        return $is_edit_staff;
    }

}