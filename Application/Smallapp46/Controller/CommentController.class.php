<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class CommentController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'subComment':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'score'=>1001,'content'=>1002,'staff_id'=>1001,'box_mac'=>1001,'reward_id'=>1002);
                break;
            case 'reward':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'reward_id'=>1001,'staff_id'=>1001,'box_mac'=>1001);
                break;
        }
        parent::_init_();
   
    }

    public function subComment(){
        $openid = $this->params['openid'];
        $score  = intval($this->params['score']);
        $content = $this->params['content'];
        $staff_id = intval($this->params['staff_id']);
        $reward_id = intval($this->params['reward_id']);
        $box_mac = $this->params['box_mac'];
        if($score>5){
            $this->to_back(90155);
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        $type = 2;
        if($staff_id>0){
            $type = 1;
            $where = array('id'=>$staff_id,'status'=>1);
            $where['hotel_id'] = array('neq',0);
            $where['room_id']  = array('neq',0);
            $res_staff = $m_staff->getInfo($where);
            if(empty($res_staff)){
                $this->to_back(90156);
            }
        }

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        
        if(!empty($user_info)){
            $m_comment = new \Common\Model\Smallapp\CommentModel();
            $data = array();
            
            $data['staff_id']= $staff_id;
            $data['user_id'] = $user_info['id'];
            $data['box_mac'] = $box_mac;
            $data['score']   = $score;
            $data['content']  = $content;
            $data['status']  = 1;
            $data['type']  = $type;
            if($reward_id>0){
                $data['reward_id'] = $reward_id;
            }
            $ret = $m_comment->add($data);
            if($ret){
                $redis  =  \Common\Lib\SavorRedis::getInstance();
                $redis->select(1);
                $redis->set('smallapp:comment:'.$openid.'_'.$box_mac, '1',7200);

                if($staff_id>0 && !empty($box_mac)){
                    $m_box = new \Common\Model\BoxModel();
                    $where = array('a.state'=>1,'a.flag'=>0);
                    $res_box = $m_box->getBoxInfo('d.id as hotel_id',$where);
                    if(!empty($res_box)){
                        $m_taskuser = new  \Common\Model\Integral\TaskuserModel();
                        $m_taskuser->getCommentTask($res_staff['openid'],$res_box[0]['hotel_id']);
                    }
                }

                $this->to_back(10000);
            }else {
                $this->to_back(90154);
            }
        }else{
            $this->to_back(90116);
        }
    }

    public function reward(){
        $openid = $this->params['openid'];
        $reward_id  = intval($this->params['reward_id']);
        $staff_id = intval($this->params['staff_id']);
        $box_mac = $this->params['box_mac'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('id'=>$staff_id,'status'=>1);
        $where['hotel_id'] = array('neq',0);
        $where['room_id']  = array('neq',0);
        $res_staff = $m_staff->getInfo($where);
        if(empty($res_staff)){
            $this->to_back(90156);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $money_list = C('REWARD_MONEY_LIST');
        if(!isset($money_list[$reward_id])){
            $this->to_back(90158);
        }
        $money = $money_list[$reward_id]['price'];
        $m_reward = new \Common\Model\Smallapp\RewardModel();
        $add_data = array('staff_id'=>$staff_id,'user_id'=>$user_info['id'],'box_mac'=>$box_mac,'money'=>$money,'status'=>1);
        $order_id = $m_reward->add($add_data);
        $m_ordermap = new \Common\Model\Smallapp\OrdermapModel();
        $trade_no = $m_ordermap->add(array('order_id'=>$order_id,'pay_type'=>10));

        $trade_name = '打赏'.$money.'元';
        $trade_info = array('trade_no'=>$trade_no,'total_fee'=>$money,'trade_name'=>$trade_name,
            'wx_openid'=>$openid,'redirect_url'=>'','attach'=>60);
        $smallapp_config = C('SMALLAPP_CONFIG');
        $pay_wx_config = C('PAY_WEIXIN_CONFIG_1594752111');
        $payconfig = array(
            'appid'=>$smallapp_config['appid'],
            'partner'=>$pay_wx_config['partner'],
            'key'=>$pay_wx_config['key']
        );
        $m_payment = new \Payment\Model\WxpayModel(3);
        $wxpay = $m_payment->pay($trade_info,$payconfig);
        $payinfo = json_decode($wxpay,true);

        $resp_data = array('order_id'=>$order_id,'payinfo'=>$payinfo);
        $this->to_back($resp_data);
    }

}