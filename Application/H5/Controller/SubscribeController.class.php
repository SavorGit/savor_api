<?php
/*
 * 关注公众号
 */
namespace H5\Controller;
use Think\Controller;

class SubscribeController extends Controller {

    public function mp(){
        $params = I('p','');
        $code = I('code', '');
        $wechat = new \Common\Lib\Wechat();
        if($code){
            $openid = $params;

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
            $this->assign('is_subscribe',$is_subscribe);
            $this->display();
        }else{
            $host_name = http_host();
            $url = $host_name.'/h5/subscribe/mp/p/'.$params;
            $wechat->wx_oauth($url);
        }
    }
}