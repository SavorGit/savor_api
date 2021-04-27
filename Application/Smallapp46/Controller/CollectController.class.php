<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
class CollectController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'recLogs':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'res_id'=>1001,'type'=>1001,'only_co'=>1000,'status'=>1000);
            break;
            case 'addGoodscollection':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'openid'=>1001,'phone'=>1001);
            break;
        }
        parent::_init_();
    }
    /**
     * 收藏、取消收藏
     */
    public function recLogs(){
        $openid = $this->params['openid'];
        $res_id  = $this->params['res_id'];
        $type    = $this->params['type'];//1:点播2:投屏3:节目单资源4:商品 5本地生活广告收藏 6店铺收藏
        $status  = $this->params['status'];
        $only_co = $this->params['only_co'] ? $this->params['only_co']:0;
        
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $data = array();
        $data['openid'] = $openid;
        $data['res_id']  = $res_id;
        $data['type']    = $type;
        if($only_co==1){
            $data['status']  = 1;
        }
        $info = $m_collect->getOne('status', $data);
        if(!empty($info)){
            if($only_co==1){
                $this->to_back(90131);
            }else {
                $m_collect->updateInfo($data, array('status'=>$status));
                $map['res_id']  = $res_id;
                $map['status']  = 1;
                $nums = $m_collect->countNum($map);

                $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
                $ret = $m_collect_count->field('nums')->where(array('res_id'=>$res_id))->find();
                $all_nums = intval($nums)+intval($ret['nums']);
                $this->to_back(array('nums'=>$all_nums));
            }
        }else {
            $data['status']  = $status;
            $ret = $m_collect->addInfo($data,1);
            if($ret){
                $map['res_id']  = $res_id;
                $map['status']  = 1;
                $nums = $m_collect->countNum($map);
                
                $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
                $ret = $m_collect_count->field('nums')->where(array('res_id'=>$res_id))->find();
                $all_nums = intval($nums)+intval($ret['nums']);
                $this->to_back(array('nums'=>$all_nums));
            }else {
                $this->to_back(91016);
            }
        }
    }

    public function addGoodscollection(){
        $usercollection_num = 5;

        $goods_id= intval($this->params['goods_id']);
        $openid = $this->params['openid'];
        $phone = $this->params['phone'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if($res_goods['status']!=2){
            $this->to_back(92020);
        }
        $cache_key = C('SAPP_SALE');
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $send_cache_key = $cache_key.'collection:sendsms'.$phone.$goods_id.$openid;
        $is_send = $redis->get($send_cache_key);
        if(!empty($is_send)){
            $this->to_back(92023);
        }

        $collection_cache_key = $cache_key.'collection:'.$openid;
        $collection_num = $redis->get($collection_cache_key);
        if(empty($collection_num)){
            $collection_num = 0;
        }
        if($collection_num>=$usercollection_num){
            $this->to_back(92022);
        }
        if($res_goods['jd_url']){
            $ucconfig = C('SMS_CONFIG');
            $options = array('accountsid'=>$ucconfig['accountsid'],'token'=>$ucconfig['token']);
            $ucpass= new \Common\Lib\Ucpaas($options);
            $appId = $ucconfig['appid'];
            $jd_goods_id = str_replace(array('https://item.jd.com/','.html'),array('',''),$res_goods['jd_url']);
            $param = "{$res_goods['name']},$jd_goods_id";
            $res_json = $ucpass->templateSMS($appId,$phone,$ucconfig['activity_goods_collection_templateid'],$param);
            $res_data = json_decode($res_json,true);
            $data = array('type'=>9,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                'url'=>$param,'tel'=>$phone,'resp_code'=>$res_data['resp']['respCode'],'msg_type'=>3);
            if(isset($res_data['resp']['respCode'])){
                $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
                $m_account_sms_log->addData($data);
            }
        }

        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $where = array('openid'=>$openid,'res_id'=>$goods_id);
        $res_collect = $m_collect->countNum($where);
        if(!$res_collect){
            $add_data = array('openid'=>$openid,'res_id'=>$goods_id,'type'=>4);
            $m_collect->add($add_data);
        }
        $res_data = array('message'=>'收藏成功');

        $redis->set($send_cache_key,1,18000);
        $collection_num = $collection_num+1;
        $redis->set($collection_cache_key,$collection_num,18000);
        $this->to_back($res_data);
    }
}