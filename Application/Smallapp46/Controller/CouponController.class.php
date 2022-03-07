<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class CouponController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'banner':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'receive':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'coupon_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function banner(){
        $openid = $this->params['openid'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90157);
        }
        $m_coupon = new \Common\Model\Smallapp\CouponModel();
        $m_coupon_user = new \Common\Model\Smallapp\UserCouponModel();
        $nowtime = date('Y-m-d H:i:s');
        $where = array('status'=>1);
        $where['start_time'] = array('elt',$nowtime);
        $where['end_time'] = array('egt',$nowtime);
        $res_coupon = $m_coupon->getDataList('*',$where,'id desc');
        $datalist = array();
        if(!empty($res_coupon)){
            foreach ($res_coupon as $v){
                $status = 1;//立即领取
                $res_coupon_user = $m_coupon_user->getInfo(array('openid'=>$openid,'coupon_id'=>$v['id']));
                if(!empty($res_coupon_user) && $res_coupon_user['ustatus']==1){
                    $status = 2;
                }
                $info = array('coupon_id'=>$v['id'],'name'=>$v['name'],'remark'=>$v['remark'],'status'=>$status);
                $datalist[]=$info;
            }
        }
        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function receive(){
        $openid = $this->params['openid'];
        $coupon_id = intval($this->params['coupon_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90157);
        }
        $m_coupon = new \Common\Model\Smallapp\CouponModel();
        $coupon_info = $m_coupon->getInfo(array('id'=>$coupon_id));

        $add_data = array('openid'=>$openid,'coupon_id'=>$coupon_id,'money'=>$coupon_info['money'],
            'min_price'=>$coupon_info['min_price'],'max_price'=>$coupon_info['max_price'],'ustatus'=>1
        );

        $m_coupon_user = new \Common\Model\Smallapp\UserCouponModel();
        $res_coupon_user = $m_coupon_user->getInfo(array('openid'=>$openid,'coupon_id'=>$coupon_id));
        if(empty($res_coupon_user)){
            $m_coupon_user->add($add_data);
        }else{
            if($res_coupon_user['ustatus']==2){
                $m_coupon_user->add($add_data);
            }
        }
        $this->to_back(array());
    }


}