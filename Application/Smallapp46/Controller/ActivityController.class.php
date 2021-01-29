<?php
namespace Smallapp46\Controller;
use Common\Lib\Smallapp_api;
use Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;

class ActivityController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getGameCode':
                $this->is_verify = 1;
                $this->valid_fields = array('scene'=>'1001');
                break;
            case 'orgGameLog':   //发起游戏
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001,'box_mac'=>1001,
                    'openid'=>1001,'mobile_brand'=>1001,
                    'mobile_model'=>1001
                );
                break;
            case 'joinGameLog': //加入游戏
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001,'openid'=>1001,
                    'mobile_brand'=>1001,'mobile_model'=>1001
                );
                break;
            case 'startGameLog':
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001);
                break;
            case 'wantGameLog':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'openid'=>1001,
                    'mobile_brand'=>1001,'mobile_model'=>1001);
                break;
            case 'jugeGamePerson': //判断当前游戏是否有人加入
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001);
                break;
            case 'canJoinGame':
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001);
                break;
            case 'lottery':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'activity_id'=>1002);
                break;
            case 'joinLottery':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'activity_id'=>1001);
                break;
            case 'getConfigLotteryStatus':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001);
                break;
            case 'addLottery':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'prize'=>1001,'image'=>1001);
                break;
            case 'openLottery':
                $this->valid_fields = array('openid'=>1001,'activity_id'=>1002);
                break;
        }
        parent::_init_();
    }

    public function getGameCode(){
        $scene = $this->params['scene'];
        $r = $this->params['r'] !='' ? $this->params['r'] : 255;
        $g = $this->params['g'] !='' ? $this->params['g'] : 255;
        $b = $this->params['b'] !='' ? $this->params['b'] : 255;
        $m_small_app = new Smallapp_api();
        $tokens  = $m_small_app->getWxAccessToken();
        header('content-type:image/png');
        $data = array();
        $data['scene'] = $scene;//自定义信息，可以填写诸如识别用户身份的字段，注意用中文时的情况
        $data['page'] = "pages/activity/turntable/joingame";//扫描后对应的path
        $data['width'] = "280";//自定义的尺寸
        $data['auto_color'] = false;//是否自定义颜色
        $color = array(
            "r"=>$r,
            "g"=>$g,
            "b"=>$b,
        );
        $data['line_color'] = $color;//自定义的颜色值
        $data['is_hyaline'] = true;
        $data = json_encode($data);
        $m_small_app->getSmallappCode($tokens,$data);
    }

    /**
     * @desc 记录发起游戏日志
     *
     */
    public function orgGameLog(){
        $activity_id = $this->params['activity_id'];
        $box_mac     = $this->params['box_mac'];
        $openid      = $this->params['openid'];
        $mobile_brand= $this->params['mobile_brand'];
        $mobile_model= $this->params['mobile_model'];
        $orggame_time= $this->params['activity_id'];
        $data = array();
        $m_turntable_log = new \Common\Model\Smallapp\TurntableLogModel();
        $data['activity_id'] = $activity_id;
        $data['box_mac']     = $box_mac;
        $data['openid']      = $openid;
        $data['mobile_brand']= $mobile_brand;
        $data['mobile_model']= $mobile_model;
        $data['orggame_time']= getMillisecond();
        //$data['join_num']    = 1;
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['is_start']    = 0;
        $m_turntable_log->addInfo($data);
        $this->to_back(10000);
    }

    /**
     * @dfesc 是否可以加入游戏
     */
    public function canJoinGame(){
        $activity_id = $this->params['activity_id'];
        $m_turntable_log = new \Common\Model\Smallapp\TurntableLogModel();
        $where = $data =  array();
        $where['activity_id'] = $activity_id;

        $activity_info = $m_turntable_log->getOne('is_start,create_time',$where);
        if($activity_info['is_start'] ==1){ //如果已经开始游戏 不能加入
            $data['can_join'] = 0;
            $this->to_back($data);
        }else {//没有开始
            $juge_time = time() - 120;
            $m_turntable_detail = new \Common\Model\Smallapp\TurntableDetailModel();
            $nums = $m_turntable_detail->countWhere($where);
            if(empty($nums)){//暂无人员加入
                //创建时间是否在两分钟前
                $create_time = strtotime($activity_info['create_time']);
                if($juge_time>=$create_time){//创建时间在两分钟之前
                    $data['can_join'] = 0;
                    $this->to_back($data);
                }else {//创建时间在两分钟之内
                    $data['can_join'] = 1;
                    $this->to_back($data);
                }
            }else {//有人员加入

                //判断最后一个加入的人是否在两分钟之内
                $order = "create_time desc";
                $activity_detail = $m_turntable_detail->getOne('create_time',$where,$order);
                $join_time = strtotime($activity_detail['create_time']);
                if($juge_time>=$join_time){
                    $data['can_join'] = 0;
                    $this->to_back($data);
                }else {
                    $data['can_join'] = 1;
                    $this->to_back($data);
                }
            }
        }
    }

    /**
     * @desc 记录加入游戏日志
     */
    public function joinGameLog(){
        $activity_id = $this->params['activity_id'];
        $openid      = $this->params['openid'];
        $mobile_brand= $this->params['mobile_brand'];
        $mobile_model= $this->params['mobile_model'];
        $join_time   = $this->params['join_time'] ? $this->params['join_time'] :0;
        /* $m_turntable_log = new \Common\Model\Smallapp\TurntableLogModel();
        $ret = $m_turntable_log->update_join_info($activity_id); */
        $data = array();
        $data['activity_id'] = $activity_id;
        $data['openid']      = $openid;
        $data['mobile_brand']= $mobile_brand;
        $data['mobile_model']= $mobile_model;
        $data['join_time']   = getMillisecond();
        $m_turntable_detail = new \Common\Model\Smallapp\TurntableDetailModel();
        $m_turntable_detail->addInfo($data,1);
        $this->to_back(10000);
    }

    /**
     * @desc 记录开始游戏
     */
    public function startGameLog(){
        $activity_id = $this->params['activity_id'];
        $startgame_time = $this->params['startgame_time'] ? $this->params['startgame_time'] :0;
        $m_turntable_log = new \Common\Model\Smallapp\TurntableLogModel();
        $where = $data = array();
        $where['activity_id']   = $activity_id;
        $data['startgame_time'] = getMillisecond();
        $data['is_start']       = 1;
        $data['update_time']    = date('Y-m-d H:i:s');
        $data['play_times']     = 1;
        $m_turntable_log->updateInfo($where, $data);
        $this->to_back(10000);
    }

    /**
     * @desc 重玩游戏
     */
    public function retryGame(){
        $activity_id = $this->params['activity_id'];
        $m_turntable_log = new \Common\Model\Smallapp\TurntableLogModel();
        $ret = $m_turntable_log->where('activity_id='.$activity_id)->setInc('play_times');
        $this->to_back(10000);
    }

    /**
     * @desc 记录想要玩游戏的用户信息
     */
    public function wantGameLog(){
        $box_mac = $this->params['box_mac'];
        $openid  = $this->params['openid'];
        $mobile_brand = $this->params['mobile_brand'];
        $mobile_model = $this->params['mobile_model'];

        $data = array();
        $data['action'] = 7;
        $data['box_mac'] = $box_mac;
        $data['openid']  = $openid;
        $data['mobile_brand'] = $mobile_brand;
        $data['mobile_model'] = $mobile_model;
        $data['create_time']  = date('Y-m-d H:i:s');

        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_WANT_GAME').":".$openid;

        $redis->rpush($cache_key, json_encode($data));
        $this->to_back(10000);
    }

    public function jugeGamePerson(){
        $activity_id = $this->params['activity_id'];
        $m_turntable_detail = new \Common\Model\Smallapp\TurntableDetailModel();
        $where['activity_id'] = $activity_id;
        $nums = $m_turntable_detail->countWhere($where);
        $data = array();
        $data['nums'] = $nums;
        $this->to_back($data);
    }

    public function getConfigLotteryStatus(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }

        $where = array('openid'=>$openid,'box_mac'=>$box_mac,'type'=>2);
        $start_time = date('Y-m-d 00:00:00');
        $end_time = date('Y-m-d 23:59:59');
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getDataList('*',$where,'id desc',0,1);
        $activity_id = 0;
        $lottery_status = 0;
        if($res_activity['total']>0){
            $res_activity = $res_activity['list'][0];
            $activity_id = intval($res_activity['id']);
            if($res_activity['status']==1){
                $lottery_status = 2;
            }elseif($res_activity['status']==3){
                $lottery_status = 3;
            }elseif($res_activity['status']==2){
                $lottery_status = 0;
            }
        }
        $res_data = array('activity_id'=>$activity_id,'lottery_status'=>$lottery_status);
        $this->to_back($res_data);
    }

    public function addLottery(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $prize = trim($this->params['prize']);
        $image_url = $this->params['image'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_box = new \Common\Model\BoxModel();
        $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);
        if(isset($forscreen_info['box_id'])){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(15);
            $cache_key = 'savor_box_'.$forscreen_info['box_id'];
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info,true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $hotel_id = $room_info['hotel_id'];
        }else{
            $where = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
            $rets = $m_box->getBoxInfo('d.id as hotel_id,',$where);
            $hotel_id = $rets[0]['hotel_id'];
        }
        $where = array('box_mac'=>$box_mac,'status'=>array('in',array(1,3)));
        $start_time = date('Y-m-d 00:00:00');
        $end_time = date('Y-m-d 23:59:59');
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getDataList('*',$where,'id desc',0,1);
        if($res_activity['total']>0){
            $this->to_back(90167);
        }

        $start_time = date('Y-m-d H:i:s');
        $data = array('hotel_id'=>$hotel_id,'box_mac'=>$box_mac,'openid'=>$openid,'prize'=>$prize,'image_url'=>$image_url,
            'start_time'=>$start_time,'status'=>0,'type'=>2);
        $res_id = $m_activity->add($data);
        if($res_id){
            $lottery_countdown = 60;
            $partakedish_img = $image_url.'?x-oss-process=image/resize,m_mfit,h_200,w_300';
            $img_info = pathinfo($image_url);

            $m_box = new \Common\Model\BoxModel();
            $condition = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
            $res_box = $m_box->getBoxByCondition('box.id as box_id',$condition);
            $host_name = C('HOST_NAME');
            $qrcode_url = $host_name."/smallapp46/qrcode/getBoxQrcode?box_mac={$box_mac}&type=35&data_id={$res_id}&box_id={$res_box['box_id']}";
            $netty_data = array('action'=>135,'countdown'=>$lottery_countdown,'lottery_time'=>'',
                'lottery_countdown'=>$lottery_countdown,'partake_img'=>$partakedish_img,'partake_filename'=>$img_info['basename'],
                'partake_name'=>$prize,'activity_name'=>'抽奖活动','codeUrl'=>$qrcode_url
            );
            $message = json_encode($netty_data);
            $m_netty = new \Common\Model\NettyModel();
            $res_push = $m_netty->pushBox($box_mac,$message);
            if($res_push['error_code']){
                $this->to_back($res_push['error_code']);
            }
            $updata = array('start_time'=>date('Y-m-d H:i:s'),'status'=>1);
            $m_activity->updateData(array('id'=>$res_id),$updata);
        }
        $resp_data = array('activity_id'=>$res_id);
        $this->to_back($resp_data);
    }

    public function openLottery(){
        $openid = $this->params['openid'];
        $activity_id = intval($this->params['activity_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id,'openid'=>$openid));
        if(empty($res_activity)){
            $this->to_back(90162);
        }
        if($res_activity['status']!=1){
            $this->to_back(90163);
        }
        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        $where = array('activity_id'=>$activity_id);
        $res_apply_user = $m_activityapply->getDataList('*',$where,'id asc');
        if(count($res_apply_user)<2){
            $this->to_back(90168);
        }
        if(empty($res_apply_user)){
            $updata = array('end_time'=>date('Y-m-d H:i:s'),'lottery_time'=>date('Y-m-d H:i:s'),'status'=>2);
            $m_activity->updateData(array('id'=>$activity_id),$updata);
            $res_data = array('activity_id'=>$activity_id,'lottery_status'=>4);
            $this->to_back($res_data);
        }

        $all_lottery_openid = array();
        foreach ($res_apply_user as $ak=>$av){
            $all_lottery_openid[]=$av['openid'];
        }
        if(!empty($res_apply_user)){
            $res_apply_user = array_values($res_apply_user);
            $user_num = count($res_apply_user) - 1;
            $lottery_rand = mt_rand(0,$user_num);
            $lottery_apply_id = $res_apply_user[$lottery_rand]['id'];
            $lottery_openid = $res_apply_user[$lottery_rand]['openid'];
        }else{
            $lottery_apply_id = 0;
            $lottery_openid = 0;
        }
        if($lottery_apply_id==0){
            $updata = array('end_time'=>date('Y-m-d H:i:s'),'lottery_time'=>date('Y-m-d H:i:s'),'status'=>2);
            $m_activity->updateData(array('id'=>$activity_id),$updata);
            $res_data = array('activity_id'=>$activity_id,'lottery_status'=>4);
            $this->to_back($res_data);
        }
        $lwhere = array('openid'=>array('in',$all_lottery_openid));
        $users = $m_user->getWhere('openid,avatarUrl,nickName',$lwhere,'id desc','','');
        $partake_user = array();
        foreach ($users as $uv){
            $is_lottery = 0;
            if($uv['openid']==$lottery_openid){
                $is_lottery = 1;
            }
            $partake_user[] = array('avatarUrl'=>base64_encode($uv['avatarUrl']),'nickName'=>$uv['nickName'],'is_lottery'=>$is_lottery);
        }
        $lottery = array('dish_name'=>$res_activity['prize'],'dish_image'=>$res_activity['image_url']);
        $netty_data = array('action'=>136,'partake_user'=>$partake_user,'lottery'=>$lottery);
        $message = json_encode($netty_data);
        $m_netty = new \Common\Model\NettyModel();
        $res_push = $m_netty->pushBox($res_activity['box_mac'],$message);
        if($res_push['error_code']){
            $this->to_back($res_push['error_code']);
        }
        $updata = array('lottery_time'=>date('Y-m-d H:i:s'),'status'=>3);
        $m_activity->updateData(array('id'=>$activity_id),$updata);

        $redis = new \Common\Lib\SavorRedis();
        $lkey = C('SMALLAPP_LOTTERY');
        $redis->select(1);
        $cache_key = $lkey.":result:$activity_id";
        $cdata = array('open_time'=>time(),'lottery_apply_id'=>$lottery_apply_id);
        $redis->set($cache_key,json_encode($cdata),86400);
        $res_data = array('activity_id'=>$activity_id,'lottery_status'=>3);
        $this->to_back($res_data);
    }

    public function getLotteryResult(){
        $openid = $this->params['openid'];
        $activity_id = intval($this->params['activity_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90157);
        }
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id,'openid'=>$openid));
        if(empty($res_activity)){
            $this->to_back(90162);
        }
        if($res_activity['status']!=3){
            $this->to_back(90164);
        }
        $redis = new \Common\Lib\SavorRedis();
        $lkey = C('SMALLAPP_LOTTERY');
        $redis->select(1);
        $cache_key = $lkey.":result:$activity_id";
        $res_cache = $redis->get($cache_key);
        $lottery_info = json_decode($res_cache,true);
        $lottery_apply_id = $lottery_info['lottery_apply_id'];
        $open_time = $lottery_info['open_time'];
        $now_time = time();
        $lottery_status = 3;
        if($now_time - $open_time>=20){
            $lottery_status = 4;
            $updata = array('end_time'=>date('Y-m-d H:i:s'),'status'=>2);
            $m_activity->updateData(array('id'=>$activity_id),$updata);
            $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
            $m_activityapply->updateData(array('id'=>$lottery_apply_id),array('status'=>2));
            $condition = array('activity_id'=>$activity_id,'id'=>array('neq',$lottery_apply_id));
            $m_activityapply->updateData($condition,array('status'=>3));
        }
        $res_data = array('activity_id'=>$activity_id,'lottery_status'=>$lottery_status);
        $this->to_back($res_data);
    }

    public function joinLottery(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $activity_id = intval($this->params['activity_id']);
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $where = array('id'=>$activity_id,'box_mac'=>$box_mac,'status'=>1);
        $res_activity = $m_activity->getInfo($where);
        if(empty($res_activity)){
            $this->to_back(90157);
        }

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }

        $redis = new \Common\Lib\SavorRedis();
        $lkey = C('SMALLAPP_LOTTERY');
        $cache_key = $lkey.":$activity_id:$openid";
        $redis->select(1);
        $status = 0;
        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        if($res_activity['status']==2 || $res_activity['status']==3){
            $where = array('openid'=>$openid,'activity_id'=>$activity_id);
            $res_apply = $m_activityapply->getDataList('*',$where,'id desc',0,1);
            if($res_apply['total'] && $res_apply['list'][0]['status']==2){
                $status = 3;
            }else{
                $status = 4;
            }
        }else{
            $res_cache = $redis->get($cache_key);
            if(!empty($res_cache)){
                $status = 1;
            }else{
                $where = array('openid'=>$openid,'activity_id'=>$activity_id);
                $res_apply = $m_activityapply->getDataList('*',$where,'id desc',0,1);
                if($res_apply['total']){
                    $status = 1;
                }
            }
            if($res_activity['end_time']!='0000-00-00 00:00:00'){
                $end_time = strtotime($res_activity['end_time']);
                $now_time = time();
                if($status==0 && $now_time>$end_time){
                    $status = 2;
                }
            }
            if($status==0){
                if($res_activity['end_time']=='0000-00-00 00:00:00'){
                    $adata = array('activity_id'=>$activity_id,'box_mac'=>$box_mac,'openid'=>$openid,'status'=>1);
                    $m_activityapply->add($adata);
                    $redis->set($cache_key,date('Y-m-d H:i:s'),10800);
                    $status = 1;
                }
            }

        }
        switch ($status){
            case 1:
                $tips = '参与游戏成功';
                break;
            case 2:
            case 3:
            case 4:
                $tips = '已过本轮抽奖时间，请等待下一轮抽奖';
                break;
            default:
                $tips = '';
        }
        $data = array('activity_id'=>$activity_id,'image_url'=>$res_activity['image_url'],'status'=>$status,'tips'=>$tips);
        $this->to_back($data);
    }

    public function againLottery(){
        $openid = $this->params['openid'];
        $activity_id = intval($this->params['activity_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90157);
        }
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id,'openid'=>$openid));
        if(empty($res_activity)){
            $this->to_back(90162);
        }
        if($res_activity['status']!=2){
            $this->to_back(90165);
        }
        $where = array('box_mac'=>$res_activity['box_mac'],'status'=>array('in',array(1,3)));
        $start_time = date('Y-m-d 00:00:00');
        $end_time = date('Y-m-d 23:59:59');
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_hasactivity = $m_activity->getDataList('*',$where,'id desc',0,1);
        if($res_hasactivity['total']>0){
            $this->to_back(90167);
        }
        $start_time = date('Y-m-d H:i:s');
        $data = array('hotel_id'=>$res_activity['hotel_id'],'box_mac'=>$res_activity['box_mac'],'openid'=>$openid,
            'prize'=>$res_activity['prize'],'image_url'=>$res_activity['image_url'],
            'start_time'=>$start_time,'status'=>0,'type'=>2);
        $res_id = $m_activity->add($data);
        if($res_id){
            $lottery_countdown = 60;
            $partakedish_img = $res_activity['image_url'].'?x-oss-process=image/resize,m_mfit,h_200,w_300';
            $img_info = pathinfo($res_activity['image_url']);
            $m_box = new \Common\Model\BoxModel();
            $condition = array('box.mac'=>$res_activity['box_mac'],'box.state'=>1,'box.flag'=>0);
            $res_box = $m_box->getBoxByCondition('box.id as box_id',$condition);
            $host_name = C('HOST_NAME');
            $qrcode_url = $host_name."/smallapp46/qrcode/getBoxQrcode?box_mac={$res_activity['box_mac']}&type=35&data_id={$res_id}&box_id={$res_box['box_id']}";
            $netty_data = array('action'=>135,'countdown'=>$lottery_countdown,'lottery_time'=>'',
                'lottery_countdown'=>$lottery_countdown,'partake_img'=>$partakedish_img,'partake_filename'=>$img_info['basename'],
                'partake_name'=>$res_activity['prize'],'activity_name'=>'抽奖活动','codeUrl'=>$qrcode_url
            );
            $message = json_encode($netty_data);
            $m_netty = new \Common\Model\NettyModel();
            $res_push = $m_netty->pushBox($res_activity['box_mac'],$message);
            if($res_push['error_code']){
                $this->to_back($res_push['error_code']);
            }
            $updata = array('start_time'=>date('Y-m-d H:i:s'),'status'=>1);
            $m_activity->updateData(array('id'=>$res_id),$updata);
        }
        $resp_data = array('activity_id'=>$res_id);
        $this->to_back($resp_data);
    }

    public function lottery(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $activity_id = intval($this->params['activity_id']);
        $m_box = new \Common\Model\BoxModel();
        $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);
        if(isset($forscreen_info['box_id'])){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(15);
            $cache_key = 'savor_box_'.$forscreen_info['box_id'];
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info,true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $hotel_id = $room_info['hotel_id'];
        }else{
            $where = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
            $rets = $m_box->getBoxInfo('d.id as hotel_id,',$where);
            $hotel_id = $rets[0]['hotel_id'];
        }

        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        if($activity_id>0){
            $res_activity = $m_activity->getInfo(array('id'=>$activity_id,'hotel_id'=>$hotel_id));
        }else{
            $where = array('hotel_id'=>$hotel_id,'status'=>1);
            $start_time = date('Y-m-d 00:00:00');
            $end_time = date('Y-m-d 23:59:59');
            $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
            $res_activity = $m_activity->getDataList('*',$where,'id desc',0,1);
            $res_activity = $res_activity['list'][0];
            $activity_id = intval($res_activity['id']);
        }
        if(empty($res_activity)){
            $this->to_back(90157);
        }

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $activity_date = date('Y-m-d',strtotime($res_activity['start_time']));
        $start_hour = date('H:i',strtotime($res_activity['start_time']));
        $end_hour = date('H:i',strtotime($res_activity['end_time']));
        $lottery_hour = date('H:i',strtotime($res_activity['lottery_time']));

        $is_apply = 0;
        $redis = new \Common\Lib\SavorRedis();
        $lkey = C('SMALLAPP_LOTTERY');
        $cache_key = $lkey.":$activity_id:$openid";
        $redis->select(1);
        $status = 0;
        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        if($res_activity['status']==2){
            $where = array('openid'=>$openid,'activity_id'=>$activity_id);
            $res_apply = $m_activityapply->getDataList('*',$where,'id desc',0,1);
            if($res_apply['total'] && $res_apply['list'][0]['status']==2){
                $status = 3;
            }else{
                $status = 4;
            }
        }else{
            $res_cache = $redis->get($cache_key);
            if(!empty($res_cache)){
                $status = 1;
            }else{
                $where = array('openid'=>$openid,'activity_id'=>$activity_id);
                $res_apply = $m_activityapply->getDataList('*',$where,'id desc',0,1);
                if($res_apply['total']){
                    $status = 1;
                }
            }
            $end_time = strtotime($res_activity['end_time']);
            $now_time = time();
            if($status==0 && $now_time>$end_time){
                $status = 2;
            }
            $now_time = date('Y-m-d H:i:s');
            if($status==0 && $now_time>$res_activity['start_time'] && $now_time<=$res_activity['end_time']){
                $is_apply = 1;
                $adata = array('activity_id'=>$activity_id,'box_mac'=>$box_mac,'openid'=>$openid,'status'=>1);
                $m_activityapply->add($adata);
                $redis->set($cache_key,date('Y-m-d H:i:s'),10800);
                $status = 1;
            }

        }
        switch ($status){
            case 1:
                $tips = '恭喜您，报名成功';
                $message = "开奖时间为{$activity_date}（今天）{$lottery_hour}，请及时关注中奖结果。详细奖项请看奖品列表";
                break;
            case 2:
                $tips = '已过本轮抽奖时间，请等待下一轮抽奖';
                $message = "本轮报名时间为{$activity_date} {$start_hour}-{$end_hour}。现已超时，请等待新一轮抽奖";
                break;
            case 3:
                $tips = "恭喜您，获得{$res_activity['prize']}";
                $expire_time = date('Y-m-d H:00',strtotime($res_apply['list'][0]['expire_time']));
                $message = "请及时联系餐厅服务人员进行兑换，过期无效。有效时间至：{$expire_time}";
                break;
            case 4:
                $tips = "很遗憾，没有中奖哦，下一轮继续吧～";
                $message = '';
                break;
            default:
                $tips = $message = '';
        }
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $data = array('activity_num'=>$activity_id,'status'=>$status,'tips'=>$tips,'message'=>$message,
            'prize_name'=>$res_activity['prize'],'img_url'=>$oss_host.$res_activity['image_url']);
        $this->to_back($data);
    }


}