<?php
/*
 * 评论
 */
namespace H5\Controller;
use Think\Controller;

class CommentController extends Controller {


    public function info(){
        $params = I('p','');
        $code = I('code', '');
        $params_info = explode('@',$params);
        $openid = $params_info[0];
        $box_id = $params_info[1];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $user_info = $m_user->getOne('id,avatarUrl,nickName,wx_mpopenid,is_subscribe',$where,'id desc');
        $op_type = 0;
        if(empty($user_info)){
            $op_type = 1;
        }elseif(empty($user_info['wx_mpopenid']) || empty($user_info['avatarUrl']) || empty($user_info['nickName'])){
            $op_type = 2;
        }else{
            $op_type = 3;
        }
        $wechat = new \Common\Lib\Wechat();
        if(in_array($op_type,array(1,2)) && empty($code)){
            $http = 'https://';
            $host_name = $http.$_SERVER['HTTP_HOST'];
            $url = $host_name.'/h5/comment/info/p/'.$params;
            $wechat->wx_oauth($url);
        }

        if($code) {
            $result = $wechat->getWxOpenid($code);
            $wx_mpopenid = $result['openid'];
            if ($op_type) {
                $res = $wechat->getWxUserInfo($result['access_token'], $wx_mpopenid);
                if (isset($res['openid'])) {
                    switch ($op_type) {
                        case 1:
                            $user_info = array('openid' => $openid, 'avatarUrl' => $res['headimgurl'], 'nickName' => $res['nickname'],
                                'gender' => $res['sex'], 'wx_mpopenid' => $wx_mpopenid, 'is_subscribe' => $res['subscribe']);
                            $user_id = $m_user->addInfo($user_info);
                            $user_info['id'] = $user_id;
                            break;
                        case 2:
                            $data = array('wx_mpopenid' => $wx_mpopenid);
                            if (isset($res['subscribe'])) {
                                $data['is_subscribe'] = $res['subscribe'];
                            }
                            if (isset($res['headimgurl'])) {
                                $data['avatarUrl'] = $res['headimgurl'];
                            }
                            if (isset($res['nickname'])) {
                                $data['nickName'] = $res['nickname'];
                            }
                            if (isset($res['sex'])) {
                                $data['gender'] = $res['sex'];
                            }
                            $m_user->updateInfo(array('id' => $user_info['id']), $data);
                            $user_info['wx_mpopenid'] = $data['wx_mpopenid'];
                            $user_info['is_subscribe'] = $data['is_subscribe'];
                            break;
                    }
                }
            }
        }

        /*
        $op_type = 0;
        if(empty($user_info)){
            $op_type = 1;
            $result = $wechat->getWxOpenid($code);
            $wx_mpopenid = $result['openid'];
        }elseif(empty($user_info['wx_mpopenid']) || empty($user_info['avatarUrl']) || empty($user_info['nickName'])){
            $op_type = 2;
            $result = $wechat->getWxOpenid($code);
            $wx_mpopenid = $result['openid'];
        }else{
            $wx_mpopenid = $user_info['wx_mpopenid'];
            $op_type = 3;
        }
        $access_token = $wechat->getWxAccessToken();
        $res = $wechat->getWxUserDetail($access_token ,$wx_mpopenid);
        if(!isset($res['openid'])){
            $access_token = $wechat->getWxAccessToken();
            $res = $wechat->getWxUserDetail($access_token ,$wx_mpopenid);
        }
        if(isset($res['openid'])){
            switch ($op_type){
                case 1:
                    $user_info = array('openid'=>$openid,'avatarUrl'=>$res['headimgurl'],'nickName'=>$res['nickname'],
                        'gender'=>$res['sex'],'wx_mpopenid'=>$wx_mpopenid,'is_subscribe'=>$res['subscribe']);
                    $user_id = $m_user->addInfo($user_info);
                    $user_info['id'] = $user_id;
                    break;
                case 2:
                    $data = array('wx_mpopenid'=>$wx_mpopenid,'is_subscribe'=>$res['subscribe'],'avatarUrl'=>$res['headimgurl'],
                        'nickName'=>$res['nickname'],'gender'=>$res['sex']);
                    $m_user->updateInfo(array('id'=>$user_info['id']),$data);
                    $user_info['wx_mpopenid'] = $data['wx_mpopenid'];
                    $user_info['is_subscribe'] = $data['is_subscribe'];
                    break;
                case 3:
                    $data = array('is_subscribe'=>$res['subscribe'],'avatarUrl'=>$res['headimgurl'],
                        'nickName'=>$res['nickname'],'gender'=>$res['sex']);
                    $m_user->updateInfo(array('id'=>$user_info['id']),$data);
                    $user_info['is_subscribe'] = $data['is_subscribe'];
                    break;
            }
        }

        $is_subscribe = intval($user_info['is_subscribe']);
        if(!$is_subscribe){
            $this->assign('openid',$openid);
            $this->assign('box_id',$box_id);
            $this->display('subscribe');
            exit;
        }
        */

        $redis = new \Common\Lib\SavorRedis();
        $redis->select(15);
        $cache_key = 'savor_box_'.$box_id;
        $redis_box_info = $redis->get($cache_key);
        if(empty($redis_box_info)){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'包间ID错误'),'JSONP');
        }
        $box_info = json_decode($redis_box_info,true);
        $cache_key = 'savor_room_' . $box_info['room_id'];
        $redis_room_info = $redis->get($cache_key);
        $room_info = json_decode($redis_room_info, true);
        $cache_key = 'savor_hotel_' . $room_info['hotel_id'];
        $redis_hotel_info = $redis->get($cache_key);
        $hotel_info = json_decode($redis_hotel_info, true);

        $hotel_id = $room_info['hotel_id'];
        $room_id = $box_info['room_id'];
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getInfo(array('hotel_id'=>$hotel_id,'room_id'=>$room_id,'status'=>1));
        $staff_openid = $res_staff['openid'];
        $where = array('openid'=>$staff_openid);
        $staffuser_info = $m_user->getOne('avatarUrl,nickName',$where,'id desc');

        $params = array('staff_id'=>$res_staff['id'],'user_id'=>$user_info['id'],'box_id'=>$box_id);
        $encode_params = encrypt_data(json_encode($params));
        $staffuser_info['ep'] = $encode_params;

        $m_tags = new \Common\Model\Smallapp\TagsModel();
        $fields = 'id,name';
        $where = array('status'=>1,'category'=>1);
        $where['hotel_id'] = array('in',array($hotel_id,0));
        $res_tags = $m_tags->getDataList($fields,$where,'type desc,id desc');
        $tags = array();
        foreach ($res_tags as $v){
            $tags[] = array('id'=>$v['id'],'value'=>$v['name'],'selected'=>false);
        }

        $this->assign('tags',json_encode($tags));
        $this->assign('uinfo',$staffuser_info);
        $this->display();

    }


    public function addcomment(){
        $ep = I('post.ep','');
        $score = I('post.score',0,'intval');
        $content = I('post.content','','trim');
        $tag_ids = I('post.tags','');

        $params_info = decrypt_data($ep);
        if(empty($params_info) || !is_array($params_info)){
            $this->ajaxReturn(array('code'=>10001,'msg'=>'参数信息异常'),'JSONP');
        }
        if(empty($score) || $score>5){
            $this->ajaxReturn(array('code'=>10002,'msg'=>'评分不能为空'),'JSONP');
        }
        $staff_id = $params_info['staff_id'];
        $user_id = $params_info['user_id'];
        $data = array('staff_id'=>$staff_id,'user_id'=>$user_id,'score'=>$score,'status'=>1);
        if($content){
            $data['content'] = $content;
        }
        if(empty($content) && empty($tag_ids)){
            $this->ajaxReturn(array('code'=>10003,'msg'=>'请选择评价标签或填写评价内容'),'JSONP');
        }
        $m_comment = new \Common\Model\Smallapp\CommentModel();
        $comment_id = $m_comment->add($data);
        if($comment_id && !empty($tag_ids)){
            $tag_ids_arr = explode(',',$tag_ids);
            $tag_datas = array();
            foreach ($tag_ids_arr as $v){
                $tag_datas[]=array('comment_id'=>$comment_id,'tag_id'=>intval($v));
            }
            $m_tagids = new \Common\Model\Smallapp\CommenttagidsModel();
            $m_tagids->addAll($tag_datas);

            $redis = new \Common\Lib\SavorRedis();
            $redis->select(15);
            $cache_key = 'savor_box_'.$params_info['box_id'];
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info,true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);

            $hotel_id = $room_info['hotel_id'];
            $room_id = $box_info['room_id'];
            $m_staff = new \Common\Model\Integral\StaffModel();
            $res_staff = $m_staff->getInfo(array('hotel_id'=>$hotel_id,'room_id'=>$room_id,'status'=>1));
            $message = array('action'=>140,'forscreen_char'=>'感谢您的评价，您的建议是我们前进的动力～',
                'waiterName'=>'','waiterIconUrl'=>'');
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
            $res_netty = $m_netty->pushBox($box_info['mac'],json_encode($message));
            if(isset($res_netty['error_code']) && $res_netty['error_code']==90109){
                $m_netty->pushBox($box_info['mac'],json_encode($message));
            }
        }
        $this->ajaxReturn(array('code'=>10000,'msg'=>'评论成功'),'JSONP');
    }

}