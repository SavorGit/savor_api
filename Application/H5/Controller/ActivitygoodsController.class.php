<?php
/*
 * 活动商品 积分兑换
 */
namespace H5\Controller;
use Think\Controller;

class ActivitygoodsController extends Controller {

    public function index(){
        $openid = I('get.openid','');
        if(empty($openid)){
            die('Parameter error');
        }
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $fields = 'id as goods_id,name,start_time,end_time';
        $where = array('type'=>30,'status'=>2);
        $goods = $m_goods->getDataList($fields,$where);
        print_r($goods);
        $this->assign('goods',$goods);
//        $this->display();
    }

    public function integralExchange(){
        $openid = I('openid','');
        $goods_id = I('goods_id',0,'intval');

        $res = array('code'=>10000,'msg'=>'兑换成功,请在1到2个工作日内,注意查收自己的微信零钱');
        $this->ajaxReturn($res,'JSONP');

        $os_agent = $_SERVER['HTTP_USER_AGENT'];
        $wx_browser = (bool) stripos($os_agent,'MicroMessenger');
        $res = array('code'=>10001,'msg'=>'fail');
        if($wx_browser){
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(5);
            $key = C('SAPP_FILE_FORSCREEN');
            $cache_key = $key.':h5file_forscreen_report';
            $res_cache = $redis->get($cache_key);
            if(empty($res_cache)){
                $num = 1;
            }else{
                $num = intval($res_cache)+1;
            }
            $redis->set($cache_key,$num);

            $res['code'] = 10000;
            $res['msg'] = 'success';
        }
        $this->ajaxReturn($res,'JSONP');
    }

}