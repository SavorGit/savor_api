<?php
namespace Smallapp21\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class DemandController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getList':
                 $this->is_verify = 1;
                 $this->valid_fields = array('page'=>1001,'openid'=>1001);
            break;
        }
        parent::_init_();
    }
    public function getList(){
        $oss_host = "http://".C('OSS_HOST').'/';

        $m_home = new \Common\Model\HomeModel();
        $size = 10;
        $page = $this->params['page'] ?  $this->params['page']: 1;
        $openid = $this->params['openid'] ;
        $fields = "content.id,content.title,content.duration,
                   CONCAT('".$oss_host."',content.`img_url`) img_url,
                   CONCAT('".$oss_host."',media.oss_addr) tx_url,'1' as type";
        $where = array();
        $where['a.state'] = 1;
        $order = 'a.sort_num asc';
        $limit = " limit 0,".$page*$size;
        $data = $m_home->getWhere($fields, $where, $order, $limit);
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        foreach($data as $key=>$v){
            $map = array();
            $map['openid']=$openid;
            $map['res_id'] =$v['id'];
            $map['type']   = 1;
            $map['status'] = 1;
            $is_collect = $m_collect->countNum($map);
            if(empty($is_collect)){
                $data[$key]['is_collect'] = 0;
            }else {
                $data[$key]['is_collect'] = 1;
            }
            $map = array();
            $map['res_id'] =$v['id'];
            $map['type']   = 1;
            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);
            $data[$key]['collect_num'] = $collect_num;
            //分享个数
            $map = array();
            $map['res_id'] =$v['id'];
            $map['type']   = 1;
            $map['status'] = 1;
            $share_num = $m_share->countNum($map);
            $data[$key]['share_num'] = $share_num;
            $tx_url = $v['tx_url'];
            $tx_url_arr = explode('?', $tx_url);
            $data[$key]['tx_url'] = $tx_url_arr[0];
        }
        $this->to_back($data);
    }
}