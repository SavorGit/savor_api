<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class UserController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getSessionkey':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'invitesale':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                break;
            case 'messagenum':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getSessionkey(){
        $openid = $this->params['openid'];
        $cache_key = C('SAPP_OPS').'session_openid:'.$openid;
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $res_session = $redis->get($cache_key);
        $session_key = '';
        if(!empty($res_session)){
            $session_key = $res_session;
        }
        $this->to_back(array('session_key'=>$session_key));
    }

    public function invitesale(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $where = array('merchant.hotel_id'=>$hotel_id,'merchant.status'=>1,'a.status'=>1,'a.level'=>1);
        $m_salestaff = new \Common\Model\Integral\StaffModel();
        $fields = 'a.id,a.openid,a.level,user.avatarUrl,user.nickName';
        $res_sale_staff = $m_salestaff->getMerchantStaff($fields,$where,'a.id desc','0,1');

        $qrcode_url = '';
        $qrinfo = '';
        if(!empty($res_sale_staff)){
            $is_edit_staff = $m_staff->check_edit_salestaff($res_staff,$hotel_id);
            if($is_edit_staff==1){
                $ops_staff_id = $res_staff['id'];
                $cache_key = C('SAPP_SALE_INVITE_QRCODE');
                $uniq_id = uniqid('',true);
                $invite_cache_key = $res_sale_staff[0]['id'].'&'.$uniq_id.'&'.$ops_staff_id;
                $code_key = $cache_key.$res_sale_staff[0]['id'].":$invite_cache_key";
                $redis  =  \Common\Lib\SavorRedis::getInstance();
                $redis->select(14);
                $redis->set($code_key,$res_sale_staff[0]['id'],3600*4);
                $qrinfo = encrypt_data($invite_cache_key);
                $host_name = C('HOST_NAME');
                $qrcode_url = $host_name."/basedata/saleQrcode/inviteQrcode?qrinfo=$qrinfo";
            }
        }
        $res = array('qrcode_url'=>$qrcode_url,'qrcode'=>$qrinfo);
        $this->to_back($res);
    }

    public function messagenum(){
        $openid = $this->params['openid'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $all_message_types = array(
            '13'=>array('name'=>'盘点任务通知','icon'=>'WeChat/resource/opsmsg-icons/13.png','type'=>13)
        );
        $m_message = new \Common\Model\Smallapp\MessageModel();
        $fields = 'count(*) as num,type';
        $where = array('ops_staff_id'=>$res_staff['id'],'read_status'=>1,'type'=>array('in',array_keys($all_message_types)));
        $res_unread = $m_message->getDatas($fields,$where,'','','type');
        $oss_host = get_oss_host();
        $unread_nums = array();
        foreach ($res_unread as $v){
            $unread_nums[$v['type']] = $v['num'];
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $datalist = array();
        foreach ($all_message_types as $v){
            $v['icon'] = $oss_host.$v['icon'];
            $num = 0;
            if(isset($unread_nums[$v['type']])){
                $num = $unread_nums[$v['type']];
            }
            $v['num'] = $num;
            $mwhere = array('a.ops_staff_id'=>$res_staff['id'],'a.read_status'=>1,'a.type'=>$v['type']);
            $fields = 'a.staff_openid,hotel.name as hotel_name,a.add_time';
            $res_unread = $m_message->getMessageInfo($fields,$mwhere,'a.id desc','0,1');
            $content = '';
            $add_time = '';
            if(!empty($res_unread)){
                $add_time = date('Y.m.d H:i',strtotime($res_unread[0]['add_time']));
                $res_user = $m_user->getOne('nickName',array('openid'=>$res_unread[0]['staff_openid']),'');
                $content = $res_unread[0]['hotel_name'].'店的'.$res_user['nickName'].'完成了盘点任务';
            }
            $v['content'] = $content;
            $v['add_time'] = $add_time;
            $datalist[]=$v;
        }
        $this->to_back(array('datalist'=>$datalist));
    }
}