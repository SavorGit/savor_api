<?php
namespace Smallapp3\Controller;
use \Common\Controller\CommonController;
class RedpacketController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getConfig'://获取红包业务配置
                $this->is_verify =1;
                $this->valid_fields = array('type'=>1001);
                break;
            case 'sendTvbonus':
                $this->is_verify =1;
                $this->valid_fields = array('open_id'=>1001,'total_money'=>1001,'amount'=>1001,
                    'surname'=>1001,'sex'=>1001,'bless_id'=>1001,'scope'=>1001,'mac'=>1001);
                break;
            case 'getresult':
                $this->is_verify =1;
                $this->valid_fields = array('open_id'=>1001,'order_id'=>1001);
                break;
            case 'sendList':   //发送红包列表
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001);
                break;
            case 'redpacketDetail':  //红包领取详情
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001,'page'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getConfig(){
        $type = $this->params['type'];
        $data = array();
        if($type==1){//红包开关
            $data['is_open_red_packet'] = 1;
        }else if($type==2){//发红包配置
            
            $cf_bless_arr = C('SMALLAPP_REDPACKET_BLESS');
            $cf_range_arr = C('SMALLAPP_REDPACKET_SEND_RANGE');
            foreach($cf_bless_arr as $v){
                $bless_tmp[] = $v;
            }
            foreach($cf_range_arr as $v){
                $range_tmp[] = $v;
            }
            
            $data['bless'] = $bless_tmp;
            $data['range'] = $range_tmp;
        }
        
        $this->to_back($data);
    }

    /**
     * @desc 发送电视红包
     */
    public function sendTvbonus(){
        $open_id = $this->params['open_id'];
        $total_money = $this->params['total_money'];
        $amount = $this->params['amount'];
        $surname = $this->params['surname'];
        $sex = $this->params['sex'];
        $bless_id = $this->params['bless_id'];
        $scope = $this->params['scope'];
        $box_mac = $this->params['mac'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$open_id,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        if($total_money<$amount*0.3){
            $this->to_back(90117);
        }
        $surname_c = new \Common\Lib\Surname();
        $check_name = $surname_c->checkSurname($surname);
        if(!$check_name){
            $this->to_back(90118);
        }
        if(!array_key_exists($scope,C('SMALLAPP_REDPACKET_SEND_RANGE'))){
            $this->to_back(90119);
        }
        $m_box = new \Common\Model\BoxModel();
        $where = array();
        $where['mac'] = $box_mac;
        $where['flag'] = 0;
        $where['state']= 1;
        $box_info = $m_box->where($where)->count();
        if(empty($box_info)){
            $this->to_back(15003);
        }
        $redpacket = array('user_id'=>$user_info['id'],'total_fee'=>$total_money,'amount'=>$amount,'surname'=>$surname,
            'sex'=>$sex,'bless_id'=>$bless_id,'scope'=>$scope,'mac'=>$box_mac);
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $order_id = $m_redpacket->addData($redpacket);
        if(!$order_id){
            $this->to_back(90120);
        }
        $sign_key = create_sign($order_id);
        $id_key = $sign_key.$order_id;
        $jump_url = http_host().'/h5/scanqrcode/scanpage?id='.$id_key;

        $result = array('order_id'=>$order_id,'jump_url'=>$jump_url);
        $this->to_back($result);
    }

    public function getresult(){
        $open_id = $this->params['open_id'];
        $order_id = $this->params['order_id'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$open_id,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $res_order = $m_redpacket->getInfo(array('id'=>$order_id));
        $status = $res_order['status'];
        $jump_url = http_host().'/h5/scanqrcode/getresult?oid='.$order_id;
        $data = array('status'=>$status,'jump_url'=>$jump_url);
        $this->to_back($data);
    }
    public function sendList(){
        $openid = $this->params['openid'];
        $page   = $this->params['page'];
        
        $pagesize = 15;
        $all_nums = $page * $pagesize;
        $limit = "limit 0,$all_nums";
        $order = 'a.add_time desc';
        $fields = "a.id order_id,a.bless_id,a.pay_fee,a.pay_time";
        $where= array();
        $where['user.openid'] = $openid;
        $where['a.status'] = array('in','4,5,6');
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $data = $m_redpacket->getList($fields,$where,$order,$limit);
        $redpacket_bless = C('SMALLAPP_REDPACKET_BLESS');
        foreach($data as $key=>$v){
            $data[$key]['bless'] = $redpacket_bless[$v['bless_id']];
        }
        $this->to_back($data);
    }
    public function redpacketDetail(){
        $openid   = $this->params['openid'];
        $order_id = $this->params['order_id']; 
        $page     = $this->params['page'];
        $m_redpacket = new \Common\Model\Smallapp\RedpacketModel();
        $fields = 'user.avatarUrl,user.nickName,a.amount,a.pay_fee,a.status';
        
        $where = array();
        $where['a.id'] = $order_id;
        
        $info = $m_redpacket->getInfo($fields,$where);
        
        if(!in_array($info['status'], array(4,5,6))){
            $this->to_back(90130);
        }
        //领取详情
        $m_redpacket_receive = new \Common\Model\Smallapp\RedpacketReceiveModel();
        
        $pagesize = 15;
        $all_nums = $page* $pagesize;
        $fields = 'user.avatarUrl,user.nickName,a.add_time,a.money';
        $order  = 'a.receive_time desc';
        $limit  = "limit 0,$all_nums";
        $where = array();
        $where['a.redpacket_id'] = $order_id;
        $receive_list = $m_redpacket_receive->getList($fields, $where, $order, $limit);
        $where = array();
        $where['redpacket_id'] = $order_id;
        $receive_nums = $m_redpacket_receive->where($where)->count(); //领取个数
        
        if($receive_nums>0){
            $rt = $m_redpacket_receive->field('sum(`money`) as receive_money')->find();
            $info['receive_nums'] = $receive_nums;
            $info['receive_money']= $rt['receive_money'];
        }else {
            $info['receive_nums'] = 0;
            $info['receive_money']= 0;
        }
        $data = array();
        $data['info'] = $info;
        $data['receive_list'] = $receive_list;
        $this->to_back($data);
    }

}
