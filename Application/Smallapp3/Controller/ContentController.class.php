<?php
namespace Smallapp3\Controller;
use \Common\Controller\CommonController as CommonController;
class ContentController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotplaylist':
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001,'pagesize'=>1002);
                break;
        }
        parent::_init_();
    }

    public function getHotplaylist(){
        $page = intval($this->params['page']);
        $pagesize = !empty($this->params['pagesize'])?intval($this->params['pagesize']):5;
        $all_nums = $page * $pagesize;
        $m_playlog = new \Common\Model\Smallapp\PlayLogModel();
        $where = array('type'=>4);

        $orderby = 'nums desc';
        $limit = "0,$all_nums";
        $fields = 'res_id as forscreen_id,nums as res_nums';
        $res_play = $m_playlog->getWhere($fields,$where,$orderby,$limit,'');
        $datalist = array();
        $oss_host = 'http://'.C('OSS_HOST').'/';
        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $m_user = new \Common\Model\Smallapp\UserModel();

        foreach ($res_play as $v){
            $res_forscreen = $m_forscreen->getInfo(array('id'=>$v['forscreen_id']));
            $v['res_type'] = $res_forscreen['resource_type'];

            $where = array('openid'=>$res_forscreen['openid']);
            $fields = 'id user_id,avatarUrl,nickName';
            $res_user = $m_user->getOne($fields, $where);
            $v['nickName'] = $res_user['nickName'];
            $v['avatarUrl'] = $res_user['avatarUrl'];
            $imgs_info = json_decode($res_forscreen['imgs'],true);
            $forscreen_url = $imgs_info[0];
            if($v['res_type']==1){
                $res_url = $oss_host.$forscreen_url;
            }else{
                $res_url = $oss_host.$forscreen_url.'?x-oss-process=image/resize,p_20';
            }
            $pubdetail = array('res_url'=>$res_url,'forscreen_url'=>$forscreen_url,'duration'=>$res_forscreen['duration'],
                'resource_size'=>$res_forscreen['resource_size'],'res_id'=>$res_forscreen['resource_id']);
            $addr_info = pathinfo($forscreen_url);
            $pubdetail['filename'] = $addr_info['basename'];
            $v['pubdetail'] = $pubdetail;

            $datalist[] = $v;
        }
        $data = array('datalist'=>$datalist);
        $this->to_back($data);
    }
}