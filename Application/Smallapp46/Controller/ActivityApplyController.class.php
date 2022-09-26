<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class ActivityApplyController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'lottery_apply_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function detail(){
        $openid = $this->params['openid'];
        $lottery_apply_id = intval($this->params['lottery_apply_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90157);
        }
        $m_activity_apply = new \Common\Model\Smallapp\ActivityapplyModel();
        $res_apply = $m_activity_apply->getInfo(array('id'=>$lottery_apply_id));
        $res_data = array();
        if(!empty($res_apply)){
            $oss_host = get_oss_host();
            $m_prize = new \Common\Model\Smallapp\ActivityprizeModel();
            $res_prize = $m_prize->getInfo(array('id'=>$res_apply['prize_id']));
            $prize = $res_prize['name'];
            $img_url = $oss_host.$res_prize['image_url'];

            $host_name = 'https://'.$_SERVER['HTTP_HOST'];
            $en_data = array('type'=>'goods','id'=>$lottery_apply_id);
            $data_id = encrypt_data(json_encode($en_data));
            $qrcode_url = $host_name."/smallapp46/qrcode/getCouponQrcode?data_id={$data_id}";

            $res_data = array('openid'=>$openid,'qrcode_url'=>$qrcode_url,'prize'=>$prize,'img_url'=>$img_url,
                'lottery_time'=>$res_apply['add_time'],'hotel_name'=>$res_apply['hotel_name']);
        }
        $this->to_back($res_data);
    }
}