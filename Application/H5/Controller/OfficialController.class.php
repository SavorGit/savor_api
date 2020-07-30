<?php
/**
 * @deprecated  关注公众号 获取用户openid
 * @author      zhang.yingtao
 * @since       2020-07-27- 16:00:02
 */
namespace H5\Controller;
use Think\Controller;

class OfficialController extends Controller {
    //private $jumpUrl = C('OFFICIAL_ACCOUNT_ARTICLE_URL');
    public function getUserInfo(){
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
            $url = $host_name.'/h5/official/getuserinfo/p/'.$params;
            $wechat->wx_oauth($url);
        }else {
            $this->assign('openid',$user_info['wx_mpopenid']);
            $this->assign('jumpurl',C('OFFICIAL_ACCOUNT_ARTICLE_URL'));
            $this->display('getuserinfo');
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
                    $this->assign('openid',$wx_mpopenid);
                    $this->assign('jumpurl',C('OFFICIAL_ACCOUNT_ARTICLE_URL'));
                    $this->display('getuserinfo');
                }
            }
        }
    }
    public function testone(){
        $openid= I('openid');
        $wechat = new \Common\Lib\Wechat();
        $access_token = $wechat->getWxAccessToken();
        $res = $wechat->getWxUserDetail($access_token ,$openid);
        print_r($res);exit;
    }
}