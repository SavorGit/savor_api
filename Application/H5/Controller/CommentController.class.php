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
        $wechat = new \Common\Lib\Wechat();
        if($code){
            $params_info = explode('-'.$params);
            $openid = $params_info[0];
            $box_id = $params_info[1];

            $m_user = new \Common\Model\Smallapp\UserModel();
            $where = array('openid'=>$openid);
            $user_info = $m_user->getOne('id,avatarUrl,nickName,wx_mpopenid,is_subscribe',$where,'id desc');
            $op_type = 0;
            if(empty($user_info)){
                $op_type = 1;
                $result = $wechat->getWxOpenid($code);
                $wx_mpopenid = $result['openid'];
            }elseif(empty($user_info['wx_mpopenid'])){
                $op_type = 2;
                $result = $wechat->getWxOpenid($code);
                $wx_mpopenid = $result['openid'];
            }else{
                $wx_mpopenid = $user_info['wx_mpopenid'];
                $op_type = 3;
            }
            $access_token = $wechat->getWxAccessToken();
            $res = $wechat->getWxUserInfo($access_token ,$wx_mpopenid);
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
                        $data = array('wx_mpopenid'=>$wx_mpopenid,'is_subscribe'=>$res['subscribe']);
                        $m_user->updateInfo(array('id'=>$user_info['id']),$data);
                        $user_info['wx_mpopenid'] = $data['wx_mpopenid'];
                        $user_info['is_subscribe'] = $data['is_subscribe'];
                        break;
                    case 3:
                        $data = array('is_subscribe'=>$res['subscribe']);
                        $m_user->updateInfo(array('id'=>$user_info['id']),$data);
                        $user_info['is_subscribe'] = $data['is_subscribe'];
                        break;
                }
            }
            $is_subscribe = intval($user_info['is_subscribe']);

            $redis = new \Common\Lib\SavorRedis();
            $redis->select(15);
            $cache_key = 'savor_box_'.$box_id;
            $redis_box_info = $redis->get($cache_key);
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
            $res_staff = $m_staff->getInfo(array('hotel_id'=>$hotel_id,'room_id'=>$room_id));
            $staff_openid = $res_staff['openid'];
            $where = array('openid'=>$staff_openid);
            $staffuser_info = $m_user->getOne('id as user_id,avatarUrl,nickName',$where,'id desc');
            $staffuser_info['staff_id'] = $res_staff['id'];
            $staffuser_info['user_id'] = $user_info['id'];
            $params = array('staff_id'=>$staffuser_info['staff_id'],'user_id'=>$staffuser_info['user_id'],'box_id'=>$staffuser_info['box_id']);
            $encode_params = encrypt_data(json_encode($params));
            $staffuser_info['ep'] = $encode_params;

            $m_commenttags = new \Common\Model\Smallapp\CommenttagsModel();
            $fields = 'id as tag_id,name';
            $where = array('status'=>1);
            $where['hotel_id'] = array('in',array($hotel_id,0));
            $res_tags = $m_commenttags->getDataList($fields,$where,'type desc,id desc');

            $this->assing('tags',$res_tags);
            $this->assing('uinfo',$staffuser_info);
            $this->assign('is_subscribe',$is_subscribe);
            $this->display();
        }else{
            $host_name = http_host();
            $url = $host_name.'/h5/comment/info/p/'.$params;
            $wechat->wx_oauth($url);
        }
    }


    public function addcomment(){
        $ep = I('post.ep','');
        $score = I('post.score',0,'intval');
        $content = I('post.content','','trim');
        $tag_ids = I('post.tags','');

        $ep_info = decrypt_data($ep);
        $params_info = json_decode($ep_info,true);
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
            $message = array('action'=>140);
            $m_netty = new \Common\Model\NettyModel();
            $res_netty = $m_netty->pushBox($box_info['mac'],json_encode($message));
            if(isset($res_netty['error_code']) && $res_netty['error_code']==90109){
                $m_netty->pushBox($box_info['mac'],json_encode($message));
            }
        }
        $this->ajaxReturn(array('code'=>10000,'msg'=>'评论成功'),'JSONP');
    }

}