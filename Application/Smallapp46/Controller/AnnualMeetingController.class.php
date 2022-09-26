<?php
namespace Smallapp46\Controller;
use Common\Lib\AliyunOss;
use \Common\Controller\CommonController as CommonController;

class AnnualMeetingController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addMeeting':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001);
                break;
            case 'startSignin':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'annualmeeting_id'=>1001,'name'=>1001,'show_time'=>1001);
                break;
            case 'joinSignin':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'signin_id'=>1001,'box_mac'=>1001);
                break;
            case 'startLottery':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'annualmeeting_id'=>1001,'box_mac'=>1001,'prize'=>1001,'image'=>1001,'people_num'=>1001);
                break;
            case 'modulelist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'annualmeeting_id'=>1001);
                break;
            case 'addvideo':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'annualmeeting_id'=>1001,'oss_addr'=>1001,'resource_size'=>1001,'box_mac'=>1001,
                    'duration'=>1001,'start_time'=>1001,'end_time'=>1001,'type'=>1001,'mv_id'=>1002);
                break;
            case 'loopplay':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'annualmeeting_id'=>1001);
                break;


        }
        parent::_init_();
    }

    public function addMeeting(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_box = new \Common\Model\BoxModel();
        $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
        $fields = 'hotel.id as hotel_id,hotel.name as hotel_name,room.id as room_id,room.name as room_name,box.id as box_id';
        $res_box = $m_box->getBoxByCondition($fields,$where);
        $hotel_id = $res_box[0]['hotel_id'];
        $hotel_name = $res_box[0]['hotel_name'];
        $room_id = $res_box[0]['room_id'];
        $room_name = $res_box[0]['room_name'];
        $box_id = $res_box[0]['box_id'];

        $where = array('openid'=>$openid,'box_mac'=>$box_mac);
        $m_annualmeeting = new \Common\Model\Smallapp\AnnualmeetingModel();
        $res_annualmeeting = $m_annualmeeting->getDataList('*',$where,'id desc');
        if(!empty($res_annualmeeting)){
            $annualmeeting_id = $res_annualmeeting[0]['id'];
        }else{
            $add_data = array('hotel_id'=>$hotel_id,'hotel_name'=>$hotel_name,'room_id'=>$room_id,'room_name'=>$room_name,
                'box_id'=>$box_id,'box_mac'=>$box_mac,'openid'=>$openid);
            $annualmeeting_id = $m_annualmeeting->add($add_data);
        }

        $resp_data = array('annualmeeting_id'=>$annualmeeting_id);
        $this->to_back($resp_data);
    }

    public function startSignin(){
        $openid = $this->params['openid'];
        $annualmeeting_id = $this->params['annualmeeting_id'];
        $name = $this->params['name'];
        $show_time = $this->params['show_time'];
        $box_mac = $this->params['box_mac'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_annualmeeting = new \Common\Model\Smallapp\AnnualmeetingModel();
        $res_meeting = $m_annualmeeting->getInfo(array('id'=>$annualmeeting_id));
        if(!empty($res_meeting)){
            $m_annualmeetingsignin = new \Common\Model\Smallapp\AnnualmeetingSigninModel();
            $res_signin = $m_annualmeetingsignin->getInfo(array('annualmeeting_id'=>$annualmeeting_id));
            $end_strtime = time()+($show_time*60);
            $end_time = date('Y-m-d H:i:00',$end_strtime);
            $add_data = array('name'=>$name,'annualmeeting_id'=>$annualmeeting_id,'show_time'=>$show_time,'end_time'=>$end_time);
            if(empty($res_signin)){
                $signin_id = $m_annualmeetingsignin->add($add_data);
            }else{
                $signin_id = $res_signin['id'];
                $add_data['add_time'] = date('Y-m-d H:i:s');
                $m_annualmeetingsignin->updateData(array('id'=>$signin_id),$add_data);
            }
            $m_box = new \Common\Model\BoxModel();
            $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
            $fields = 'box.id as box_id';
            $res_box = $m_box->getBoxByCondition($fields,$where);
            $box_id = $res_box[0]['box_id'];
            $host_name = C('HOST_NAME');
            $partake_user = array();
            $fields = 'a.openid,user.avatarUrl,user.nickName';
            $m_signinuser = new \Common\Model\Smallapp\AnnualmeetingSigninUserModel();
            $res_alluser = $m_signinuser->getsigninuser($fields,array('a.signin_id'=>$signin_id),'a.id desc','','');
            if(!empty($res_alluser)){
                foreach ($res_alluser as $uv){
                    $partake_user[] = array('avatarUrl'=>base64_encode($uv['avatarUrl']),'nickName'=>$uv['nickName']);
                }
            }
            $qrcode_url = $host_name."/smallapp46/qrcode/getBoxQrcode?box_mac={$box_mac}&type=44&data_id={$signin_id}&box_id={$box_id}";
            $netty_data = array('action'=>161,'countdown'=>$add_data['show_time'],
                'activity_name'=>$name,'codeUrl'=>$qrcode_url,'partake_user'=>$partake_user
            );
            $message = json_encode($netty_data);
            $m_netty = new \Common\Model\NettyModel();
            $res_push = $m_netty->pushBox($box_mac,$message);
            if($res_push['error_code']){
                $this->to_back($res_push['error_code']);
            }
        }
        $this->to_back(array());
    }

    public function joinSignin(){
        $openid = $this->params['openid'];
        $signin_id = $this->params['signin_id'];
        $box_mac = $this->params['box_mac'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_annualmeetingsignin = new \Common\Model\Smallapp\AnnualmeetingSigninModel();
        $res_signin = $m_annualmeetingsignin->getInfo(array('id'=>$signin_id));
        if(!empty($res_signin)){
            $m_signinuser = new \Common\Model\Smallapp\AnnualmeetingSigninUserModel();
            $res_suser = $m_signinuser->getInfo(array('signin_id'=>$signin_id,'openid'=>$openid));
            if(empty($res_suser)){
                $m_signinuser->add(array('signin_id'=>$signin_id,'openid'=>$openid));
            }
            $fields = 'a.openid,user.avatarUrl,user.nickName';
            $res_alluser = $m_signinuser->getsigninuser($fields,array('a.signin_id'=>$signin_id),'a.id desc','','');
            $partake_user = array();
            foreach ($res_alluser as $uv){
                $partake_user[] = array('avatarUrl'=>base64_encode($uv['avatarUrl']),'nickName'=>$uv['nickName']);
            }

            $now_time = strtotime(date('Y-m-d H:i:00'));
            $end_time = strtotime($res_signin['end_time']);
            $diff_time = $end_time-$now_time>0?$end_time-$now_time:0;
            if($diff_time==0){
                $countdown = 0;
            }else{
                $countdown = floor($diff_time%86400/60);
            }

            $m_box = new \Common\Model\BoxModel();
            $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);

            $fields = 'box.id as box_id';
            $res_box = $m_box->getBoxByCondition($fields,$where);
            $box_id = $res_box[0]['box_id'];

            $host_name = C('HOST_NAME');
            $partakedish_img = C('MEETING_SIGNIN_IMG');
            $img_info = pathinfo($partakedish_img);
            $partake_filename = $img_info['basename'];
            $partakedish_img = $partake_filename = '';
            $qrcode_url = $host_name."/smallapp46/qrcode/getBoxQrcode?box_mac={$box_mac}&type=44&data_id={$signin_id}&box_id={$box_id}";
            $netty_data = array('action'=>161,'countdown'=>$countdown,'activity_name'=>$res_signin['name'],
                'partake_img'=>$partakedish_img,'partake_filename'=>$partake_filename,'codeUrl'=>$qrcode_url,'partake_user'=>$partake_user
            );
            $message = json_encode($netty_data);
            $m_netty = new \Common\Model\NettyModel();
            $m_netty->pushBox($box_mac,$message);
        }
        $this->to_back(array());
    }

    public function startLottery(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $prize = trim($this->params['prize']);
        $image_url = $this->params['image'];
        $people_num = intval($this->params['people_num']);
        $annualmeeting_id = $this->params['annualmeeting_id'];

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
        $m_annualmeeting = new \Common\Model\Smallapp\AnnualmeetingModel();
        $res_meeting = $m_annualmeeting->getInfo(array('id'=>$annualmeeting_id));
        if(empty($res_meeting)){
            $this->to_back(90186);
        }
        $m_annualmeetingsignin = new \Common\Model\Smallapp\AnnualmeetingSigninModel();
        $res_signin = $m_annualmeetingsignin->getInfo(array('annualmeeting_id'=>$annualmeeting_id));
        if(empty($res_signin)){
            $this->to_back(90187);
        }
//        if($res_signin['end_time']>date('Y-m-d H:i:s')){
//            $this->to_back(90190);
//        }
        $signin_id = $res_signin['id'];
        $m_signinuser = new \Common\Model\Smallapp\AnnualmeetingSigninUserModel();
        $fields = 'a.openid,user.avatarUrl,user.nickName';
        $res_alluser = $m_signinuser->getsigninuser($fields,array('a.signin_id'=>$signin_id),'a.id desc','','');
        if(empty($res_alluser)){
            $this->to_back(90188);
        }
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $start_time = date('Y-m-d H:i:s');
        $data = array('hotel_id'=>$hotel_id,'box_mac'=>$box_mac,'openid'=>$openid,'prize'=>$prize,'image_url'=>$image_url,
            'start_time'=>$start_time,'people_num'=>$people_num,'status'=>2,'type'=>9);
        $res_id = $m_activity->add($data);
        if($res_id){
            $lottery_users = $partake_user = array();
            $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
            shuffle($res_alluser);
            foreach ($res_alluser as $uv){
                $is_lottery = 0;
                $status = 1;
                if($people_num>0){
                    $status = 2;
                    $is_lottery = 1;
                    $people_num--;
                    $lottery_users[] = array('openid'=>$uv['openid'],'dish_name'=>$prize,'dish_image'=>$image_url,
                        'level'=>0,'room_name'=>'');
                }
                $adata = array('activity_id'=>$res_id,'box_mac'=>$box_mac,'openid'=>$openid,'status'=>$status);
                $m_activityapply->add($adata);

                $partake_user[] = array('openid'=>$uv['openid'],'avatarUrl'=>base64_encode($uv['avatarUrl']),
                    'nickName'=>$uv['nickName'],'is_lottery'=>$is_lottery);
            }
            $netty_data = array('action'=>156,'partake_user'=>$partake_user,'lottery'=>$lottery_users);
            $message = json_encode($netty_data);
            $m_netty = new \Common\Model\NettyModel();
            $res_push = $m_netty->pushBox($box_mac,$message);
            if($res_push['error_code']){
                $this->to_back($res_push['error_code']);
            }
        }
        $resp_data = array('activity_id'=>$res_id);
        $this->to_back($resp_data);
    }

    public function modulelist(){
        $openid = $this->params['openid'];
        $annualmeeting_id = $this->params['annualmeeting_id'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_annualmeeting = new \Common\Model\Smallapp\AnnualmeetingModel();
        $res_meeting = $m_annualmeeting->getInfo(array('id'=>$annualmeeting_id));
        if(empty($res_meeting)){
            $this->to_back(90186);
        }
        $alltype_str = array('1'=>'企业宣传片','2'=>'祝福视频','3'=>'欢迎词');
        $resources = array();
        $m_meetingvideo = new \Common\Model\Smallapp\AnnualmeetingVideoModel();
        $res_video = $m_meetingvideo->getDataList('*',array('annualmeeting_id'=>$annualmeeting_id),'type asc');
        $oss_host = get_oss_host();
        foreach ($res_video as $v){
            $res_url = $oss_host.$v['oss_addr'];
            $img_url = $res_url.'?x-oss-process=video/snapshot,t_3000,f_jpg,w_450,m_fast';
            $pubdetail = array('res_url'=>$res_url,'img_url'=>$img_url,'forscreen_url'=>$v['oss_addr'],'duration'=>$v['duration'],
                'resource_size'=>$v['resource_size']);
            $addr_info = pathinfo($v['oss_addr']);
            $pubdetail['filename'] = $addr_info['basename'];

            $data = array('mv_id'=>$v['id'],'start_time'=>date('Y-m-d H',strtotime($v['start_time'])),'end_time'=>date('Y-m-d H',strtotime($v['end_time'])),
                'status'=>$v['status'],'type'=>$v['type'],'type_str'=>$alltype_str[$v['type']],'pubdetail'=>array($pubdetail)
            );
            $resources[]=$data;
        }
        $m_welcome = new \Common\Model\Smallapp\WelcomeModel();
        $res_welcome = $m_welcome->getDataList('*',array('annualmeeting_id'=>$annualmeeting_id),'id desc');
        $type = 3;
        foreach ($res_welcome as $v){
            $imgs = explode(',',$v['image']);
            $img_url = $oss_host.$imgs[0].'?x-oss-process=image/resize,m_mfit,h_400,w_750';
            $data = array('welcome_id'=>$v['id'],'img_url'=>$img_url,'stay_time'=>$v['stay_time'],'type'=>$type,'type_str'=>$alltype_str[$type]);
            $resources[]=$data;
        }
        $signin_user = array();
        $m_annualmeetingsignin = new \Common\Model\Smallapp\AnnualmeetingSigninModel();
        $res_signin = $m_annualmeetingsignin->getInfo(array('annualmeeting_id'=>$annualmeeting_id));
        if(!empty($res_signin)){
            $m_signinuser = new \Common\Model\Smallapp\AnnualmeetingSigninUserModel();
            $fields = 'a.openid,user.avatarUrl,user.nickName';
            $signin_user = $m_signinuser->getsigninuser($fields,array('a.signin_id'=>$res_signin['id']),'a.id desc','','');
        }
        $res_data = array('resources'=>$resources,'signin_users'=>$signin_user);
        $this->to_back($res_data);
    }

    public function addvideo(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $annualmeeting_id = $this->params['annualmeeting_id'];
        $oss_addr = $this->params['oss_addr'];
        $resource_size = $this->params['resource_size'];
        $duration = $this->params['duration'];
        $start_time = $this->params['start_time'];
        $end_time = $this->params['end_time'];
        $type = $this->params['type'];
        $mv_id = intval($this->params['mv_id']);

        $tmp_start_time = strtotime("$start_time:00:00");
        $tmp_end_time = strtotime("$end_time:00:00");
        $d_start_time = date('Y-m-d',$tmp_start_time);
        $d_end_time = date('Y-m-d',$tmp_end_time);
        if($d_start_time!=$d_end_time){
            $this->to_back(90189);
        }
        $download_date = date('Y-m-d');
        if($d_start_time>$download_date){
            $download_date = date('Y-m-d',$tmp_start_time-86400);
        }
        $start_time = date('Y-m-d H:i:s',$tmp_start_time);
        $end_time = date('Y-m-d H:i:s',$tmp_end_time);
        if($end_time<date('Y-m-d H:i:s')){
            $this->to_back(90191);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_annualmeeting = new \Common\Model\Smallapp\AnnualmeetingModel();
        $res_meeting = $m_annualmeeting->getInfo(array('id'=>$annualmeeting_id));
        if(empty($res_meeting)){
            $this->to_back(90186);
        }

        $accessKeyId = C('OSS_ACCESS_ID');
        $accessKeySecret = C('OSS_ACCESS_KEY');
        $endpoint = 'oss-cn-beijing.aliyuncs.com';
        $bucket = C('OSS_BUCKET');
        $aliyunoss = new AliyunOss($accessKeyId, $accessKeySecret, $endpoint);
        $aliyunoss->setBucket($bucket);
        $range = '0-199';
        $bengin_info = $aliyunoss->getObject($oss_addr,$range);
        $last_size = $resource_size-1;
        $last_range = $last_size - 199;
        $last_range = $last_range.'-'.$last_size;
        $end_info = $aliyunoss->getObject($oss_addr,$last_range);
        $file_str = md5($bengin_info).md5($end_info);
        $fileinfo = strtoupper($file_str);
        $md5_file = md5($fileinfo);

        $data = array('annualmeeting_id'=>$annualmeeting_id,'oss_addr'=>$oss_addr,'resource_size'=>$resource_size,
            'duration'=>$duration,'md5_file'=>$md5_file,'download_date'=>$download_date,'start_time'=>$start_time,'end_time'=>$end_time,
            'status'=>1,'box_mac'=>$box_mac,'type'=>$type
        );
        $m_meeting_video = new \Common\Model\Smallapp\AnnualmeetingVideoModel();
        if($mv_id>0){
            $m_meeting_video->updateData(array('id'=>$mv_id),$data);
        }else{
            $mv_id = $m_meeting_video->add($data);
        }

        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $program_key = C('SAPP_ANNUALMEETING_PROGRAM').$box_mac;
        $period = getMillisecond();
        $cache_data = array('period'=>$period,'is_has_data'=>1);
        $redis->set($program_key,json_encode($cache_data),86400*10);

        $this->to_back(array('mv_id'=>$mv_id));
    }

    public function loopplay(){
        $openid = $this->params['openid'];
        $annualmeeting_id = $this->params['annualmeeting_id'];
        $box_mac = $this->params['box_mac'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_annualmeeting = new \Common\Model\Smallapp\AnnualmeetingModel();
        $res_meeting = $m_annualmeeting->getInfo(array('id'=>$annualmeeting_id));
        if(empty($res_meeting)){
            $this->to_back(90186);
        }

        $resource_ids = array();
        $m_meetingvideo = new \Common\Model\Smallapp\AnnualmeetingVideoModel();
        $res_video = $m_meetingvideo->getDataList('*',array('annualmeeting_id'=>$annualmeeting_id),'type asc');
        foreach ($res_video as $v){
            $resource_ids[] = intval($v['id']);
        }

        $m_welcome = new \Common\Model\Smallapp\WelcomeModel();
        $res_welcome = $m_welcome->getDataList('*',array('annualmeeting_id'=>$annualmeeting_id),'id desc');
        $welcome_message = new \stdClass();
        if(!empty($res_welcome)){
            $res_welcome = $res_welcome[0];
            $m_welcomeresource = new \Common\Model\Smallapp\WelcomeresourceModel();
            $wordsize_id = $res_welcome['wordsize_id'];
            $color_id = $res_welcome['color_id'];
            $backgroundimg_id = $res_welcome['backgroundimg_id'];
            $font_id = $res_welcome['font_id'];
            $music_id = $res_welcome['music_id'];

            $ids = array($wordsize_id,$color_id);
            if($font_id){
                $ids[]=$font_id;
            }
            if($music_id){
                $ids[]=$music_id;
            }
            if($backgroundimg_id){
                $ids[]=$backgroundimg_id;
            }
            $where = array('id'=>array('in',$ids));
            $res_resource = $m_welcomeresource->getDataList('*',$where,'id asc');
            $resource_info = array();
            foreach ($res_resource as $v){
                $resource_info[$v['id']]=$v;
            }
            $finish_time = time() + $res_welcome['stay_time']*60;
            $welcome_message = array('id'=>$res_welcome['id'],'forscreen_char'=>$res_welcome['content'],
                'wordsize'=>$resource_info[$wordsize_id]['tv_wordsize'],'color'=>$resource_info[$color_id]['color'],
                'finish_time'=>date('Y-m-d H:i:s',$finish_time));
            $img_list = array();
            $imgs = explode(',',$res_welcome['image']);
            if(!empty($imgs)){
                foreach ($imgs as $v){
                    if(!empty($v)){
                        $file_info = pathinfo($v);
                        $img_list[] = array('url'=>$v,'filename'=>$file_info['basename']);
                    }
                }
            }
            $welcome_message['img_list'] = $img_list;
            $m_media = new \Common\Model\MediaModel();
            if(isset($resource_info[$font_id])){
                $res_media = $m_media->getMediaInfoById($resource_info[$font_id]['media_id']);
                $welcome_message['font_id'] = intval($font_id);
                $welcome_message['font_oss_addr'] = $res_media['oss_path'];
            }else{
                $welcome_message['font_id'] = 0;
                $welcome_message['font_oss_addr'] = '';
            }
            if(isset($resource_info[$music_id])){
                $res_media = $m_media->getMediaInfoById($resource_info[$music_id]['media_id']);
                $welcome_message['music_id'] = intval($music_id);
                $welcome_message['music_oss_addr'] = $res_media['oss_path'];
            }else{
                $welcome_message['music_id'] = 0;
                $welcome_message['music_oss_addr'] = '';
            }
            $playtime = intval($res_welcome['stay_time']*60);
            $welcome_message['play_times'] = $playtime;
        }

        if(!empty($resource_ids) || !empty($welcome_message)){
            $netty_data = array('action'=>162,'resource_ids'=>$resource_ids,'welcome'=>$welcome_message);
            $message = json_encode($netty_data);
            $m_netty = new \Common\Model\NettyModel();
            $res_push = $m_netty->pushBox($box_mac,$message);
            if($res_push['error_code']){
                $this->to_back($res_push['error_code']);
            }
        }
        $this->to_back(array());
    }



}