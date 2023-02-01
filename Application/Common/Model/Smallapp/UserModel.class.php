<?php
namespace Common\Model\Smallapp;
use Think\Model;

class UserModel extends Model{
	protected $tableName='smallapp_user';
	
	public function addInfo($data,$type=1){
	    if($type==1){
	        $ret = $this->add($data);
	        
	    }else {
	        $ret = $this->addAll($data);
	    }
	    return $ret;
	}
	public function updateInfo($where,$data){
	    $ret = $this->where($where)->save($data);
	    return $ret;
	}
	public function getWhere($fields,$where,$order,$limit,$group){
	    $data = $this->field($fields)->where($where)->order($order)->group($group)->limit($limit)->select();
	    return $data;
	}
	public function getOne($fields,$where,$order){
	    $data =  $this->field($fields)->where($where)->order($order)->find();
	    return $data;
	}
	public function countNum($where){
	    $nums = $this->where($where)->count();
	    return $nums;
	}

	public function getMemberPopupinfo($openid,$hotel_id=0,$room_id=0,$box_id=0){
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $this->getOne('id,openid,avatarUrl,nickName,mpopenid,vip_level,mobile,is_wx_auth', $where, '');

        $vip_level = 0;
        $is_wx_auth = 0;
        $mobile = '';
        if(!empty($user_info)){
            $vip_level = $user_info['vip_level'];
            $mobile = $user_info['mobile'];
            $is_wx_auth = $user_info['is_wx_auth'];
        }
        $coupon_money = 0;
        $coupon_end_time = '';
        $coupon_unnum = 0;
        if($vip_level==0){
            $m_sys_config = new \Common\Model\SysConfigModel();
            $sys_info = $m_sys_config->getAllconfig();
            $vip_coupons = json_decode($sys_info['vip_coupons'],true);
            $now_vip_level = 1;
            if(!empty($vip_coupons) && !empty($vip_coupons[$now_vip_level])){
                $m_coupon = new \Common\Model\Smallapp\CouponModel();
                $where = array('id'=>array('in',$vip_coupons[$now_vip_level]));
                $where['end_time'] = array('egt',date('Y-m-d H:i:s'));
                $res_all_coupon = $m_coupon->getALLDataList('*',$where,'end_time desc','','');
                $end_time = date('Y年m月d日',strtotime($res_all_coupon[0]['end_time']));
                $coupon_end_time = $end_time.'到期';
                foreach ($res_all_coupon as $v){
                    $coupon_money+=$v['money'];
                }
            }
        }else{
            $where = array('a.openid'=>$openid,'a.ustatus'=>1,'a.status'=>1,'coupon.type'=>2);
            $where['a.end_time'] = array('egt',date('Y-m-d H:i:s'));
            $fields = 'count(a.id) as num';
            $m_coupon_user = new \Common\Model\Smallapp\UserCouponModel();
            $res_coupon = $m_coupon_user->getUsercouponDatas($fields,$where,'a.id desc','');
            if(!empty($res_coupon)){
                $coupon_unnum = intval($res_coupon[0]['num']);
            }
        }
        $data = array('openid'=>$openid,'vip_level'=>$vip_level,'coupon_money'=>$coupon_money,'coupon_end_time'=>$coupon_end_time,
            'coupon_unnum'=>$coupon_unnum,'mobile'=>$mobile,'is_wx_auth'=>$is_wx_auth,
            'hotel_id'=>$hotel_id,'room_id'=>$room_id,'box_id'=>$box_id,'code_msg'=>'');
        return $data;
    }
}