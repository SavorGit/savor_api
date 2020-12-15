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
                $this->valid_fields = array('openid'=>1001,'score'=>1002,'content'=>1002,
                    'satisfaction_id'=>1002,'tag_ids'=>1002,
                    'staff_id'=>1001,'box_mac'=>1001,'reward_id'=>1002);
                break;
            case 'reward':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'reward_id'=>1001,'staff_id'=>1001,'box_mac'=>1001,
                    'comment_id'=>1002,'money'=>1002);
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
        $satisfaction_id = intval($this->params['satisfaction_id']);
        $tag_ids = $this->params['tag_ids'];
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
            $where['room_ids']  = array('neq','');
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
            if($satisfaction_id>0){
                $comment_cacsi = C('COMMENT_CACSI');
                $label = $comment_cacsi[$satisfaction_id]['label'];
                $score_map = array('1'=>1,'2'=>3,'3'=>5);
                $data['score']   = $score_map[$satisfaction_id];
                $data['satisfaction'] = $satisfaction_id;
                if(!empty($tag_ids)){
                    $tag_ids_arr = explode(',',$tag_ids);
                    $label_arr = array();
                    foreach ($tag_ids_arr as $v){
                        $label_id = intval($v);
                        if($label_id>0){
                            $label_arr[]=$label[$label_id]['name'];
                        }
                    }
                    $data['label'] = join(',',$label_arr);
                }
            }
            $m_box = new \Common\Model\BoxModel();
            $where = array('a.mac'=>$box_mac,'a.state'=>1,'a.flag'=>0);
            $res_box = $m_box->getBoxInfo('d.id as hotel_id',$where);
            $data['hotel_id'] = intval($res_box[0]['hotel_id']);

            $ret = $m_comment->add($data);
            if($ret){
                $redis  =  \Common\Lib\SavorRedis::getInstance();
                $redis->select(1);
                $redis->set('smallapp:comment:'.$openid.'_'.$box_mac, '1',7200);

                if($staff_id>0 && !empty($box_mac)){
                    if(!empty($res_box)){
                        $m_taskuser = new  \Common\Model\Integral\TaskuserModel();
                        $m_taskuser->getCommentTask($res_staff['openid'],$res_box[0]['hotel_id']);
                    }
                }
                if($satisfaction_id>0 && $staff_id>0 && !empty($box_mac)){
                    $message = array('action'=>140,'forscreen_char'=>$comment_cacsi[$satisfaction_id]['tv_tips'],
                        'waiterName'=>'','waiterIconUrl'=>'','satisfaction'=>$satisfaction_id);
                    if(!empty($res_staff)){
                        $where = array('openid'=>$res_staff['openid']);
                        $m_user = new \Common\Model\Smallapp\UserModel();
                        $res_user = $m_user->getOne('id as user_id,avatarUrl,nickName',$where,'id desc');
                        $message['waiterName'] = $res_user['nickName'];
                        $message['waiterIconUrl'] = $res_user['avatarUrl'];

                        $font_id = 90;
                        $message['wordsize'] = 50;
                        $message['color'] = '#666666';
                        $message['font_id'] = $font_id;
                        $m_welcomeresource = new \Common\Model\Smallapp\WelcomeresourceModel();
                        $m_media = new \Common\Model\MediaModel();
                        $res_font = $m_welcomeresource->getInfo(array('id'=>$font_id));
                        $res_media = $m_media->getMediaInfoById($res_font['media_id']);
                        $message['font_oss_addr'] = $res_media['oss_addr'];
                    }
                    $m_netty = new \Common\Model\NettyModel();
                    $res_netty = $m_netty->pushBox($box_mac,json_encode($message));
                    if(isset($res_netty['error_code']) && $res_netty['error_code']==90109){
                        $m_netty->pushBox($box_mac,json_encode($message));
                    }
                }
                $res_data = array('comment_id'=>$ret);
                $this->to_back($res_data);
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
        $comment_id = intval($this->params['comment_id']);
        $money = intval($this->params['money']);
        $box_mac = $this->params['box_mac'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('id'=>$staff_id,'status'=>1);
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
        if($reward_id){
            $money_list = C('REWARD_MONEY_LIST');
            if(!isset($money_list[$reward_id])){
                $this->to_back(90158);
            }
            $money = $money_list[$reward_id]['price'];
        }else{
            $money = intval($money);
            if($money<0){
                $this->to_back(90158);
            }
        }
        $m_box = new \Common\Model\BoxModel();
        $where = array('a.mac'=>$box_mac,'a.state'=>1,'a.flag'=>0);
        $res_box = $m_box->getBoxInfo('d.id as hotel_id',$where);


        $m_reward = new \Common\Model\Smallapp\RewardModel();
        $add_data = array('staff_id'=>$staff_id,'user_id'=>$user_info['id'],'box_mac'=>$box_mac,'money'=>$money,'status'=>1);
        $add_data['hotel_id'] = intval($res_box[0]['hotel_id']);
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

        if($comment_id>0){
            $m_comment = new \Common\Model\Smallapp\CommentModel();
            $m_comment->updateData(array('id'=>$comment_id),array('reward_id'=>$order_id));
        }
        $resp_data = array('order_id'=>$order_id,'payinfo'=>$payinfo);
        $this->to_back($resp_data);
    }

}