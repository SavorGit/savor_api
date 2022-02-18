<?php
namespace Smallapp46\Controller;
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
                $this->valid_fields = array('open_id'=>1001,'order_id'=>1001,'box_mac'=>1002);
                break;
            case 'grabBonus':
                $this->is_verify =1;
                $this->valid_fields = array('order_id'=>1001,'status'=>1001,'user_id'=>1001,'sign'=>1001,'option_id'=>1002);
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
        }else if($type==2 || $type==4){//发红包配置 type4 只发当前包间红包
            $bless_tmp = $range_tmp = array();
            $cf_bless_arr = C('SMALLAPP_REDPACKET_BLESS');
            $cf_range_arr = C('SMALLAPP_REDPACKET_SEND_RANGE');
            foreach($cf_bless_arr as $v){
                $bless_tmp[] = $v;
            }
            $range_list = array();
            foreach($cf_range_arr as $k=>$v){
                $rinfo = array('id'=>$k,'name'=>$v);
                if($type==4){
                    if($k==3){
                        $range_list[] = $rinfo;
                    }
                }else{
                    $range_list[] = $rinfo;
                }
            }

            if($type==4){
                unset($cf_range_arr[1],$cf_range_arr[2]);
            }
            foreach($cf_range_arr as $v){
                $range_tmp[] = $v;
            }
            $data['bless'] = $bless_tmp;
            $data['range'] = $range_tmp;
            $data['range_list'] = $range_list;
        }else if($type==3){
            list($t1, $t2) = explode(' ', microtime());
            $data['systemtime'] = (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
            $data['diff_time'] = 90000;
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
        if($total_money>500){
            $this->to_back(90123);
        }
        $m_config = new \Common\Model\SysConfigModel();
        $all_config = $m_config->getAllconfig();
        $red_packet_rate = $all_config['red_packet_rate'];
        $rate_fee = sprintf("%01.2f",$total_money*$red_packet_rate);
        $tmp_money = $total_money - $rate_fee;
        $redpacket_money = sprintf("%01.2f",$tmp_money);
        if($redpacket_money<$amount*0.3){
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

        $pk_type = C('PK_TYPE');//1走线上原来逻辑 2走新的支付方式
        if($pk_type==2){
            $trade_info = array('trade_no'=>$order_id,'total_fee'=>$total_money,'trade_name'=>'小热点红包',
                'buy_time'=>date('Y-m-d H:i:s'),'wx_openid'=>$open_id,'redirect_url'=>'','attach'=>20);
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
            $result['payinfo'] = $payinfo;
        }
        $result['pk_type'] = $pk_type;

        $this->to_back($result);
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
        if(empty($res_order)){
            $this->to_back(90122);
        }
        $status = $res_order['status'];
        $jump_url = http_host().'/h5/scanqrcode/getresult?oid='.$order_id;
        $data = array('status'=>$status,'jump_url'=>$jump_url);
        $this->to_back($data);
    }

    //获取扫电视红包码结果
    public function getScanresult(){
        $open_id = $this->params['open_id'];
        $order_id = $this->params['order_id'];
        $box_mac = $this->params['box_mac'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$open_id,'status'=>1);
        $user_info = $m_user->getOne('id,openid,wx_mpopenid as mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $user_id = $user_info['id'];
        $m_order = new \Common\Model\Smallapp\RedpacketModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));

        if(empty($res_order)){
            $this->to_back(90122);
        }
        $now_time = time();
        $remain_time = $now_time - strtotime($res_order['add_time']);
        if(!in_array($res_order['status'],array(4,5,6)) || $remain_time>86400){
            $this->to_back(90130);
        }

        $red_packet_key = C('SAPP_REDPACKET');
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);

        $key_box = $red_packet_key.$order_id.':boxs';
        $resbox = $redis->get($key_box);
        if(!empty($resbox)){
            $res_boxdata= json_decode($resbox,true);
        }else{
            $res_boxdata = array();
        }
        if($box_mac){
            $res_boxdata[$user_id] = $box_mac;
        }
        if(!empty($res_boxdata)){
            $redis->set($key_box,json_encode($res_boxdata),86400);
        }
        $status = 0;
        if($res_order['status'] == 5){
            $key_hasget = $red_packet_key.$order_id.':hasget';//已经抢到红包的用户列表
            $res_hasget = $redis->get($key_hasget);
            $get_money = '';
            $status = 2;//红包已领完,未领到
            if($res_hasget){
                $hasget_users = json_decode($res_hasget,true);
                if(array_key_exists($user_id,$hasget_users)){
                    $status = 5;//本人已领到
                }else{
                    $status = 2;//红包已领完,未领到
                }
            }
        }elseif(empty($user_info['mpopenid'])){
            $key_bonus = $red_packet_key.$order_id.':bonus';//红包列表
            $res_redpacket = $redis->get($key_bonus);
            $resdata = json_decode($res_redpacket,true);
            $key_bonusqueue = $red_packet_key.$order_id.':bonusqueue';//红包队列列表
            $res_redpacketqueue = $redis->lgetrange($key_bonusqueue,0,2000);

            $key_grabbonus = $red_packet_key.$order_id.':grabbonus';//抢红包用户队列
            $res_grabbonus = $redis->lgetrange($key_grabbonus,0,2000);
            if(empty($res_redpacketqueue) || count($res_grabbonus)>=$res_order['amount']*2){
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
                $key_bonusqueue = $red_packet_key.$order_id.':bonusqueue';//红包队列列表
                $res_redpacketqueue = $redis->lgetrange($key_bonusqueue,0,2000);

                //红包黑名单
                $key_invaliduser = $red_packet_key.'invaliduser';
                $res_invaliduser = $redis->get($key_invaliduser);
                if(!empty($res_invaliduser)){
                    $invaliduser = json_decode($res_invaliduser,true);
                }else{
                    $invaliduser = array();
                    $m_invalidlist = new \Common\Model\Smallapp\ForscreenInvalidlistModel();
                    $res_invalids = $m_invalidlist->getDataList('invalidid',array('type'=>4),'id desc');
                    if(!empty($res_invalids)) {
                        foreach ($res_invalids as $iv) {
                            $invaliduser[] = $iv['invalidid'];
                        }
                        $redis->set($key_invaliduser,json_encode($invaliduser),86400);
                    }
                }
                $is_finish = 0;
                if(!empty($invaliduser) && in_array($open_id,$invaliduser)){
                    $key_invaliduserdate = $red_packet_key.'invaliduser'.date('Ymd');
                    $res_invaliduserdate = $redis->get($key_invaliduserdate);
                    if(!empty($res_invaliduserdate)){
                        $invaliduserdate = json_decode($res_invaliduserdate,true);
                        $getnum = C('REDPACKET_GETNUM');
                        if(array_key_exists($open_id,$invaliduserdate) && $invaliduserdate[$open_id]>=$getnum){
                            $is_finish = 1;
                        }
                    }
                }

                if(empty($res_redpacketqueue) || $is_finish){
                    $status = 2;//红包已领完,未领到
                }else{
                    $key_getbonus = $red_packet_key.$order_id.':getbonus';//领红包用户队列
                    $res_getbonus = $redis->lgetrange($key_getbonus,0,2000);
                    if(in_array($user_id,$res_getbonus)){
                        $status = 3;//正在领红包
                    }else{
                        $key_grabbonus = $red_packet_key.$order_id.':grabbonus';//抢红包用户队列
                        $res_grabbonus = $redis->lgetrange($key_grabbonus,0,2000);
                        if(empty($res_grabbonus)){
                            $redis->rpush($key_grabbonus,$user_id);
                            $status = 4;//进入抢红包队列,同时生成token
                        }else{
                            if(count($res_grabbonus)>=$res_order['amount']*2){
                                $status = 2;//红包已领完,未领到
                            }else{
                                if(!in_array($user_id,$res_grabbonus)){
                                    $redis->rpush($key_grabbonus,$user_id);
                                }
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
                $all_bless = C('SMALLAPP_REDPACKET_BLESS');
                $res_data['bless'] = $all_bless[$res_order['bless_id']];
                $res_data['message'] = '手慢了，红包抢完了';
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

        //连接当前版位
        $code = rand(100, 999);
        $cache_key = C('SMALLAPP_CHECK_CODE');
        $cache_key .= $box_mac.':'.$open_id;
        $info = $redis->get($cache_key);
        if(empty($info)){
            $info = array();
            $info['is_have'] = 1;
            $info['code'] = $code;
            $redis->set($cache_key, json_encode($info),7200);

            $key = C('SMALLAPP_CHECK_CODE')."*".$open_id;
            $keys = $redis->keys($key);
            foreach($keys as $v){
                $key_arr = explode(':', $v);
                if($key_arr[2]!=$box_mac){
                    $redis->remove($v);
                }
            }
        }else{
            $key = C('SMALLAPP_CHECK_CODE')."*".$open_id;
            $keys = $redis->keys($key);
            foreach($keys as $v){
                $key_arr = explode(':', $v);
                if($key_arr[2]!=$box_mac){
                    $redis->remove($v);
                }
            }
        }
        //end
        $op_type = 1;
        $op_uid = C('REDPACKET_OPERATIONERID');
        $op_info = C('BONUS_OPERATION_INFO');
        if($res_order['user_id']==$op_uid){
            $res_data['bless'] = '';
            if($res_order['operate_type']==2){
                $op_type = 3;
                $res_data['nickName'] = $op_info['nickName'];
                $res_data['avatarUrl'] = $op_info['avatarUrl'];
            }
            /*
            $op_type = 2;
            $m_box = new \Common\Model\BoxModel();
            $fileds = 'd.id as hotel_id';
            $where = array('a.mac'=>$res_order['mac'],'a.state'=>1,'a.flag'=>0,'d.state'=>1,'d.flag'=>0);
            $res_box = $m_box->getBoxInfo($fileds,$where);
            $hotel_id = $res_box[0]['hotel_id'];
            $rd_test_hotels = C('RD_TEST_HOTEL');
            if(isset($rd_test_hotels[$hotel_id])){
                $res_data['nickName'] = $rd_test_hotels[$hotel_id]['short_name'];
                $m_hotelext = new \Common\Model\HotelExtModel();
                $res_hotel_ext = $m_hotelext->getOnerow(array('hotel_id'=>$hotel_id));
                $m_media = new \Common\Model\MediaModel();
                $res_media = $m_media->getMediaInfoById($res_hotel_ext['hotel_cover_media_id']);
                $res_data['avatarUrl'] = $res_media['oss_addr'];
            }
            */
        }
        $res_data['op_type'] = $op_type;
        $this->to_back($res_data);
    }

    public function grabBonus(){
        $order_id = $this->params['order_id'];
        $status = $this->params['status'];
        $user_id = $this->params['user_id'];
        $option_id = $this->params['option_id'];
        $sign = $this->params['sign'];
        $now_sign = create_sign($status.$order_id.$user_id);
        if($sign!=$now_sign){
            $this->to_back(90121);
        }

        $m_order = new \Common\Model\Smallapp\RedpacketModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));

        if(empty($res_order)){
            $this->to_back(90122);
        }
        $now_time = time();
        $remain_time = $now_time - strtotime($res_order['add_time']);
        if(!in_array($res_order['status'],array(4,6)) || $remain_time>86400){
            $this->to_back(90130);
        }

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
            $key_bonusqueue = $red_packet_key.$order_id.':bonusqueue';//红包队列列表
            $res_redpacketqueue = $redis->lgetrange($key_bonusqueue,0,2000);
            if(empty($res_redpacketqueue)){
                $status = 2;//红包已领完,未领到
            }else{
                $key_getbonus = $red_packet_key.$order_id.':getbonus';//领红包用户队列
                $res_getbonus = $redis->lgetrange($key_getbonus,0,2000);
                if(in_array($user_id,$res_getbonus)){
                    $status = 3;//正在领红包
                }else{
                    $key_grabbonus = $red_packet_key.$order_id.':grabbonus';//抢红包用户队列
                    $res_grabbonus = $redis->lgetrange($key_grabbonus,0,2000);
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

        if(!empty($option_id)){
            $all_question = C('BONUS_QUESTIONNAIRE');
            $wine_name = $all_question[$option_id]['name'];
            if(!empty($wine_name)){
                $m_question = new \Common\Model\Smallapp\QuestionnaireModel();
                $add_data = array('user_id'=>$user_id,'redpacket_id'=>$order_id,'wine_name'=>$wine_name);
                $m_question->add($add_data);
            }
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

        $m_order = new \Common\Model\Smallapp\RedpacketModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            $this->to_back(90122);
        }
        $now_time = time();
        $remain_time = $now_time - strtotime($res_order['add_time']);
        if(!in_array($res_order['status'],array(4,5,6)) || $remain_time>86400){
            $this->to_back(90130);
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
            }else{
                if($res_order['status']==5){
                    $status = 2;
                }
            }
        }else{
            $hasget_users = array();
            if($res_order['status']==5){
                $status = 2;
            }
        }

        if($status==0){
            $key_bonus = $red_packet_key.$order_id.':bonus';//红包列表
            $res_redpacket = $redis->get($key_bonus);
            $resdata = json_decode($res_redpacket,true);
            $key_bonusqueue = $red_packet_key.$order_id.':bonusqueue';//红包队列列表
            $res_redpacketqueue = $redis->lgetrange($key_bonusqueue,0,2000);
            if(empty($res_redpacketqueue)){
                $status = 2;//红包已领完,未领到
            }else{
                $grab_num = $res_order['amount'];
                $key_getbonus = $red_packet_key.$order_id.':getbonus';//领红包用户队列
                $unused_bonus = $resdata['unused'];
                $used_bonus = $resdata['used'];

                $m_netty = new \Common\Model\NettyModel();
                $m_user = new \Common\Model\Smallapp\UserModel();
                $m_redpacketreceive = new \Common\Model\Smallapp\RedpacketReceiveModel();

                $bless_id = $res_order['bless_id'];
                if(in_array($bless_id,array(6,7,8))){
                    $all_barrage = C('SMALLAPP_TYPE_BARRAGES');
                    $all_barrage = $all_barrage[$bless_id];
                }else{
                    $all_barrage = C('SMALLAPP_BARRAGES');
                }
                $user_barrages = array();
                $getnum = C('REDPACKET_GETNUM');
                for ($i=0;$i<$grab_num;$i++){
                    $grab_user_id = $redis->lpop($key_getbonus);
                    if(empty($grab_user_id)){
                        break;
                    }
                    $now_money = $redis->lpop($key_bonusqueue);;
//                    $now_money = $unused_bonus[$i];
                    if(empty($now_money)){
                        break;
                    }
                    $unused_key = array_search($now_money, $unused_bonus);
                    if($unused_key!==false){
                        unset($unused_bonus[$unused_key]);
                    }
                    $used_bonus[] = $now_money;
                    $all_bonus = array('unused'=>$unused_bonus,'used'=>$used_bonus);
                    $redis->set($key_bonus,json_encode($all_bonus),86400);

                    $hasget_users[$grab_user_id] = $now_money;
                    $redis->set($key_hasget,json_encode($hasget_users),86400);

                    //红包记录进入数据库
                    shuffle($all_barrage);
                    $barrage = $all_barrage[0];
                    $get_data = array('redpacket_id'=>$order_id,'user_id'=>$grab_user_id,'money'=>$now_money,
                        'barrage'=>$barrage);
                    $receive_id = $m_redpacketreceive->addData($get_data);
                    //end

                    //发现金红包 推送消息到订阅
                    $message_oid = $order_id.'_'.$receive_id;
                    sendTopicMessage($message_oid,20);
                    //end

                    //增加电视弹幕推送
                    $user_info = $m_user->getOne('*',array('id'=>$grab_user_id),'');
                    $head_pic = '';
                    if(!empty($user_info['avatarUrl'])){
                        $head_pic = base64_encode($user_info['avatarUrl']);
                    }
                    $user_barrages[] = array('nickName'=>$user_info['nickName'],'headPic'=>$head_pic,'avatarUrl'=>$user_info['avatarUrl'],'barrage'=>$barrage);
                    //end

                    //红包黑名单
                    $key_invaliduser = $red_packet_key.'invaliduser';
                    $res_invaliduser = $redis->get($key_invaliduser);
                    if(!empty($res_invaliduser)) {
                        $invaliduser = json_decode($res_invaliduser, true);
                        if(!empty($invaliduser) && in_array($user_info['openid'],$invaliduser)){
                            $key_invaliduserdate = $red_packet_key.'invaliduser'.date('Ymd');
                            $res_invaliduserdate = $redis->get($key_invaliduserdate);
                            if(!empty($res_invaliduserdate)){
                                $invaliduserdate = json_decode($res_invaliduserdate,true);
                            }else{
                                $invaliduserdate = array();
                            }
                            if(isset($invaliduserdate[$user_info['openid']])){
                                $invaliduserdate[$user_info['openid']] = $invaliduserdate[$user_info['openid']]+1;
                            }else{
                                $invaliduserdate[$user_info['openid']] = 1;
                            }
                            $redis->set($key_invaliduserdate,json_encode($invaliduserdate),86400);
                        }
                    }

                    if($grab_user_id == $user_id){
                        $status = 1;
                        $get_money = $now_money;
                    }
                }
                if(!empty($user_barrages) && $res_order['operate_type']!=2){
                    $m_box = new \Common\Model\BoxModel();
                    $bwhere = array('a.mac'=>$res_order['mac'],'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
                    $res_box = $m_box->getBoxInfo('a.is_4g',$bwhere);
                    if(!empty($res_box) && $res_box[0]['is_4g']==1){
                        foreach ($user_barrages as $k=>$v){
                            $user_barrages[$k]['avatarUrl'] = 'http://oss.littlehotspot.com/WeChat/MiniProgram/LaunchScreen/source/images/imgs/default_user_head.png';
                        }
                    }
                    $message = array('action'=>122,'userBarrages'=>$user_barrages);
                    $m_netty->pushBox($res_order['mac'],json_encode($message));
                }
                $res_redpacketqueue = $redis->lgetrange($key_bonusqueue,0,2000);
                if(empty($res_redpacketqueue)){
                    $data = array('status'=>5);
                    if(empty($res_order['grab_time'])){
                        $data['grab_time'] = date('Y-m-d H:i:s');
                    }
                    $m_order->updateData(array('id'=>$order_id),$data);
                }else{
                    $data = array('status'=>6);
                    $m_order->updateData(array('id'=>$order_id),$data);
                }
                if($status!=1){
                    $status = 2;
                }
            }
        }
        $mac = $res_order['mac'];
        $key_box = $red_packet_key.$order_id.':boxs';
        $resbox = $redis->get($key_box);
        $box_mac = '';
        if(!empty($resbox)){
            $res_boxdata= json_decode($resbox,true);
            if(isset($res_boxdata[$user_id])){
                $box_mac = $res_boxdata[$user_id];
            }
        }
        $res_data = array('order_id'=>$order_id,'user_id'=>$user_id,'status'=>$status,'box_mac'=>$box_mac,'mac'=>$mac);

        $where = array('id'=>$res_order['user_id']);
        $m_user = new \Common\Model\Smallapp\UserModel();
        $user_info = $m_user->getOne('*',$where,'');
        $res_data['nickName'] = $user_info['nickName'];
        $res_data['avatarUrl'] = $user_info['avatarUrl'];

        $all_bless = C('SMALLAPP_REDPACKET_BLESS');
        $res_data['bless'] = $all_bless[$res_order['bless_id']];
        switch ($status){
            case 1:
                $res_data['money'] = $get_money;
                break;
            case 2:
                $res_data['money'] = 0;
                break;
        }
        $op_type = 1;
        $op_uid = C('REDPACKET_OPERATIONERID');
        $op_info = C('BONUS_OPERATION_INFO');
        if($res_order['user_id']==$op_uid){
            $res_data['bless'] = '';
            if($res_order['operate_type']==2){
                $op_type = 3;
                $res_data['nickName'] = $op_info['nickName'];
                $res_data['avatarUrl'] = $op_info['avatarUrl'];
            }
            /*
            $op_type = 2;
            $m_box = new \Common\Model\BoxModel();
            $fileds = 'd.id as hotel_id';
            $where = array('a.mac'=>$res_order['mac'],'a.state'=>1,'a.flag'=>0,'d.state'=>1,'d.flag'=>0);
            $res_box = $m_box->getBoxInfo($fileds,$where);
            $hotel_id = $res_box[0]['hotel_id'];
            $rd_test_hotels = C('RD_TEST_HOTEL');
            if(isset($rd_test_hotels[$hotel_id])){
                $res_data['nickName'] = $rd_test_hotels[$hotel_id]['short_name'];
                $m_hotelext = new \Common\Model\HotelExtModel();
                $res_hotel_ext = $m_hotelext->getOnerow(array('hotel_id'=>$hotel_id));
                $m_media = new \Common\Model\MediaModel();
                $res_media = $m_media->getMediaInfoById($res_hotel_ext['hotel_cover_media_id']);
                $res_data['avatarUrl'] = $res_media['oss_addr'];
            }
            */
        }
        $res_data['op_type'] = $op_type;
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
        $openid   = $this->params['openid'];
        $order_id = $this->params['order_id']; 
        $page     = $this->params['page'];
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $fields = 'user.avatarUrl,user.nickName,a.amount,a.pay_fee,a.status,a.mac,a.pay_time,a.grab_time,a.mac';
        
        $where = array();
        $where['a.id'] = $order_id;
        
        $info = $m_redpacket->getOrderAndUserInfo($fields,$where);
        
        if(!in_array($info['status'], array(4,5,6,7))){
            $this->to_back(90130);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }

        if($info['status']==5){
            $info['diff_time'] = changeTimeType(strtotime($info['grab_time']) - strtotime($info['pay_time']));
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
        $where['a.status'] = 1;
        $receive_list = $m_redpacket_receive->getList($fields, $where, $order, $limit);
        $where = array();
        $where['redpacket_id'] = $order_id;
        $where['status'] = 1;
        $receive_nums = $m_redpacket_receive->where($where)->count(); //领取个数
        
        if($receive_nums>0){
            $rt = $m_redpacket_receive->where($where)->field('sum(`money`) as receive_money')->find();
            $info['receive_nums'] = $receive_nums;
            $info['receive_money']= $rt['receive_money'];
        }else {
            $info['receive_nums'] = 0;
            $info['receive_money']= 0;
        }
        $red_packet_key = C('SAPP_REDPACKET');
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $key_box = $red_packet_key.$order_id.':boxs';
        $resbox = $redis->get($key_box);
        $box_mac = '';
        if(!empty($resbox)){
            $res_boxdata= json_decode($resbox,true);
            if(isset($res_boxdata[$user_info['id']])){
                $box_mac = $res_boxdata[$user_info['id']];
            }
        }
        $info['box_mac'] = $box_mac;
        $data = array();
        $data['info'] = $info;
        $data['receive_list'] = $receive_list;
        $this->to_back($data);
    }

}
