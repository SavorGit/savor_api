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
            case 'config':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'openid'=>1001);
                break;
            case 'subComment':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'score'=>1001,'content'=>1001,'staff_id'=>1001,'box_mac'=>1001);
                break;
            case 'reward':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'reward_id'=>1001,'staff_id'=>1001,'box_mac'=>1001);
                break;
            
        }
        parent::_init_();
   
    }

    public function config(){
        $box_mac = $this->params['box_mac'];
        $openid = $this->params['openid'];

        $m_box = new \Common\Model\BoxModel();
        $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);
        $redis = new \Common\Lib\SavorRedis();
        if(isset($forscreen_info['box_id'])){
            $redis->select(15);
            $cache_key = 'savor_box_'.$forscreen_info['box_id'];
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info,true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $hotel_info = array('box_id'=>$forscreen_info['box_id'],'hotel_id'=>$room_info['hotel_id'],'room_id'=>$box_info['room_id']);
        }else{
            $map = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
            $rets = $m_box->getBoxInfo('d.id hotel_id,c.id room_id,a.id box_id',$map);
            $hotel_info = $rets[0];
        }

        $redis->select(1);
        $comment_count = $redis->get('smallapp:comment:'.$openid.'_'.$box_mac);
        if(!empty($comment_count)){
            $is_open_popcomment = 0;
        }else{
            if($forscreen_info['is_open_popcomment']==1){
                $is_open_popcomment = 1;
            }else{
                $is_open_popcomment = 0;
            }
        }
        $comment_str = '服务评分';
        $waiter_str = '服务专员';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getInfo(array('hotel_id'=>$hotel_info['hotel_id'],'room_id'=>$hotel_info['room_id'],'status'=>1));
        if(!empty($res_staff)){
            $staff_openid = $res_staff['openid'];
            $m_user = new \Common\Model\Smallapp\UserModel();
            $where = array('openid'=>$staff_openid);
            $user_info = $m_user->getOne('avatarUrl,nickName',$where,'id desc');
            $staffuser_info = array('staff_id'=>$res_staff['id'],'avatarUrl'=>$user_info['avatarUrl'],'nickName'=>$user_info['nickName'],
                'comment_str'=>$comment_str,'waiter_str'=>$waiter_str);
            $category = 1;
        }else{
            $comment_str = '餐厅评分';
            $waiter_str = '';
            $staffuser_info = array('staff_id'=>0,'comment_str'=>$comment_str,'waiter_str'=>$waiter_str);
            $category = 3;
        }
        $m_tags = new \Common\Model\Smallapp\TagsModel();
        $fields = 'id,name';
        $where = array('status'=>1,'category'=>$category);
        $where['hotel_id'] = array('in',array($hotel_info['hotel_id'],0));
        $res_tags = $m_tags->getDataList($fields,$where,'type desc,id desc');
        $tags = array();
        foreach ($res_tags as $v){
            $tags[] = array('id'=>$v['id'],'value'=>$v['name'],'selected'=>false);
        }
        $reward_money_list = C('REWARD_MONEY_LIST');
        $reward_money = array();
        $oss_host = C('OSS_HOST');
        foreach ($reward_money_list as $v){
            $v['image'] = 'http://'.$oss_host.'/'.$v['image'];
            $v['selected'] = false;
            $reward_money[]=$v;
        }

        $res_data = array('tags'=>$tags,'is_open_popcomment'=>$is_open_popcomment,'staff_user_info'=>$staffuser_info,
            'reward_money'=>$reward_money);
        $this->to_back($res_data);
    }




    public function subComment(){
        $openid = $this->params['openid'];
        $score  = intval($this->params['score']);
        $content = $this->params['content'];
        $staff_id = intval($this->params['staff_id']);
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
            $ret = $m_comment->add($data);
            if($ret){
                $redis  =  \Common\Lib\SavorRedis::getInstance();
                $redis->select(1);
                $redis->set('smallapp:comment:'.$openid.'_'.$box_mac, '1',7200);
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
        $add_data = array('staff_id'=>$staff_id,'user_id'=>$user_info['user_id'],'box_mac'=>$box_mac,'money'=>$money,'status'=>1);
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