<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class DistributionuserController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'datalist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001);
                break;
            case 'deluser':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'sale_uid'=>1001);
                break;
        }
        parent::_init_();
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $page = $this->params['page'];

        $datalist = array();
        $m_distuser = new \Common\Model\Smallapp\DistributionUserModel();
        $res_duser = $m_distuser->getInfo(array('openid'=>$openid));
        $invite_uid = 0;
        if(!empty($res_duser) && $res_duser['level']==1){
            $pagesize = 10;
            $start = ($page-1)*$pagesize;
            $fields = 'a.id as sale_uid,user.openid,user.nickName,user.avatarUrl';
            $res_data = $m_distuser->getUserDatas($fields,array('a.parent_id'=>$res_duser['id'],'a.status'=>1),'a.id desc',"$start,$pagesize");
            if(!empty($res_data)){
                $datalist = $res_data;
            }
            $invite_uid = $res_duser['id'];
        }
        $res_data = array('datalist'=>$datalist,'invite_uid'=>$invite_uid);
        $this->to_back($res_data);
    }

    public function deluser(){
        $openid = $this->params['openid'];
        $sale_uid = $this->params['sale_uid'];

        $m_distuser = new \Common\Model\Smallapp\DistributionUserModel();
        $res_duser = $m_distuser->getInfo(array('openid'=>$openid,'status'=>1));
        $parent_id = intval($res_duser['id']);
        $res_sale_user = $m_distuser->getInfo(array('id'=>$sale_uid));
        if(!empty($res_sale_user) && $res_sale_user['parent_id']==$parent_id){
            $m_distuser->updateData(array('id'=>$sale_uid),array('status'=>2));
        }
        $this->to_back(array());
    }


}