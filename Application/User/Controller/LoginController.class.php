<?php
namespace User\Controller;
use Common\Lib\EtacarRedis;
use Common\Lib\Verify;
use \Common\Controller\BaseController as BaseController;
/**
 * 登录
 */
class LoginController extends BaseController{
	private $need_member_login = array(
		'user_name'=> 1001,   //用户名
		'password' => 1001,   //密码
		'code'     => 1002,   //图片验证码
	);
	
	/**
	 * @desc   普通用户登录
	 * @author zhang.yingtao
	 * @since  2016.12.28
	 */
	public function memberLogin(){
		$user_name = $this->params['user_name'];  //用户名
		$password  = $this->params['password'];   //密码
		//$code      = trim($this->params['code']); //图片验证码
		//根据用户名、密码获取用户信息
		$map['user_name'] = $user_name;
		$map['password']  = $password;
		$map['status']    = 0;
		$userInfo = $this->getUserInfo($map);
		if(!empty($userInfo)){
			$traceinfo = $this->traceinfo;
			$user =  json_encode(array('user_id'=>$userInfo['user_id'],'type'=>1));  //type:1为普通用户 2：为厂商用户
			$token  = create_token($traceinfo['deviceid'],$user);
			$userToken = new \Common\Model\User\UserTokenModel();
			$tokenInfo = $userToken->getUserToken(array('deviceid'=>$traceinfo['deviceid']));
			
			//更新user_token表
			$data = $where =  array();
			if($tokenInfo){
				$data['user_id'] = $userInfo['user_id'];
				$data['token'] = $token;
				$data['is_logout'] = 0;
				$data['update_time'] = time();
				$where['deviceid'] = $traceinfo['deviceid'];
				$rt = $userToken->updateUserToken($data,$where);
			}else {
				$data['user_id'] = $userInfo['user_id'];
				$data['deviceid'] = $traceinfo['deviceid'];
				$data['token'] = $token;
				$data['create_time'] = time();
				$rt = $userToken->addUserToken($data);
			}
			if($rt){
				$this->addLoginLog($userInfo,$traceinfo);        //记录登录日志
				$userInfo['token'] = $token;
				$this->to_back(array('userinfo'=>$userInfo));
					
			}else {
				$this->to_back('12003');
			}
		}else  {
			$this->to_back('12002');
		}
		
	}
	
	/**
	 * @desc 厂商用户登录
	 */
	public function factoryLogin(){
		
	}
	/**
	 * @desc 获取用户信息
	 */
	private function getUserInfo($map){
		$userModel = new \Common\Model\User\UserModel();
		$userinfo = $userModel->getUserInfo($map);
		return $userinfo;
	}
	/**
	 * @desc 记录用户登录日志
	 */
	private function addLoginLog($userInfo,$traceinfo){
		$map = array();
		$location = explode(',', $this->traceinfo['location']) ;
		$traceStr = $_SERVER['HTTP_TRACEINFO'];
		$map['user_id'] = $userInfo['id'];
		$map['deviceid'] = $traceinfo['deviceid'];
		$map['traceinfo'] = $traceStr;
		$map['longitude'] = $location[0];
		$map['latitude']  = $location[1];
		$map['where_from'] = getClientId($traceinfo['clientname']);
		$map['ip'] = get_client_ipaddr();
		$map['addtime'] = time();
		$loginLogModel = new \Common\Model\User\LoginLogModel();
		$loginLogModel->addLoginLog($map);
	}
}