<?php
namespace Smallapp4\Controller;
use \Common\Controller\CommonController as CommonController;
class ForscreenHelpController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'helpplay':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'forscreen_id'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('forscreen_id'=>1001,'openid'=>1001);
                break;
            case 'userlist':
                $this->is_verify = 1;
                $this->valid_fields = array('forscreen_id'=>1001,'page'=>1001,'pagesize'=>1002);
                break;
            case 'addhelp':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'help_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function helpplay(){
        $openid = $this->params['openid'];
        $forscreen_id = intval($this->params['forscreen_id']);
        $m_user = new \Common\Model\Smallapp\UserModel();
        $fields = 'id,avatarUrl,nickName';
        $res_user = $m_user->getOne($fields,array('openid'=>$openid,'status'=>1),'');
        if(empty($res_user)){
            $this->to_back(90116);
        }
        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $order = 'id asc';
        $limit = '0,1';
        $res_forscreen = $m_forscreen->getWhere('id',array('forscreen_id'=>$forscreen_id),$order,$limit);
        if(empty($res_forscreen)){
            $this->to_back(90105);
        }
        $forscreen_record_id = $res_forscreen[0]['id'];
        $m_forscreenhelp = new \Common\Model\Smallapp\ForscreenHelpModel();
        $where = array('openid'=>$openid,'forscreen_record_id'=>$forscreen_record_id);
        $res_help = $m_forscreenhelp->getInfo($where);
        if(empty($res_help)){
            $add_data = array('openid'=>$openid,'forscreen_record_id'=>$forscreen_record_id,'status'=>1);
            $m_forscreenhelp->add($add_data);
        }
        $res = array('forscreen_id'=>$forscreen_record_id);
        $this->to_back($res);
    }

    public function detail(){
        $openid = $this->params['openid'];
        $forscreen_id = intval($this->params['forscreen_id']);
        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res_forscreen = $m_forscreen->getInfo(array('id'=>$forscreen_id));

        $m_forscreenhelpuser = new \Common\Model\Smallapp\ForscreenHelpuserModel();
        $m_forscreenhelp = new \Common\Model\Smallapp\ForscreenHelpModel();
        $where = array('forscreen_record_id'=>$forscreen_id);
        $res_help = $m_forscreenhelp->getInfo($where);
        $help_id = $res_help['id'];

        $res_data = array('content'=>'快来帮我助力！<br>我要在全国千家高端餐厅show','help_id'=>$help_id);

        if($res_forscreen['openid']==$openid){
            $res_data['status'] = 1;//1 邀请好友助力 2帮他上电视 3已助力
        }else{
            $res_huser = $m_forscreenhelpuser->getInfo(array('help_id'=>$help_id,'openid'=>$openid));
            if(empty($res_huser)){
                $res_data['status'] = 2;
            }else{
                $res_data['status'] = 3;
            }
        }
        $helpuser_num = $m_forscreenhelpuser->countNum(array('help_id'=>$help_id));
        $res_data['rate'] = $this->help_rate($helpuser_num);

        $typeinfo = C('RESOURCE_TYPEINFO');
        $oss_host = 'http://'.C('OSS_HOST').'/';
        $imgs_info = json_decode($res_forscreen['imgs'],true);
        $oss_addr = $imgs_info[0];
        $tempInfo = pathinfo($oss_addr);
        $surfix = $tempInfo['extension'];
        if($surfix){
            $surfix = strtolower($surfix);
        }
        if(isset($typeinfo[$surfix])){
            $res_data['media_type'] = $typeinfo[$surfix];
        }else{
            $res_data['media_type'] = 3;
        }

        $res_data['forscreen_url'] = $oss_addr;
        $res_data['oss_addr'] = $oss_host.$oss_addr;
        if($res_data['media_type']==2){
            $res_data['img_url'] = $oss_host.$oss_addr;
        }else{
            $res_data['img_url'] = $oss_host.$oss_addr.'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450';
        }
        $where = array('openid'=>$res_forscreen['openid']);
        $fields = 'id user_id,avatarUrl,nickName';
        $res_user = $m_user->getOne($fields, $where);
        $res_data['user'] = $res_user;
        $this->to_back($res_data);
    }

    public function userlist(){
        $forscreen_id = intval($this->params['forscreen_id']);
        $page = intval($this->params['page']);
        $pagesize = !empty($this->params['pagesize'])?intval($this->params['pagesize']):10;
        $all_nums = $page * $pagesize;

        $m_forscreenhelp = new \Common\Model\Smallapp\ForscreenHelpModel();
        $where = array('forscreen_record_id'=>$forscreen_id);
        $res_help = $m_forscreenhelp->getInfo($where);
        $help_id = $res_help['id'];

        $fields = 'u.id user_id,u.avatarUrl,u.nickName,h.add_time';
        $where = array('h.help_id'=>$help_id);
        $order = 'h.id asc';
        $limit = "0,$all_nums";
        $m_forscreenhelpuser = new \Common\Model\Smallapp\ForscreenHelpuserModel();
        $res_huser = $m_forscreenhelpuser->getList($fields,$where,$order,$limit);
        $datalist = array();
        foreach ($res_huser as $v){
            $datalist[] = $v;
        }
        $total_num = $m_forscreenhelpuser->countNum(array('help_id'=>$help_id));
        $res = array('datalist'=>$datalist,'total_num'=>$total_num);
        $this->to_back($res);
    }

    public function addhelp(){
        $openid = $this->params['openid'];
        $help_id = intval($this->params['help_id']);
        $m_user = new \Common\Model\Smallapp\UserModel();
        $fields = 'id as user_id,avatarUrl,nickName';
        $res_user = $m_user->getOne($fields,array('openid'=>$openid,'status'=>1),'');
        if(empty($res_user)){
            $this->to_back(90116);
        }
        $m_forscreenhelpuser = new \Common\Model\Smallapp\ForscreenHelpuserModel();
        $where = array('help_id'=>$help_id,'openid'=>$openid);
        $res_help = $m_forscreenhelpuser->getInfo($where);
        if(!empty($res_help)){
            $this->to_back(90107);
        }
        $data = array('help_id'=>$help_id,'openid'=>$openid);
        $m_forscreenhelpuser->add($data);
        $helpuser_num = $m_forscreenhelpuser->countNum(array('help_id'=>$help_id));
        $rate = $this->help_rate($helpuser_num);
        $res_data = array('rate'=>$rate,'user'=>$res_user);
        $this->to_back($res_data);
    }

    private function help_rate($helpuser_num){
        $rate = 30;
        if($helpuser_num){
            $first_num = 5;
            $last_num = $helpuser_num - $first_num;
            if($last_num>0){
                $other_rate = $first_num*5+$last_num;
            }else{
                $other_rate = $helpuser_num*5;
            }
            $rate = $rate+$other_rate>90?90:$rate+$other_rate;
        }
        return $rate;
    }
}