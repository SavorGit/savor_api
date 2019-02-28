<?php
namespace Smallapp3\Controller;
use \Common\Controller\CommonController;
class RedpacketController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getConfig'://获取红包业务配置
                $this->is_verify =1;
                $this->valid_fields = array('type'=>1001);
                break;
            case 'sendTvbonus':
                $this->is_verify =1;
                $this->valid_fields = array('open_id'=>1001,'total_money'=>1001,'amount'=>1001,
                    'surname'=>1001,'sex'=>1001,'bless_id'=>1001,'scope'=>1001,'mac'=>1001);
                break;
            case 'getresult':
                $this->is_verify =1;
                $this->valid_fields = array('open_id'=>1001,'order_id'=>1001);
                break;
            case 'getScanresult':
                $this->is_verify =1;
                $this->valid_fields = array('open_id'=>1001,'order_id'=>1001);
                break;
            case 'grabBonus':
                $this->is_verify =1;
                $this->valid_fields = array('order_id'=>1001,'status'=>1001,'user_id'=>1001,'sign'=>1001);
                break;
            case 'grabBonusResult':
                $this->is_verify =1;
                $this->valid_fields = array('order_id'=>1001,'user_id'=>1001,'sign'=>1001);
                break;
            case 'sendList':   //发送红包列表
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001);
                break;
            case 'redpacketDetail':  //红包领取详情
                $this->is_verify = 1;
                $this->valid_fields = array('order_id'=>1001,'page'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getConfig(){
        $type = $this->params['type'];
        $data = array();
        if($type==1){//红包开关
            $data['is_open_red_packet'] = 1;
        }else if($type==2){//发红包配置
            
            $cf_bless_arr = C('SMALLAPP_REDPACKET_BLESS');
            $cf_range_arr = C('SMALLAPP_REDPACKET_SEND_RANGE');
            foreach($cf_bless_arr as $v){
                $bless_tmp[] = $v;
            }
            foreach($cf_range_arr as $v){
                $range_tmp[] = $v;
            }
            
            $data['bless'] = $bless_tmp;
            $data['range'] = $range_tmp;
        }
        
        $this->to_back($data);
    }

    /**
     * @desc 发送电视红包
     */
    public function sendTvbonus(){
        $open_id = $this->params['open_id'];
        $total_money = $this->params['total_money'];
        $amount = $this->params['amount'];
        $surname = $this->params['surname'];
        $sex = $this->params['sex'];
        $bless_id = $this->params['bless_id'];
        $scope = $this->params['scope'];
        $box_mac = $this->params['mac'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$open_id,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        if($total_money<$amount*0.3){
            $this->to_back(90117);
        }
        $surname_c = new \Common\Lib\Surname();
        $check_name = $surname_c->checkSurname($surname);
        if(!$check_name){
            $this->to_back(90118);
        }
        if(!array_key_exists($scope,C('SMALLAPP_REDPACKET_SEND_RANGE'))){
            $this->to_back(90119);
        }
        $m_box = new \Common\Model\BoxModel();
        $where = array();
        $where['mac'] = $box_mac;
        $where['flag'] = 0;
        $where['state']= 1;
        $box_info = $m_box->where($where)->count();
        if(empty($box_info)){
            $this->to_back(15003);
        }
        $redpacket = array('user_id'=>$user_info['id'],'total_fee'=>$total_money,'amount'=>$amount,'surname'=>$surname,
            'sex'=>$sex,'bless_id'=>$bless_id,'scope'=>$scope,'mac'=>$box_mac);
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $order_id = $m_redpacket->addData($redpacket);
        if(!$order_id){
            $this->to_back(90120);
        }
        $sign_key = create_sign($order_id);
        $id_key = $sign_key.$order_id;
        $jump_url = http_host().'/h5/scanqrcode/scanpage?id='.$id_key;

        $result = array('order_id'=>$order_id,'jump_url'=>$jump_url);
        $this->to_back($result);
    }

    //获取扫电视红包码结果
    public function getScanresult(){
        $open_id = $this->params['open_id'];
        $order_id = $this->params['order_id'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$open_id,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $user_id = $user_info['id'];
        $m_order = new \Common\Model\Smallapp\RedpacketModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        $red_packet_key = C('SAPP_REDPACKET');
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);

        $status = 0;
        if(empty($user_info['mpopenid'])){
            $key_bonus = $red_packet_key.$order_id.':bonus';//红包列表
            $res_redpacket = $redis->get($key_bonus);
            $resdata = json_decode($res_redpacket,true);

            $key_grabbonus = $red_packet_key.$order_id.':grabbonus';//抢红包用户队列
            $res_grabbonus = $redis->lgetrange($key_grabbonus,0,1000);
            if(empty($resdata['unused']) || count($res_grabbonus)>=$res_order['amount']*2){
                $status = 2;//红包已领完,未领到
            }
        }else{
            $key_hasget = $red_packet_key.$order_id.':hasget';//已经抢到红包的用户列表
            $res_hasget = $redis->get($key_hasget);
            $get_money = '';
            if($res_hasget){
                $hasget_users = json_decode($res_hasget,true);
                if(array_key_exists($user_id,$hasget_users)){
                    $status = 1;//已领取红包
                    $get_money = $hasget_users[$user_id];
                }
            }
            if($status!=1){
                $key_bonus = $red_packet_key.$order_id.':bonus';//红包列表
                $res_redpacket = $redis->get($key_bonus);
                $resdata = json_decode($res_redpacket,true);
                if(empty($resdata['unused'])){
                    $status = 2;//红包已领完,未领到
                }else{
                    $key_getbonus = $red_packet_key.$order_id.':getbonus';//领红包用户队列
                    $res_getbonus = $redis->lgetrange($key_getbonus,0,1000);
                    if(in_array($user_id,$res_getbonus)){
                        $status = 3;//正在领红包
                    }else{
                        $key_grabbonus = $red_packet_key.$order_id.':grabbonus';//抢红包用户队列
                        $res_grabbonus = $redis->lgetrange($key_grabbonus,0,1000);
                        if(empty($res_grabbonus)){
                            $redis->rpush($key_grabbonus,$user_id);
                            $status = 4;//进入抢红包队列,同时生成token
                        }else{
                            if(count($res_grabbonus)>=$res_order['amount']*2){
                                $status = 2;//红包已领完,未领到
                            }else{
                                $redis->rpush($key_grabbonus,$user_id);
                                $status = 4;//进入抢红包队列,同时生成token
                            }
                        }
                    }
                }
            }
        }
        $sign = create_sign($status.$order_id.$user_id);
        $res_data = array('status'=>$status,'order_id'=>$order_id,'user_id'=>$user_id,'sign'=>$sign);
        switch ($status){
            case 0:
                $jump_url = http_host().'/h5/scanqrcode/grabBonus?oid='.$order_id.'&guid='.$user_id;
                $res_data['jump_url'] = $jump_url;
                break;
            case 1:
                $all_bless = C('SMALLAPP_REDPACKET_BLESS');
                $res_data['bless'] = $all_bless[$res_order['bless_id']];
                $res_data['money'] = $get_money;

                $where = array('id'=>$res_order['user_id']);
                $user_info = $m_user->getOne('*',$where,'');
                $res_data['nickName'] = $user_info['nickName'];
                $res_data['avatarUrl'] = $user_info['avatarUrl'];
                break;
            case 2:
                $res_data['bless'] = '手慢了，红包抢完了';
                $res_data['money'] = 0;
                $where = array('id'=>$res_order['user_id']);
                $user_info = $m_user->getOne('*',$where,'');
                $res_data['nickName'] = $user_info['nickName'];
                $res_data['avatarUrl'] = $user_info['avatarUrl'];
                break;
            case 3:
                $res_data['tips'] = '正在领红包,请稍后';
                break;
            case 4:
                $token = create_sign($order_id.$user_id);
                $jump_url = http_host().'/h5/scanqrcode/grabBonus?oid='.$order_id.'&guid='.$user_id.'&token='.$token;
                $res_data['jump_url'] = $jump_url;
                break;
        }

        $this->to_back($res_data);
    }


    //获取发红包结果
    public function getresult(){
        $open_id = $this->params['open_id'];
        $order_id = $this->params['order_id'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$open_id,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $res_order = $m_redpacket->getInfo(array('id'=>$order_id));
        $status = $res_order['status'];
        $jump_url = http_host().'/h5/scanqrcode/getresult?oid='.$order_id;
        $data = array('status'=>$status,'jump_url'=>$jump_url);
        $this->to_back($data);
    }

    public function grabBonus(){
        $order_id = $this->params['order_id'];
        $status = $this->params['status'];
        $user_id = $this->params['user_id'];
        $sign = $this->params['sign'];
        $now_sign = create_sign($status.$order_id.$user_id);
        if($sign!=$now_sign){
            $this->to_back(90121);
        }

        $m_order = new \Common\Model\Smallapp\RedpacketModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));

        $red_packet_key = C('SAPP_REDPACKET');
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);

        $key_hasget = $red_packet_key.$order_id.':hasget';//已经抢到红包的用户列表
        $res_hasget = $redis->get($key_hasget);
        $get_money = '';
        if($res_hasget){
            $hasget_users = json_decode($res_hasget,true);
            if(array_key_exists($user_id,$hasget_users)){
                $status = 1;//已领取红包
                $get_money = $hasget_users[$user_id];
            }
        }
        if($status!=1){
            $key_bonus = $red_packet_key.$order_id.':bonus';//红包列表
            $res_redpacket = $redis->get($key_bonus);
            $resdata = json_decode($res_redpacket,true);
            if(empty($resdata['unused'])){
                $status = 2;//红包已领完,未领到
            }else{
                $key_getbonus = $red_packet_key.$order_id.':getbonus';//领红包用户队列
                $res_getbonus = $redis->lgetrange($key_getbonus,0,1000);
                if(in_array($user_id,$res_getbonus)){
                    $status = 3;//正在领红包
                }else{
                    $key_grabbonus = $red_packet_key.$order_id.':grabbonus';//抢红包用户队列
                    $res_grabbonus = $redis->lgetrange($key_grabbonus,0,1000);
                    if(!in_array($user_id,$res_grabbonus)){
                        $status = 2;
                    }else{
                        $redis->rpush($key_getbonus,$user_id);
                        $status = 3;//正在领红包
                    }
                }
            }
        }
        $sign = create_sign($order_id.$user_id);
        $res_data = array('order_id'=>$order_id,'user_id'=>$user_id,'sign'=>$sign,'status'=>$status);
        switch ($status){
            case 1:
                $all_bless = C('SMALLAPP_REDPACKET_BLESS');
                $res_data['bless'] = $all_bless[$res_order['bless_id']];
                $res_data['money'] = $get_money;

                $where = array('id'=>$res_order['user_id']);
                $m_user = new \Common\Model\Smallapp\UserModel();
                $user_info = $m_user->getOne('*',$where,'');
                $res_data['nickName'] = $user_info['nickName'];
                $res_data['avatarUrl'] = $user_info['avatarUrl'];
                break;
            case 2:
                $res_data['bless'] = '手慢了，红包抢完了';
                $res_data['money'] = 0;
                $where = array('id'=>$res_order['user_id']);
                $m_user = new \Common\Model\Smallapp\UserModel();
                $user_info = $m_user->getOne('*',$where,'');
                $res_data['nickName'] = $user_info['nickName'];
                $res_data['avatarUrl'] = $user_info['avatarUrl'];
                break;
        }
        $this->to_back($res_data);
    }

    public function grabBonusResult(){
        $order_id = $this->params['order_id'];
        $user_id = $this->params['user_id'];
        $sign = $this->params['sign'];
        $now_sign = create_sign($order_id.$user_id);
        if($sign!=$now_sign){
            $this->to_back(1007);
        }

        $red_packet_key = C('SAPP_REDPACKET');
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $key_hasget = $red_packet_key.$order_id.':hasget';//已经抢到红包的用户列表
        $res_hasget = $redis->get($key_hasget);
        $get_money = '';
        $status = 0;
        if($res_hasget){
            $hasget_users = json_decode($res_hasget,true);
            if(array_key_exists($user_id,$hasget_users)){
                $status = 1;//已领取红包
                $get_money = $hasget_users[$user_id];
            }
        }else{
            $res_hasget = array();
        }

        $m_order = new \Common\Model\Smallapp\RedpacketModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));

        if($status!=1){
            $key_bonus = $red_packet_key.$order_id.':bonus';//红包列表
            $res_redpacket = $redis->get($key_bonus);
            $resdata = json_decode($res_redpacket,true);
            if(empty($resdata['unused'])){
                $status = 2;//红包已领完,未领到
            }else{
                $grab_num = $res_order['amount']*2;
                $key_getbonus = $red_packet_key.$order_id.':getbonus';//领红包用户队列

                $unused_bonus = $resdata['unused'];
                $used_bonus = $resdata['used'];

                $m_netty = new \Common\Model\NettyModel();
                $m_user = new \Common\Model\Smallapp\UserModel();
                $m_redpacketreceive = new \Common\Model\Smallapp\RedpacketReceiveModel();
                $all_barrage = C('BARRAGES');
                for ($i=0;$i<$grab_num;$i++){
                    $grab_user_id = $redis->lpop($key_getbonus);
                    $now_money = $unused_bonus[$i];
                    if(empty($now_money)){
                        break;
                    }
                    unset($unused_bonus[$i]);
                    $used_bonus[] = $now_money;

                    $all_bonus = array('unused'=>$unused_bonus,'used'=>$used_bonus);
                    $redis->set($key_bonus,json_encode($all_bonus));

                    $res_hasget[$grab_user_id] = $now_money;
                    $redis->set($key_hasget,json_encode($res_hasget));

                    shuffle($all_barrage);
                    $barrage = $all_barrage[0];

                    //红包记录进入数据库
                    $get_data = array('redpacket_id'=>$order_id,'user_id'=>$grab_user_id,'money'=>$now_money,
                        'barrage'=>$barrage);
                    $m_redpacketreceive->addData($get_data);
                    //end

                    //增加电视弹幕推送
                    $user_info = $m_user->getOne('*',array('id'=>$grab_user_id),'');
                    $message = array('action'=>122,'nickName'=>$user_info['nickName'],
                        'avatarUrl'=>$user_info['avatarUrl'],'barrage'=>$barrage);
                    $m_netty->pushBox($res_order['mac'],json_encode($message));
                    //end

                    if($grab_user_id == $user_id){
                        $status = 1;
                        $get_money = $now_money;
                    }
                }
                if($status!=1){
                    $status = 2;
                }
            }
        }
        $res_data = array('order_id'=>$order_id,'user_id'=>$user_id,'status'=>$status);

        $where = array('id'=>$res_order['user_id']);
        $m_user = new \Common\Model\Smallapp\UserModel();
        $user_info = $m_user->getOne('*',$where,'');
        $res_data['nickName'] = $user_info['nickName'];
        $res_data['avatarUrl'] = $user_info['avatarUrl'];

        switch ($status){
            case 1:
                $all_bless = C('SMALLAPP_REDPACKET_BLESS');
                $res_data['bless'] = $all_bless[$res_order['bless_id']];
                $res_data['money'] = $get_money;
                break;
            case 2:
                $res_data['bless'] = '手慢了，红包抢完了';
                $res_data['money'] = 0;
                break;
        }
        $this->to_back($res_data);
    }


    public function sendList(){
        $openid = $this->params['openid'];
        $page   = $this->params['page'];
        
        $pagesize = 15;
        $all_nums = $page * $pagesize;
        $limit = "limit 0,$all_nums";
        $order = 'a.add_time desc';
        $fields = "a.id order_id,a.bless_id,a.pay_fee,a.pay_time";
        $where= array();
        $where['user.openid'] = $openid;
        $where['a.status'] = array('in','4,5,6');
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $data = $m_redpacket->getList($fields,$where,$order,$limit);
        $redpacket_bless = C('SMALLAPP_REDPACKET_BLESS');
        foreach($data as $key=>$v){
            $data[$key]['bless'] = $redpacket_bless[$v['bless_id']];
        }
        $this->to_back($data);
    }
    public function redpacketDetail(){
        //$openid   = $this->params['openid'];
        $order_id = $this->params['order_id']; 
        $page     = $this->params['page'];
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $fields = 'user.avatarUrl,user.nickName,a.amount,a.pay_fee,a.status,a.mac';
        
        $where = array();
        $where['a.id'] = $order_id;
        
        $info = $m_redpacket->getInfo($fields,$where);
        
        if(!in_array($info['status'], array(4,5,6))){
            $this->to_back(90130);
        }
        //领取详情
        $m_redpacket_receive = new \Common\Model\Smallapp\RedpacketReceiveModel();
        
        $pagesize = 15;
        $all_nums = $page* $pagesize;
        $fields = 'user.avatarUrl,user.nickName,a.add_time,a.money';
        $order  = 'a.receive_time desc';
        $limit  = "limit 0,$all_nums";
        $where = array();
        $where['a.redpacket_id'] = $order_id;
        $receive_list = $m_redpacket_receive->getList($fields, $where, $order, $limit);
        $where = array();
        $where['redpacket_id'] = $order_id;
        $receive_nums = $m_redpacket_receive->where($where)->count(); //领取个数
        
        if($receive_nums>0){
            $rt = $m_redpacket_receive->field('sum(`money`) as receive_money')->find();
            $info['receive_nums'] = $receive_nums;
            $info['receive_money']= $rt['receive_money'];
        }else {
            $info['receive_nums'] = 0;
            $info['receive_money']= 0;
        }
        $data = array();
        $data['info'] = $info;
        $data['receive_list'] = $receive_list;
        $this->to_back($data);
    }

}
