<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class UserIntegralrecordModel extends BaseModel{
	protected $tableName='smallapp_user_integralrecord';

	public function getIntegralBytime($openid,$type,$start_time,$end_time){
        $where = array('openid'=>$openid,'type'=>$type);
        $where['add_time'] = array(array('egt',$start_time), array('elt',$end_time));
        $fields = 'sum(integral) as total_integral';
        $res = $this->field($fields)->where($where)->find();
        $total_integral = 0;
        if(!empty($res)){
            $total_integral = intval($res['total_integral']);
        }
        return $total_integral;
    }

    public function activityPromote($openid,$box_mac,$goods_id,$type){
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $key_integral = C('SAPP_SALE_ACTIVITY_PROMOTE');
        $key_opintegral = $key_integral.date('Ymd').':'.$openid;
        $res_opintegral = $redis->get($key_opintegral);
        $data = array('date'=>date('Y-m-d H:i:s'),'goods_id'=>$goods_id,'box_mac'=>$box_mac);
        if(!empty($res_opintegral)){
            $res_opintegral = json_decode($res_opintegral,true);
            $res_opintegral[$type][]=$data;
        }else{
            $res_opintegral=array();
            $res_opintegral[$type][]=$data;
        }
        $redis->set($key_opintegral,json_encode($res_opintegral),86400*7);
    }

    public function activityRewardIntegral($openid,$box_mac,$goods_id=0,$hotel_id=0){
        $feast_time = C('FEAST_TIME');
        $now_date = date('Y-m-d');
        $lunch_stime = $now_date.' '.$feast_time['lunch'][0];
        $lunch_etime = $now_date.' '.$feast_time['lunch'][1];
        $dinner_stime = $now_date.' '.$feast_time['dinner'][0];
        $dinner_etime = $now_date.' '.$feast_time['dinner'][1];
        $now_time = date('Y-m-d H:i');
        $fj_type = 0;//饭局类型0无,1午饭,2晚饭
        if($now_time>=$lunch_stime && $now_time<=$lunch_etime){
            $fj_type = 1;
        }elseif($now_time>=$dinner_stime && $now_time<=$dinner_etime){
            $fj_type = 2;
        }
        if($openid && $fj_type){
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(14);
            $key_integral = C('SAPP_SALE_OPGOODS_INTEGRAL');
            $key_opintegral = $key_integral.date('Ymd').':'.$openid;
            $res_opintegral = $redis->get($key_opintegral);
            $fj_data = array();
            if(!empty($res_opintegral)){
                $res_opintegral = json_decode($res_opintegral,true);
                if(!isset($res_opintegral[$fj_type])){
                    $fj_data = array('date'=>date('Y-m-d H:i:s'),'goods_id'=>$goods_id);
                }
            }else{
                $res_opintegral = array();
                $fj_data = array('date'=>date('Y-m-d H:i:s'),'goods_id'=>$goods_id);
            }


            if(!empty($fj_data)){
                $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
                if($hotel_id){
                    $where['merchant.hotel_id']=$hotel_id;
                }
                $m_staff = new \Common\Model\Integral\StaffModel();
                $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
                if(!empty($res_staff)){
                    $m_integral_record = new \Common\Model\Smallapp\UserIntegralrecordModel();
                    $tmp_date = date('Y-m');
                    $start_time = $tmp_date.'-01 00:00:00';
                    $end_time = $tmp_date.'-31 23:59:59';

                    $max_integral = 3540;
                    $integral = 60;
                    $type = 6;
                    $total_integral = $m_integral_record->getIntegralBytime($openid,$type,$start_time,$end_time);
                    if($total_integral<$max_integral){
                        $res_opintegral[$fj_type] = $fj_data;
                        $redis->set($key_opintegral,json_encode($res_opintegral));

                        $m_box = new \Common\Model\BoxModel();
                        $res_box = $m_box->getHotelInfoByBoxMacNew($box_mac);
                        $integralrecord_data = array('openid'=>$openid,'area_id'=>$res_box['area_id'],'area_name'=>$res_box['area_name'],
                            'hotel_id'=>$res_box['hotel_id'],'hotel_name'=>$res_box['hotel_name'],'hotel_box_type'=>$res_box['hotel_box_type'],
                            'room_id'=>$res_box['room_id'],'room_name'=>$res_box['room_name'],'box_id'=>$res_box['box_id'],'box_mac'=>$box_mac,
                            'box_type'=>$res_box['box_type'],'integral'=>$integral,'goods_id'=>$goods_id,'content'=>1,'type'=>$type,
                            'fj_type'=>$fj_type,'integral_time'=>date('Y-m-d H:i:s'));
                        $m_userintegralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
                        $m_userintegralrecord->add($integralrecord_data);

                        $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
                        $res_integral = $m_userintegral->getInfo(array('openid'=>$openid));
                        if(!empty($res_integral)){
                            $userintegral = $res_integral['integral']+$integral;
                            $data = array('integral'=>$userintegral,'update_time'=>date('Y-m-d H:i:s'));
                            $m_userintegral->updateData(array('id'=>$res_integral['id']),$data);
                        }else{
                            $data = array('openid'=>$openid,'integral'=>$integral,'update_time'=>date('Y-m-d H:i:s'));
                            $m_userintegral->addData($data);
                        }
                    }
                }
            }
        }
    }

}