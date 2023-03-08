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
            case 'getTurntableStatus':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'openid'=>1001);
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
            case 'startTurntable':
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001);
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
            case 'againTurntable':
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001,'box_mac'=>1001,
                    'openid'=>1001,
                );
                break;
            case 'lottery':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1002,'activity_id'=>1002,'version'=>1002);
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
            case 'againLottery':
                $this->valid_fields = array('openid'=>1001,'activity_id'=>1001);
                break;
            case 'julottery':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1002,'activity_id'=>1001);
                break;
            case 'joinTastewine':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'activity_id'=>1001,'mobile'=>1001);
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

    public function getTurntableStatus(){
        $box_mac = $this->params['box_mac'];
        $openid  = $this->params['openid'];
        $m_turntable_log = new \Common\Model\Smallapp\TurntableLogModel();
        $where = array('openid'=>$openid,'box_mac'=>$box_mac,'is_start'=>0);
        $res_activity = $m_turntable_log->getOne('*',$where);
        $activity_id = 0;
        $status = 0;
        if(!empty($res_activity)){
            $create_time = strtotime($res_activity['create_time']);
            $now_time = time();
            if($now_time-$create_time<600){
                $activity_id = $res_activity['activity_id'];
                $status = 1;
            }
        }
        $data = array('activity_id'=>$activity_id,'status'=>$status);
        $this->to_back($data);
    }

    public function startTurntable(){
        $activity_id = $this->params['activity_id'];
        $m_turntable_log = new \Common\Model\Smallapp\TurntableLogModel();
        $where = array('activity_id'=>$activity_id,'is_start'=>0);
        $res_activity = $m_turntable_log->getOne('*',$where);
        if(!empty($res_activity)){
            $create_time = strtotime($res_activity['create_time']);
            $now_time = time();
            if($now_time-$create_time>600){
                $this->to_back(90170);
            }
        }
        $data = array('startgame_time'=>getMillisecond(),'is_start'=>1,'update_time'=>date('Y-m-d H:i:s'),'play_times'=>1);
        $m_turntable_log->updateInfo($where, $data);

        $m_turntable_detail = new \Common\Model\Smallapp\TurntableDetailModel();
        $where = array('activity_id'=>$activity_id);
        $fields = 'mobile_model,mobile_brand,openid';
        $res_details = $m_turntable_detail->getDatas($fields,$where,'id asc');
        if(!empty($res_details)){
            $turntable_openids = array();
            foreach ($res_details as $v){
                $turntable_openids[]=$v['openid'];
            }
            $lwhere = array('openid'=>array('in',$turntable_openids));
            $m_user = new \Common\Model\Smallapp\UserModel();
            $users = $m_user->getWhere('openid,avatarUrl,nickName',$lwhere,'id desc','','');
            $turntable_user = array();
            foreach ($users as $uv){
                $turntable_user[] = array('avatarUrl'=>base64_encode($uv['avatarUrl']),'nickName'=>$uv['nickName']);
            }
            $host_name = C('HOST_NAME');
            $gamecode = $host_name."/smallapp46/activity/getGameCode?scene={$res_activity['box_mac']}_$activity_id";
            $netty_data = array('action'=>105,'openid'=>$res_activity['openid'],'activity_id'=>$activity_id,'gamecode'=>$gamecode
            ,'turntable_user'=>$turntable_user);
            $message = json_encode($netty_data);
            $m_netty = new \Common\Model\NettyModel();
            $res_push = $m_netty->pushBox($res_activity['box_mac'],$message);
            if($res_push['error_code']){
                $this->to_back($res_push['error_code']);
            }
        }

        $this->to_back(10000);
    }

    /*
     * 接口1 判断当前用户是否有未开始的游戏
     * 接口2 开始游戏 判断当前是否有
     */

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

    public function againTurntable(){
        $activity_id = intval($this->params['activity_id']);
        $box_mac     = $this->params['box_mac'];
        $openid      = $this->params['openid'];
        $m_turntable_log = new \Common\Model\Smallapp\TurntableLogModel();
        $m_turntable_log->where('activity_id='.$activity_id)->setInc('play_times');

        $m_turntable_detail = new \Common\Model\Smallapp\TurntableDetailModel();
        $where = array('activity_id'=>$activity_id);
        $fields = 'mobile_model,mobile_brand,openid';
        $res_details = $m_turntable_detail->getDatas($fields,$where,'id asc');
        if(!empty($res_details)){
            $turntable_openids = array();
            foreach ($res_details as $v){
                $turntable_openids[]=$v['openid'];
                $data = array('activity_id'=>$activity_id,'openid'=>$v['openid'],
                    'mobile_brand'=>$v['mobile_brand'],'mobile_model'=>$v['mobile_model'],
                    'join_time'=>getMillisecond());
                $m_turntable_detail->addInfo($data,1);
            }
            if(!in_array($openid,$turntable_openids)){
                $data = array('activity_id'=>$activity_id,'openid'=>$openid,
                    'mobile_brand'=>'','mobile_model'=>'',
                    'join_time'=>getMillisecond());
                $m_turntable_detail->addInfo($data,1);
                $turntable_openids[]=$openid;
            }
            $lwhere = array('openid'=>array('in',$turntable_openids));
            $m_user = new \Common\Model\Smallapp\UserModel();
            $users = $m_user->getWhere('openid,avatarUrl,nickName',$lwhere,'id desc','','');
            $turntable_user = array();
            foreach ($users as $uv){
                $turntable_user[] = array('avatarUrl'=>base64_encode($uv['avatarUrl']),'nickName'=>$uv['nickName']);
            }
            $host_name = C('HOST_NAME');
            $gamecode = $host_name."/smallapp46/activity/getGameCode?scene={$box_mac}_$activity_id";
            $netty_data = array('action'=>105,'openid'=>$openid,'activity_id'=>$activity_id,'gamecode'=>$gamecode
            ,'turntable_user'=>$turntable_user);
            $message = json_encode($netty_data);
            $m_netty = new \Common\Model\NettyModel();
            $res_push = $m_netty->pushBox($box_mac,$message);
            if($res_push['error_code']){
                $this->to_back($res_push['error_code']);
            }
        }

        $this->to_back(10000);

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
                $pre_time = strtotime($res_activity['add_time']);
                $now_time = time();
                $lottery_timeout = C('LOTTERY_TIMEOUT');
                if($now_time-$pre_time>$lottery_timeout){
                    $lottery_status = 0;
                }else{
                    $lottery_status = 2;
                }
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
            $pre_time = strtotime($res_activity['list'][0]['add_time']);
            $now_time = time();
            $lottery_timeout = C('LOTTERY_TIMEOUT');
            if($now_time-$pre_time<$lottery_timeout){
                $this->to_back(90167);
            }
        }

        $start_time = date('Y-m-d H:i:s');
        $data = array('hotel_id'=>$hotel_id,'box_mac'=>$box_mac,'openid'=>$openid,'prize'=>$prize,'image_url'=>$image_url,
            'start_time'=>$start_time,'status'=>0,'type'=>2);
        $res_id = $m_activity->add($data);
        if($res_id){
            $countdown = 300;
            $partakedish_img = $image_url.'?x-oss-process=image/resize,m_mfit,h_200,w_300';
            $img_info = pathinfo($image_url);

            $m_box = new \Common\Model\BoxModel();
            $condition = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
            $res_box = $m_box->getBoxByCondition('box.id as box_id',$condition);
            $host_name = C('HOST_NAME');
            $qrcode_url = $host_name."/smallapp46/qrcode/getBoxQrcode?box_mac={$box_mac}&type=35&data_id={$res_id}&box_id={$res_box['box_id']}";
            $netty_data = array('action'=>135,'countdown'=>$countdown,'lottery_time'=>'',
                'lottery_countdown'=>0,'partake_img'=>$partakedish_img,'partake_filename'=>$img_info['basename'],
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

            $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
            $adata = array('activity_id'=>$res_id,'box_mac'=>$box_mac,'openid'=>$openid,'status'=>1);
            $m_activityapply->add($adata);
            $redis = new \Common\Lib\SavorRedis();
            $lkey = C('SMALLAPP_LOTTERY');
            $cache_key = $lkey.":$res_id:$openid";
            $redis->select(1);
            $redis->set($cache_key,date('Y-m-d H:i:s'),10800);
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
        $pre_time = strtotime($res_activity['add_time']);
        $now_time = time();
        $lottery_timeout = C('LOTTERY_TIMEOUT');
        if($now_time-$pre_time>$lottery_timeout){
            $this->to_back(90169);
        }
        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        $where = array('activity_id'=>$activity_id);
        $res_apply_user = $m_activityapply->getApplylist('*',$where,'id asc','openid');
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
        $m_invalidlist = new \Common\Model\Smallapp\ForscreenInvalidlistModel();
        $res_invalid = $m_invalidlist->getInfo(array('invalidid'=>$openid,'type'=>2));
        if(!empty($res_invalid)){
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
            $pre_time = strtotime($res_hasactivity['list'][0]['add_time']);
            $now_time = time();
            $lottery_timeout = C('LOTTERY_TIMEOUT');
            if($now_time-$pre_time<$lottery_timeout){
                $this->to_back(90167);
            }
        }
        $start_time = date('Y-m-d H:i:s');
        $data = array('hotel_id'=>$res_activity['hotel_id'],'box_mac'=>$res_activity['box_mac'],'openid'=>$openid,
            'prize'=>$res_activity['prize'],'image_url'=>$res_activity['image_url'],
            'start_time'=>$start_time,'status'=>0,'type'=>2);
        $res_id = $m_activity->add($data);
        if($res_id){
            $countdown = 300;
            $partakedish_img = $res_activity['image_url'].'?x-oss-process=image/resize,m_mfit,h_200,w_300';
            $img_info = pathinfo($res_activity['image_url']);
            $m_box = new \Common\Model\BoxModel();
            $condition = array('box.mac'=>$res_activity['box_mac'],'box.state'=>1,'box.flag'=>0);
            $res_box = $m_box->getBoxByCondition('box.id as box_id',$condition);
            $host_name = C('HOST_NAME');
            $qrcode_url = $host_name."/smallapp46/qrcode/getBoxQrcode?box_mac={$res_activity['box_mac']}&type=35&data_id={$res_id}&box_id={$res_box['box_id']}";
            $netty_data = array('action'=>135,'countdown'=>$countdown,'lottery_time'=>'',
                'lottery_countdown'=>0,'partake_img'=>$partakedish_img,'partake_filename'=>$img_info['basename'],
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

            $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
            $where = array('activity_id'=>$activity_id);
            $res_apply_user = $m_activityapply->getDataList('*',$where,'id asc');
            if(!empty($res_apply_user)){
                $redis = new \Common\Lib\SavorRedis();
                $lkey = C('SMALLAPP_LOTTERY');
                foreach ($res_apply_user as $v){
                    $adata = array('activity_id'=>$res_id,'box_mac'=>$res_activity['box_mac'],'openid'=>$v['openid'],'status'=>1);
                    $m_activityapply->add($adata);

                    $cache_key = $lkey.":$res_id:{$v['openid']}";
                    $redis->select(1);
                    $redis->set($cache_key,date('Y-m-d H:i:s'),10800);
                }
            }

        }
        $resp_data = array('activity_id'=>$res_id);
        $this->to_back($resp_data);
    }

    public function lottery(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $activity_id = intval($this->params['activity_id']);
        $version = $this->params['version'];

        $m_box = new \Common\Model\BoxModel();
        $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);
        if(isset($forscreen_info['box_id']) && $forscreen_info['box_id']>0){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(15);
            $cache_key = 'savor_box_' . $forscreen_info['box_id'];
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info, true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $cache_key = 'savor_hotel_' . $room_info['hotel_id'];
            $redis_hotel_info = $redis->get($cache_key);
            $hotel_info = json_decode($redis_hotel_info, true);
            $hotel_id = $room_info['hotel_id'];
            $hotel_name = $hotel_info['name'];
            $room_id = $box_info['room_id'];
            $box_id = $forscreen_info['box_id'];
            $box_name = $box_info['name'];
        }else{
            $where = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
            $fields = 'a.id as box_id,a.name as box_name,c.id as room_id,d.id as hotel_id,d.name as hotel_name';
            $rets = $m_box->getBoxInfo($fields, $where);
            $hotel_id = $rets[0]['hotel_id'];
            $hotel_name = $rets[0]['hotel_name'];
            $room_id = $rets[0]['room_id'];
            $box_id = $rets[0]['box_id'];
            $box_name = $rets[0]['box_name'];
        }

        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        if($activity_id>0){
            $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
        }else{
            $where = array('hotel_id'=>$hotel_id);
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
        $user_info = $m_user->getOne('id,openid,avatarUrl,nickName,mpopenid',$where,'');
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
        $lottery_apply_id = 0;
        if($res_activity['status']==2){
            $where = array('openid'=>$openid,'activity_id'=>$activity_id);
            $res_apply = $m_activityapply->getDataList('*',$where,'id desc',0,1);
            if($res_apply['total']){
                $lottery_apply_id = $res_apply['list'][0]['id'];
                if($res_apply['list'][0]['status']==2){
                    $status = 3;
                }elseif($res_apply['list'][0]['status']==3){
                    $status = 4;
                }else{
                    $status = 1;
                }
            }else{
                $end_time = strtotime($res_activity['end_time']);
                $now_time = time();
                if($status==0 && $now_time>$end_time){
                    $status = 2;
                }
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
                $adata = array('activity_id'=>$activity_id,'box_mac'=>$box_mac,'openid'=>$openid,'status'=>1,
                    'hotel_id'=>$hotel_id,'hotel_name'=>$hotel_name,'room_id'=>$room_id,'box_id'=>$box_id,'box_name'=>$box_name,
                );
                $m_activityapply->add($adata);
                $redis->set($cache_key,date('Y-m-d H:i:s'),10800);
                $status = 1;
            }
        }
        $is_hotplay = 1;
        switch ($status){
            case 1:
                $m_config = new \Common\Model\SysConfigModel();
                $all_config = $m_config->getAllconfig();
                $hotellottery_people_num = $all_config['hotellottery_people_num'];

                $lottery_stime = strtotime($res_activity['lottery_time']);
                $countdown_time = $lottery_stime-time()>0?$lottery_stime-time():0;
                $countdown_mtime = round($countdown_time/60);
                if($countdown_mtime>0){
                    $tips = $countdown_mtime.'分钟后开始抽奖';
                }else{
                    $tips = '即将开始抽奖';
                    if(time()>$lottery_stime){
                        $awhere = array('activity_id'=>$activity_id);
                        $res_apply = $m_activityapply->getDataList('*',$awhere,'id desc',0,1);
                        if($res_apply['total']<$hotellottery_people_num){
                            $tips = '参与人数不足，无法开奖';
                        }
                    }
                }
                $message = "本轮参与人数在{$hotellottery_people_num}人以上才可开始抽奖";
                break;
            case 2:
                $tips = '已过本轮抽奖时间，请等待下一轮抽奖';
                $message = "本轮报名时间为{$activity_date} {$start_hour}-{$end_hour}。现已超时，请等待新一轮抽奖";
                break;
            case 3:
                $tips = "恭喜您中奖了";
                $message = '';
                if(!empty($res_apply['list'][0]['prize_id'])){
                    $prize_id = $res_apply['list'][0]['prize_id'];
                    $m_prize = new \Common\Model\Smallapp\ActivityprizeModel();
                    $res_prize = $m_prize->getInfo(array('id'=>$prize_id));
                    $res_activity['prize'] = $res_prize['name'];
                    $res_activity['img_url'] = $res_prize['image_url'];
                    $res_activity['prize_type'] = $res_prize['type'];

                    switch ($res_prize['type']){
                        case 2:
                            $tips = "恭喜您，获得{$res_prize['name']}";
                            break;
                        case 4:
                            $m_prizepool_prize = new \Common\Model\Smallapp\PrizepoolprizeModel();
                            $res_prizepool = $m_prizepool_prize->getInfo(array('id'=>$res_prize['prizepool_prize_id']));
                            $coupon_id = intval($res_prizepool['coupon_id']);
                            $m_coupon = new \Common\Model\Smallapp\CouponModel();
                            $res_coupon = $m_coupon->getInfo(array('id'=>$coupon_id));
                            if($res_coupon['min_price']>0){
                                $min_price = "满{$res_coupon['min_price']}可用";
                            }else{
                                $min_price = '无门槛';
                            }
                            $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
                            $res_ucoupon = $m_user_coupon->getInfo(array('activity_id'=>$activity_id,'coupon_id'=>$coupon_id));
                            $start_time = date('Y.m.d H:i',strtotime($res_ucoupon['start_time']));
                            $end_time = date('Y.m.d H:i',strtotime($res_ucoupon['end_time']));
                            $res_activity['coupon_user_id'] = $res_ucoupon['id'];
                            $res_activity['coupon_money'] = $res_coupon['money'];
                            $res_activity['coupon_min_price'] = $min_price;
                            $res_activity['coupon_start_time'] = "有效期：{$start_time}";
                            $res_activity['coupon_end_time'] = "至{$end_time}";
                            break;
                    }
                }
                break;
            case 4:
                $no_lottery_tips = array('很遗憾，没有中奖哦～','太可惜了，竟与奖品擦肩而过！','据说换个姿势能提高中奖几率哦～','谢谢参与');
                shuffle($no_lottery_tips);
                $tips = $no_lottery_tips[0];
                $message = '';
                break;
            default:
                $tips = $message = '';
        }
        $lottery_time = date('Y-m-d H:i:s',strtotime($res_activity['lottery_time']));
        $oss_host = get_oss_host();
        $img_url = '';
        if(!empty($res_activity['img_url'])){
            $img_url = $oss_host.$res_activity['img_url'];
        }

        $data = array('activity_num'=>$activity_id,'status'=>$status,'tips'=>$tips,'message'=>$message,
            'activity_name'=>$res_activity['name'],'prize_name'=>$res_activity['prize'],'img_url'=>$img_url,
            'nickName'=>$user_info['nickName'],'avatarUrl'=>$user_info['avatarUrl'],'hotel_name'=>$hotel_name,
            'is_hotplay'=>$is_hotplay,'lottery_time'=>$lottery_time,'lottery_apply_id'=>$lottery_apply_id);
        if($status==3){
            $data['prize_type']  = $res_activity['prize_type'];
            $data['coupon_user_id']  = $res_activity['coupon_user_id'];
            $data['coupon_money']  = $res_activity['coupon_money'];
            $data['coupon_min_price']  = $res_activity['coupon_min_price'];
            $data['coupon_start_time']  = $res_activity['coupon_start_time'];
            $data['coupon_end_time']  = $res_activity['coupon_end_time'];
        }
        $this->to_back($data);
    }

    public function julottery(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $activity_id = intval($this->params['activity_id']);

        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
        if(empty($res_activity)){
            $this->to_back(90157);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,avatarUrl,nickName,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $redis = new \Common\Lib\SavorRedis();
        $lkey = C('SMALLAPP_LOTTERY');
        $cache_key = $lkey.":$activity_id:$openid";
        $redis->select(1);
        $status = 0;
        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        $lottery_time = '';
        if($res_activity['status']==2){
            $where = array('openid'=>$openid,'activity_id'=>$activity_id);
            $res_apply = $m_activityapply->getDataList('*',$where,'id asc',0,1);
            if($res_apply['total']){
                $status = 3;
                $lottery_time = $res_apply['list'][0]['add_time'];
            }
        }else{
            $res_cache = $redis->get($cache_key);
            if(!empty($res_cache)){
                $status = 3;
                $lottery_time = $res_cache;
            }else{
                $where = array('openid'=>$openid,'activity_id'=>$activity_id);
                $res_apply = $m_activityapply->getDataList('*',$where,'id asc',0,1);
                if($res_apply['total']){
                    $status = 3;
                    $lottery_time = $res_apply['list'][0]['add_time'];
                }
            }
        }
        $jd_page_url = 'pages/item/detail/detail?sku=30642076102';
        $jd_price = '988';
        $price = '854';
        if($status==0){
            $adata = array('activity_id'=>$activity_id,'box_mac'=>$box_mac,'openid'=>$openid,'status'=>2);
            $m_activityapply->add($adata);
            $redis->set($cache_key,date('Y-m-d H:i:s'),10800);
            $status = 3;
            $lottery_time = date('Y-m-d H:i:s');
//            $m_activity->updateData(array('id'=>$activity_id),array('status'=>2));
            $prize_list = array('1.'.$res_activity['prize'],'2.'.$res_activity['attach_prize']);
            $message = array('action'=>152,'countdown'=>120,'prize_list'=>$prize_list,'price'=>$price,'jd_price'=>$jd_price);
            $m_netty = new \Common\Model\NettyModel();
            $m_netty->pushBox($res_activity['box_mac'],json_encode($message));
        }
        $tips = "恭喜您获得以下奖品";
        $m_box = new \Common\Model\BoxModel();
        $res_box = $m_box->getHotelInfoByBoxMacNew($res_activity['box_mac']);
        $box_name = '';
        if(!empty($res_box)){
            $box_name = $res_box['box_name'];
        }
        $data = array('activity_num'=>$activity_id,'status'=>$status,'tips'=>$tips,
            'activity_name'=>$res_activity['name'],'prize1'=>$res_activity['prize'],'prize2'=>$res_activity['attach_prize'],
            'nickName'=>$user_info['nickName'],'avatarUrl'=>$user_info['avatarUrl'],
            'lottery_time'=>$lottery_time,'box_name'=>$box_name,'is_compareprice'=>$res_activity['is_compareprice'],
            'price'=>$price,'jd_price'=>$jd_price,'jd_page_url'=>$jd_page_url);
        $this->to_back($data);
    }

    public function joinTastewine(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $activity_id = intval($this->params['activity_id']);
        $mobile = $this->params['mobile'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid' => $openid, 'status' => 1);
        $user_info = $m_user->getOne('id,openid,avatarUrl,nickName,mpopenid', $where, '');
        if(empty($user_info)){
            $this->to_back(90116);
        }

        $m_invalidlist = new \Common\Model\Smallapp\ForscreenInvalidlistModel();
        $res_invalid = $m_invalidlist->getInfo(array('invalidid'=>$openid));
        if(!empty($res_invalid)){
            $resp_data = array('message'=>'无法领取','tips'=>'请联系管理员');
            $this->to_back($resp_data);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,unionId,mobile',$where,'');
        if(!empty($user_info['unionId'])){
            $where = array('unionId'=>$user_info['unionId'],'small_app_id'=>5);
            $res_sale_user = $m_user->getOne('id,openid,unionId',$where,'');
            if(!empty($res_sale_user)){
                $resp_data = array('message'=>'无法领取','tips'=>'请联系管理员');
                $this->to_back($resp_data);
            }
        }
        if(!empty($user_info['mobile'])){
            $where = array('mobile'=>$user_info['mobile'],'small_app_id'=>5);
            $res_sale_user = $m_user->getOne('id,openid,unionId',$where,'');
            if(!empty($res_sale_user)){
                $resp_data = array('message'=>'无法领取','tips'=>'请联系管理员');
                $this->to_back($resp_data);
            }
        }

        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id,'status'=>1));
        if(empty($res_activity)){
            $this->to_back(90175);
        }
        $now_time = date('Y-m-d H:i:s');
        if($res_activity['start_time']>$now_time || $res_activity['end_time']<$now_time){
            $this->to_back(90182);
        }
        $m_box = new \Common\Model\BoxModel();
        $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);
        if(isset($forscreen_info['box_id']) && $forscreen_info['box_id']>0){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(15);
            $cache_key = 'savor_box_' . $forscreen_info['box_id'];
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info, true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $cache_key = 'savor_hotel_' . $room_info['hotel_id'];
            $redis_hotel_info = $redis->get($cache_key);
            $hotel_info = json_decode($redis_hotel_info, true);
            $hotel_id = $room_info['hotel_id'];
            $hotel_name = $hotel_info['name'];
            $room_id = $box_info['room_id'];
            $box_id = $forscreen_info['box_id'];
            $box_name = $box_info['name'];
        }else{
            $where = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
            $fields = 'a.id as box_id,a.name as box_name,c.id as room_id,d.id as hotel_id,d.name as hotel_name';
            $rets = $m_box->getBoxInfo($fields, $where);
            $hotel_id = $rets[0]['hotel_id'];
            $hotel_name = $rets[0]['hotel_name'];
            $room_id = $rets[0]['room_id'];
            $box_id = $rets[0]['box_id'];
            $box_name = $rets[0]['box_name'];
        }
        $m_activityhotel = new \Common\Model\Smallapp\ActivityhotelModel();
        $res_ahotel = $m_activityhotel->getInfo(array('activity_id'=>$activity_id,'hotel_id'=>$hotel_id));
        if(empty($res_ahotel)){
            $this->to_back(90175);
        }
        $lunch_stime = date("Y-m-d {$res_activity['lunch_start_time']}");
        $lunch_etime = date("Y-m-d {$res_activity['lunch_end_time']}");
        $dinner_stime = date("Y-m-d {$res_activity['dinner_start_time']}");
        $dinner_etime = date("Y-m-d {$res_activity['dinner_end_time']}");
        $meal_type = '';
        $meal_stime = $meal_etime = '';
        if($now_time>=$lunch_stime && $now_time<=$lunch_etime){
            $meal_type = 'lunch';
            $meal_stime = $lunch_stime;
            $meal_etime = $lunch_etime;
        }elseif($now_time>=$dinner_stime && $now_time<=$dinner_etime){
            $meal_type = 'dinner';
            $meal_stime = $dinner_stime;
            $meal_etime = $dinner_etime;
        }
        if(empty($meal_type)){
            $this->to_back(90176);
        }
        $people_num = $res_activity['people_num'];
        $bottle_num = $res_activity['bottle_num'];
        $join_num = $res_activity['join_num'];
        $meal_get_num = $res_activity['meal_get_num'];
        $box_get_num = $res_activity['box_get_num'];

        $u_fields = 'count(a.id) as num';
        $u_where = array('a.openid'=>$openid,'activity.type'=>6);
        $u_where['a.add_time'] = array('egt','2023-03-01 00:00:00');
        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        $res_activity_apply = $m_activityapply->getApplyDatas($u_fields,$u_where,'a.id desc','','');
        if($res_activity_apply[0]['num']>$join_num){
            $this->to_back(90178);
        }

        $where = array('activity_id'=>$activity_id,'hotel_id'=>$hotel_id);
        $res_activity_apply = $m_activityapply->getApplylist('count(*) as num',$where,'id desc','');
        $all_hotel_num = $people_num*$bottle_num;
        if($res_activity_apply[0]['num']>=$all_hotel_num){
            $this->to_back(90177);
        }
        $where['add_time'] = array(array('egt',$meal_stime),array('elt',$meal_etime));
        $res_activity_apply = $m_activityapply->getApplylist('count(*) as num',$where,'id desc','');
        if($res_activity_apply[0]['num']>=$meal_get_num){
            $this->to_back(90177);
        }
        $where['box_mac'] = $box_mac;
        $res_activity_apply = $m_activityapply->getApplylist('count(*) as num',$where,'id desc','');
        if($res_activity_apply[0]['num']>=$box_get_num){
            $this->to_back(90177);
        }

        $where = array('activity_id'=>$activity_id,'openid'=>$openid);
        $where['add_time'] = array(array('egt',$meal_stime),array('elt',$meal_etime));
        $res_activity_apply = $m_activityapply->getApplylist('*',$where,'id desc','');
        if($res_activity_apply[0]['status']==1){
            $this->to_back(90179);
        }
        $join_time = date('Y-m-d H:i:s');
        $data = array('activity_id'=>$activity_id,'hotel_id'=>$hotel_id,'hotel_name'=>$hotel_name,'room_id'=>$room_id,
            'box_id'=>$box_id,'box_name'=>$box_name,'box_mac'=>$box_mac,'openid'=>$openid,'status'=>1,'mobile'=>$mobile,
            'add_time'=>$join_time
        );
        $apply_id = $m_activityapply->addData($data);

        $user_info['nickName'] = '手机尾号'.substr($mobile,-4);
        $m_netty = new \Common\Model\NettyModel();
        $message = array('action'=>153,'nickName'=>$user_info['nickName'],'headPic'=>base64_encode($user_info['avatarUrl']),
            'url'=>$res_activity['image_url']);
        $m_netty->pushBox($box_mac,json_encode($message));

        $all_box = $m_netty->getPushBox(2,$box_mac);
        $barrage = $box_name.'包间成功领取了一份小热点赠送的品鉴酒';
        foreach ($all_box as $box){
            $user_barrages = array(array('nickName'=>$user_info['nickName'],'headPic'=>base64_encode($user_info['avatarUrl']),'barrage'=>$barrage));
            $message = array('action'=>122,'userBarrages'=>$user_barrages);
            $m_netty->pushBox($box,json_encode($message));
        }

        $wine_ml = $res_activity['wine_ml'];
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $fields = 'g.id,g.finance_goods_id,g.name,g.detail_imgs';
        $res_goods = $m_hotelgoods->getGoodsList($fields,array('h.hotel_id'=>$hotel_id,'g.finance_goods_id'=>$res_activity['finance_goods_id']),'','0,1');
        $goods_name = $res_goods[0]['name'];

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('hotel_id'=>$hotel_id,'status'=>1));
        $end_mobile = substr($mobile,-4);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('user.mobile',array('a.merchant_id'=>$res_merchant['id'],'a.status'=>1,'user.status'=>1),'a.id desc');
        $all_mobiles = array();
        foreach ($res_staff as $sv){
            if(!empty($sv['mobile'])){
                $all_mobiles[]=$sv['mobile'];
            }
        }
        $mobiles = join(',',$all_mobiles);

        $emsms = new \Common\Lib\EmayMessage();
        $sms_params = array('box_name'=>$box_name,'end_mobile'=>$end_mobile,'goods_name'=>$goods_name,'mobiles'=>$mobiles);
        $content = "{$box_name}包间手机尾号{$end_mobile}的客人成功领取了品鉴酒{$goods_name}{$res_activity['wine_ml']}ml。请及时领取任务，为客人斟酒。";
        $res_data = $emsms->sendSMS($content,$mobiles);
        $resp_code = $res_data->code;
        $data = array('type'=>15,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
            'url'=>join(',',$sms_params),'tel'=>$all_mobiles[0],'resp_code'=>$resp_code,'msg_type'=>3
        );
        $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
        $m_account_sms_log->addData($data);

        $sms_params = array('goods_name'=>$goods_name,'wine_ml'=>$wine_ml);
        $user_content = "恭喜您成功领取品鉴酒{$goods_name}{$res_activity['wine_ml']}ml，已通知餐厅经理为您斟酒，请稍候。为节省您的时间，您也可向服务员询问。";
        $res_data = $emsms->sendSMS($user_content,$mobile);
        $resp_code = $res_data->code;
        $data = array('type'=>15,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
            'url'=>join(',',$sms_params),'tel'=>$mobile,'resp_code'=>$resp_code,'msg_type'=>3
        );
        $m_account_sms_log->addData($data);
        
        $m_message = new \Common\Model\Smallapp\MessageModel();
        $m_message->recordMessage($openid,$apply_id,11);

        $resp_data = array('message'=>"恭喜您领到本饭局品鉴酒",'tips'=>'已通知餐厅为您送酒，为节省等待时间，您可直接向服务员询问','join_time'=>$join_time);
        $this->to_back($resp_data);
    }
}